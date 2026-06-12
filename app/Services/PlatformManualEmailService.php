<?php

namespace App\Services;

use App\Enums\PlatformManualEmailCategory;
use App\Models\DemonstrationRequest;
use App\Models\Organization;
use App\Models\PlatformManualEmail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PlatformManualEmailService
{
    public function __construct(
        private readonly ResendConfigService $resendConfig,
        private readonly TransactionalEmailService $transactionalEmail,
        private readonly AuditService $auditService,
        private readonly PlatformEmailBrandingService $emailBranding,
    ) {}

    /**
     * @return array{
     *     categories: list<array{value: string, label: string}>,
     *     recipients: list<array<string, mixed>>,
     *     mail_configured: bool,
     *     mailer: string,
     *     from_address: string,
     *     from_name: string,
     *     whatsapp_number: string|null
     * }
     */
    public function recipientsPayload(): array
    {
        $recipients = [];

        $tenants = Tenant::query()
            ->with(['clinics' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        foreach ($tenants as $tenant) {
            foreach ($tenant->clinics as $organization) {
                $this->appendOrganizationRecipients($recipients, $tenant, $organization);
            }
        }

        $leads = DemonstrationRequest::query()
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        foreach ($leads as $lead) {
            if (! is_string($lead->email) || trim($lead->email) === '') {
                continue;
            }

            $recipients[] = [
                'id' => 'lead:'.$lead->id,
                'type' => 'lead',
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'label' => trim(($lead->name ?: 'Lead').' · '.$lead->email),
                'group' => 'Leads',
            ];
        }

        usort($recipients, fn (array $a, array $b) => strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? '')));

        return [
            'categories' => PlatformManualEmailCategory::options(),
            'recipients' => $recipients,
            'mail_configured' => $this->resendConfig->isConfigured(),
            'mailer' => $this->resendConfig->getMailer(),
            'from_address' => $this->resendConfig->getFromAddress(),
            'from_name' => $this->resendConfig->getFromName(),
            'whatsapp_number' => $this->resendConfig->getWhatsappNumber(),
        ];
    }

    public function paginateHistory(int $perPage = 20): LengthAwarePaginator
    {
        return PlatformManualEmail::query()
            ->with(['tenant:id,name', 'organization:id,name', 'lead:id,name,email'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array{
     *     category: string,
     *     to_email: string,
     *     to_name?: string|null,
     *     subject: string,
     *     body: string,
     *     tenant_id?: int|null,
     *     organization_id?: int|null,
     *     lead_id?: int|null
     * }  $data
     */
    public function send(User $user, array $data): PlatformManualEmail
    {
        if (! $this->resendConfig->isConfigured()) {
            throw new \RuntimeException('Configure o Resend em Configurações da plataforma antes de enviar e-mails.');
        }

        PlatformConfigService::mergeIntoConfig();

        $category = PlatformManualEmailCategory::from($data['category']);
        $toEmail = Str::lower(trim($data['to_email']));
        $toName = isset($data['to_name']) ? trim((string) $data['to_name']) : null;
        $subject = trim($data['subject']);
        $bodyPlain = trim($data['body']);
        $resolvedName = $this->emailBranding->resolveRecipientName($bodyPlain, $toName);
        $isSupport = $category === PlatformManualEmailCategory::Support
            || $this->emailBranding->isSupportWhatsappBody($bodyPlain);

        $record = PlatformManualEmail::create([
            'user_id' => $user->id,
            'category' => $category,
            'recipient_email' => $toEmail,
            'recipient_name' => $resolvedName !== 'cliente' ? $resolvedName : ($toName !== '' ? $toName : null),
            'subject' => $subject,
            'body' => $bodyPlain,
            'tenant_id' => $data['tenant_id'] ?? null,
            'organization_id' => $data['organization_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'meta_json' => [
                'mailer' => $this->resendConfig->getMailer(),
                'from_address' => $this->resendConfig->getFromAddress(),
            ],
        ]);

        try {
            $view = $isSupport ? 'emails.support' : 'emails.transactional';
            $bodyForRender = $this->emailBranding->applyRecipientNameToGreeting($bodyPlain, $resolvedName);
            $templateData = $isSupport
                ? $this->emailBranding->buildSupportTemplateData($bodyPlain, $resolvedName)
                : array_merge($this->emailBranding->baseTemplateData(), [
                    'recipientName' => $resolvedName,
                    'body' => $this->emailBranding->formatManualBodyHtml($bodyForRender),
                    'manualEmail' => true,
                ]);

            $this->transactionalEmail->sendNow(
                $toEmail,
                $subject,
                $view,
                $templateData
            );

            $record->update(['sent_at' => now()]);
        } catch (\Throwable $e) {
            $record->delete();
            throw $e;
        }

        $this->auditService->log(
            'platform_manual_email_sent',
            'platform_manual_email',
            (int) $record->id,
            [
                'category' => $category->value,
                'recipient_email' => $toEmail,
                'subject' => $subject,
            ],
            $data['organization_id'] ?? null,
            $user->id
        );

        return $record->fresh(['tenant', 'organization', 'lead']);
    }

    /**
     * @param  list<array<string, mixed>>  $recipients
     */
    private function appendOrganizationRecipients(array &$recipients, Tenant $tenant, Organization $organization): void
    {
        $emailFields = [
            'contact' => ['field' => 'contact_email', 'label' => 'Contato'],
            'billing' => ['field' => 'billing_email', 'label' => 'Cobrança'],
            'notification' => ['field' => 'notification_email', 'label' => 'Notificações'],
        ];

        foreach ($emailFields as $type => $config) {
            $email = $organization->{$config['field']} ?? null;
            if (! is_string($email) || trim($email) === '') {
                continue;
            }

            $email = Str::lower(trim($email));
            $recipients[] = [
                'id' => 'org:'.$organization->id.':'.$type,
                'type' => 'organization',
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'email_type' => $type,
                'email_type_label' => $config['label'],
                'email' => $email,
                'name' => $organization->name,
                'label' => $tenant->name.' · '.$organization->name.' · '.$config['label'].' · '.$email,
                'group' => 'Clientes',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(PlatformManualEmail $email): array
    {
        return [
            'id' => $email->id,
            'category' => $email->category->value,
            'category_label' => $email->category->label(),
            'recipient_email' => $email->recipient_email,
            'recipient_name' => $email->recipient_name,
            'subject' => $email->subject,
            'body_preview' => Str::limit($email->body, 120),
            'tenant_id' => $email->tenant_id,
            'tenant_name' => $email->tenant?->name,
            'organization_id' => $email->organization_id,
            'organization_name' => $email->organization?->name,
            'lead_id' => $email->lead_id,
            'lead_name' => $email->lead?->name,
            'sent_at' => $email->sent_at?->toIso8601String(),
            'created_at' => $email->created_at?->toIso8601String(),
        ];
    }
}
