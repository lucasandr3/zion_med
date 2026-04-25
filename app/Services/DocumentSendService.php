<?php

namespace App\Services;

use App\Models\DocumentSend;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Support\MailBrand;
use Illuminate\Support\Facades\Mail;

class DocumentSendService
{
    public function __construct(
        protected PublicLinkService $publicLinkService,
        protected EvolutionGoClient $evolutionGoClient
    ) {}

    /**
     * Envia o link do formulário por e-mail e registra o envio.
     */
    public function sendByEmail(
        FormTemplate $template,
        string $recipientEmail,
        ?string $recipientPhone = null,
        ?\DateTimeInterface $expiresAt = null,
        ?int $personId = null,
        ?string $recipientName = null
    ): DocumentSend {
        if (! $template->public_token || ! $template->public_enabled) {
            $this->publicLinkService->generateToken($template);
            $template->refresh();
        }

        $url = $this->publicLinkService->getPublicUrl($template);
        $expiresAt = $expiresAt ?? $template->public_token_expires_at?->toDateTimeImmutable();

        $send = DocumentSend::create([
            'organization_id' => $template->organization_id ?? $template->clinic_id,
            'person_id' => $personId,
            'form_template_id' => $template->id,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'channel' => 'email',
            'sent_at' => now(),
            'expires_at' => $expiresAt,
            'public_token' => $template->public_token,
        ]);

        $organizationName = $template->organization?->name ?? 'Organização';
        $templateName = $template->name;

        Mail::send(
            'emails.document-sign-invite',
            MailBrand::with([
                'emailTitle' => 'Documento para assinatura',
                'organizationName' => $organizationName,
                'templateName' => $templateName,
                'signUrl' => $url,
                'recipientName' => $recipientName,
            ]),
            function ($message) use ($recipientEmail, $organizationName, $templateName) {
                $message->to($recipientEmail)
                    ->subject("{$organizationName} — documento para assinatura: {$templateName}");
            }
        );

        return $send;
    }

    /**
     * Reenvia o link do documento (cria novo registro de envio e envia e-mail).
     */
    public function reenvio(DocumentSend $documentSend): DocumentSend
    {
        $template = $documentSend->formTemplate;
        if (! $template->public_token || ! $template->public_enabled) {
            throw new \RuntimeException('Link público do template não está ativo.');
        }

        $recipientEmail = $documentSend->recipient_email;
        if (! $recipientEmail) {
            throw new \RuntimeException('Envio original não possui e-mail do destinatário.');
        }

        return $this->sendByEmail(
            $template,
            $recipientEmail,
            $documentSend->recipient_phone,
            $documentSend->expires_at?->toDateTimeImmutable(),
            $documentSend->person_id,
            $documentSend->recipient_name
        );
    }

    /**
     * Associa uma submissão a um envio (quando o paciente submete e o e-mail coincide).
     */
    public function linkSubmissionToSend(DocumentSend $send, int $formSubmissionId): void
    {
        if ($send->form_submission_id === null) {
            $send->update(['form_submission_id' => $formSubmissionId]);
        }
    }

    /**
     * Cancela um envio (revoga o convite para assinar; não desativa o link do template).
     */
    public function cancel(DocumentSend $documentSend): void
    {
        if ($documentSend->form_submission_id !== null) {
            throw new \RuntimeException('Não é possível cancelar um envio já assinado.');
        }
        $documentSend->update(['cancelled_at' => now()]);
    }

    /**
     * Envia e-mail de lembrete (mesmo link) e marca reminded_at.
     */
    public function sendReminder(DocumentSend $documentSend): bool
    {
        if ($documentSend->form_submission_id || $documentSend->isCancelled() || $documentSend->isExpired()) {
            return false;
        }
        $template = $documentSend->formTemplate;
        if (! $template->public_token || ! $template->public_enabled) {
            return false;
        }
        $url = $this->publicLinkService->getPublicUrl($template);
        $organizationName = $template->organization?->name ?? 'Organização';
        $templateName = $template->name;
        $email = $documentSend->recipient_email;
        if (! $email) {
            return false;
        }
        Mail::send(
            'emails.document-sign-reminder',
            MailBrand::with([
                'emailTitle' => 'Lembrete — documento pendente',
                'organizationName' => $organizationName,
                'templateName' => $templateName,
                'signUrl' => $url,
                'recipientName' => $documentSend->recipient_name,
            ]),
            function ($message) use ($email, $organizationName, $templateName) {
                $message->to($email)
                    ->subject("Lembrete: {$organizationName} — documento pendente: {$templateName}");
            }
        );
        $documentSend->update(['reminded_at' => now()]);
        return true;
    }

    /** Envia o link por WhatsApp usando a integração Evolution Go da organização. */
    public function sendByWhatsApp(
        FormTemplate $template,
        string $recipientPhone,
        ?\DateTimeInterface $expiresAt = null,
        ?int $personId = null,
        ?string $recipientName = null
    ): ?DocumentSend
    {
        if (! $this->evolutionGoClient->isConfigured()) {
            return null;
        }
        $orgId = $template->organization_id ?? $template->clinic_id;
        $organization = $orgId ? Organization::query()->find($orgId) : null;
        if (! $organization || ! $organization->evolution_go_instance_token) {
            return null;
        }
        $number = $this->normalizeWhatsappRecipient($recipientPhone);
        if (! $number) {
            return null;
        }
        if (! $template->public_token || ! $template->public_enabled) {
            $this->publicLinkService->generateToken($template);
            $template->refresh();
        }
        $url = $this->publicLinkService->getPublicUrl($template);
        $expiresAt = $expiresAt ?? $template->public_token_expires_at?->toDateTimeImmutable();
        $send = DocumentSend::create([
            'organization_id' => $template->organization_id ?? $template->clinic_id,
            'person_id' => $personId,
            'form_template_id' => $template->id,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'channel' => 'whatsapp',
            'sent_at' => now(),
            'expires_at' => $expiresAt,
            'public_token' => $template->public_token,
        ]);
        $payload = [
            'message' => "Documento para assinatura: {$template->name}. Acesse: {$url}",
        ];
        try {
            $this->evolutionGoClient->sendText(
                (string) $organization->evolution_go_instance_token,
                $number,
                (string) $payload['message']
            );
        } catch (\Throwable $e) {
            report($e);
        }
        return $send;
    }

    private function normalizeWhatsappRecipient(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }
        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            return '55'.$digits;
        }

        return null;
    }
}
