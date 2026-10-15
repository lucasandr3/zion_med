<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\ProductEvent;
use App\Models\User;
use App\Support\FrontendUrl;
use Illuminate\Support\Collection;

/**
 * Recuperação automática de leads: trial acabando, checkout abandonado e onboarding parado.
 * Idempotente via product_events (recovery_email_sent).
 */
class FunnelRecoveryService
{
    public function __construct(
        private TransactionalEmailService $mail,
        private ProductEventService $events,
    ) {}

    /**
     * @return array{trial_ending: int, checkout_abandoned: int, onboarding_stalled: int, skipped: int}
     */
    public function run(bool $dryRun = false): array
    {
        $stats = [
            'trial_ending' => 0,
            'checkout_abandoned' => 0,
            'onboarding_stalled' => 0,
            'skipped' => 0,
        ];

        $stats['trial_ending'] = $this->recoverTrialEnding($dryRun, $stats);
        $stats['checkout_abandoned'] = $this->recoverCheckoutAbandoned($dryRun, $stats);
        $stats['onboarding_stalled'] = $this->recoverOnboardingStalled($dryRun, $stats);

        return $stats;
    }

    /**
     * @param  array{skipped: int}  $stats
     */
    private function recoverTrialEnding(bool $dryRun, array &$stats): int
    {
        $warningDays = max(1, (int) config('asaas.trial_warning_days', 3));
        $sent = 0;

        Organization::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', now()->startOfDay())
            ->where('trial_ends_at', '<=', now()->addDays($warningDays)->endOfDay())
            ->whereIn('subscription_status', ['trial', 'inactive'])
            ->orderBy('id')
            ->chunkById(80, function ($organizations) use ($dryRun, &$sent, &$stats) {
                foreach ($organizations as $organization) {
                    /** @var Organization $organization */
                    if ($organization->hasConfirmedBillingPayment()) {
                        $stats['skipped']++;

                        continue;
                    }

                    $kind = 'trial_ending';
                    $key = 'recovery:'.$kind.':org:'.$organization->id.':'.$organization->trial_ends_at->format('Y-m-d');
                    if ($this->alreadySent($key)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $ends = $organization->trial_ends_at->copy()->startOfDay();
                    $daysLeft = (int) max(0, now()->startOfDay()->diffInDays($ends));
                    $owners = $this->ownersOf($organization);
                    if ($owners->isEmpty()) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($dryRun) {
                        $sent++;

                        continue;
                    }

                    $billingUrl = FrontendUrl::billing();
                    $body = '<p>Olá!</p>'
                        .'<p>O período de teste da <strong>'.e($organization->name).'</strong> '
                        .($daysLeft === 0
                            ? 'termina <strong>hoje</strong>.'
                            : 'termina em <strong>'.$daysLeft.' dia'.($daysLeft === 1 ? '' : 's').'</strong>.')
                        .'</p>'
                        .'<p>Para continuar com fichas, assinaturas e histórico sem interrupção, escolha um plano (mensal ou anual) na área de Assinatura.</p>';

                    foreach ($owners as $owner) {
                        $this->mail->send(
                            (string) $owner->email,
                            $daysLeft === 0
                                ? 'Seu teste termina hoje'
                                : 'Seu teste termina em '.$daysLeft.' dia'.($daysLeft === 1 ? '' : 's'),
                            'emails.transactional',
                            [
                                'body' => $body,
                                'actionLink' => $billingUrl,
                                'actionText' => 'Ver planos e assinar',
                                'emailTitle' => 'Lembrete de trial',
                            ]
                        );
                    }

                    $this->markSent($organization, $kind, $key, [
                        'days_left' => $daysLeft,
                        'recipients' => $owners->count(),
                    ]);
                    $sent++;
                }
            });

        return $sent;
    }

    /**
     * @param  array{skipped: int}  $stats
     */
    private function recoverCheckoutAbandoned(bool $dryRun, array &$stats): int
    {
        $sent = 0;
        $from = now()->subDays(7);
        $waitUntil = now()->subHours(18);

        $starts = ProductEvent::query()
            ->where('event_name', 'checkout_started')
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $waitUntil)
            ->whereNotNull('organization_id')
            ->orderByDesc('occurred_at')
            ->get();

        $seenOrgs = [];
        foreach ($starts as $event) {
            $orgId = (int) $event->organization_id;
            if (isset($seenOrgs[$orgId])) {
                continue;
            }
            $seenOrgs[$orgId] = true;

            if ($this->orgHasEventAfter($orgId, ['payment_confirmed'], $event->occurred_at)) {
                $stats['skipped']++;

                continue;
            }

            $organization = Organization::query()->find($orgId);
            if (! $organization || $organization->hasConfirmedBillingPayment()) {
                $stats['skipped']++;

                continue;
            }

            $kind = 'checkout_abandoned';
            $key = 'recovery:'.$kind.':org:'.$orgId.':'.$event->occurred_at->format('Ymd');
            if ($this->alreadySent($key)) {
                $stats['skipped']++;

                continue;
            }

            $owners = $this->ownersOf($organization);
            if ($owners->isEmpty()) {
                $stats['skipped']++;

                continue;
            }

            if ($dryRun) {
                $sent++;

                continue;
            }

            $billingUrl = FrontendUrl::billing();
            $body = '<p>Olá!</p>'
                .'<p>Notamos que você iniciou a assinatura do Gestgo para <strong>'.e($organization->name).'</strong>, '
                .'mas o pagamento ainda não foi concluído.</p>'
                .'<p>Você pode retomar quando quiser — PIX ou boleto — e manter o acesso às fichas digitais.</p>';

            foreach ($owners as $owner) {
                $this->mail->send(
                    (string) $owner->email,
                    'Finalize sua assinatura quando estiver pronto',
                    'emails.transactional',
                    [
                        'body' => $body,
                        'actionLink' => $billingUrl,
                        'actionText' => 'Continuar assinatura',
                        'emailTitle' => 'Checkout pendente',
                    ]
                );
            }

            $this->markSent($organization, $kind, $key, [
                'checkout_event_id' => $event->id,
                'recipients' => $owners->count(),
            ]);
            $sent++;
        }

        return $sent;
    }

    /**
     * @param  array{skipped: int}  $stats
     */
    private function recoverOnboardingStalled(bool $dryRun, array &$stats): int
    {
        $sent = 0;
        $from = now()->subDays(14);
        $waitUntil = now()->subDays(2);

        $signups = ProductEvent::query()
            ->whereIn('event_name', ['signup_completed', 'trial_started'])
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $waitUntil)
            ->whereNotNull('organization_id')
            ->orderByDesc('occurred_at')
            ->get();

        $seenOrgs = [];
        foreach ($signups as $event) {
            $orgId = (int) $event->organization_id;
            if (isset($seenOrgs[$orgId])) {
                continue;
            }
            $seenOrgs[$orgId] = true;

            if ($this->orgHasEventAfter($orgId, [
                'template_created',
                'template_published',
                'template_selected',
                'first_form_shared',
                'payment_confirmed',
            ], $event->occurred_at->copy()->subMinute())) {
                $stats['skipped']++;

                continue;
            }

            $organization = Organization::query()->find($orgId);
            if (! $organization) {
                $stats['skipped']++;

                continue;
            }

            $kind = 'onboarding_stalled';
            $key = 'recovery:'.$kind.':org:'.$orgId;
            if ($this->alreadySent($key)) {
                $stats['skipped']++;

                continue;
            }

            $owners = $this->ownersOf($organization);
            if ($owners->isEmpty()) {
                $stats['skipped']++;

                continue;
            }

            if ($dryRun) {
                $sent++;

                continue;
            }

            $onboardingUrl = FrontendUrl::onboarding();
            $body = '<p>Olá!</p>'
                .'<p>Sua conta <strong>'.e($organization->name).'</strong> já está pronta. '
                .'O próximo passo é escolher um modelo de ficha (ou importar a sua) e enviar o primeiro link.</p>'
                .'<p>Leva poucos minutos — e você vê o protocolo completo no painel.</p>';

            foreach ($owners as $owner) {
                $this->mail->send(
                    (string) $owner->email,
                    'Pronto para publicar sua primeira ficha?',
                    'emails.transactional',
                    [
                        'body' => $body,
                        'actionLink' => $onboardingUrl,
                        'actionText' => 'Continuar configuração',
                        'emailTitle' => 'Onboarding',
                    ]
                );
            }

            $this->markSent($organization, $kind, $key, [
                'recipients' => $owners->count(),
            ]);
            $sent++;
        }

        return $sent;
    }

    /** @return Collection<int, User> */
    private function ownersOf(Organization $organization): Collection
    {
        return User::query()
            ->where('organization_id', $organization->id)
            ->where('role', Role::Owner->value)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();
    }

    private function alreadySent(string $idempotencyKey): bool
    {
        return ProductEvent::query()->where('idempotency_key', $idempotencyKey)->exists();
    }

    /**
     * @param  list<string>  $eventNames
     */
    private function orgHasEventAfter(int $organizationId, array $eventNames, $after): bool
    {
        return ProductEvent::query()
            ->where('organization_id', $organizationId)
            ->whereIn('event_name', $eventNames)
            ->where('occurred_at', '>=', $after)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function markSent(Organization $organization, string $kind, string $idempotencyKey, array $properties = []): void
    {
        $this->events->record(
            'recovery_email_sent',
            (int) $organization->id,
            null,
            array_merge(['kind' => $kind], $properties),
            $idempotencyKey,
            ['channel' => 'recovery']
        );
    }
}
