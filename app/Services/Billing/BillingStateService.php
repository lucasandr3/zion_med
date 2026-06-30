<?php

namespace App\Services\Billing;

use App\Models\Organization;

class BillingStateService
{
    public function isTrialWindowOpen(Organization $organization): bool
    {
        if (! $organization->trial_ends_at) {
            return false;
        }

        return now()->lte($organization->trial_ends_at->copy()->endOfDay());
    }

    public function hasConfirmedBillingPayment(Organization $organization): bool
    {
        return $organization->payments()
            ->whereIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
            ->exists();
    }

    public function hasActiveGatewaySubscription(Organization $organization): bool
    {
        return $organization->subscriptions()
            ->whereNotNull('asaas_subscription_id')
            ->whereIn('status', ['active', 'ACTIVE'])
            ->exists();
    }

    public function isAwaitingFirstBillingPayment(Organization $organization): bool
    {
        if ($this->hasConfirmedBillingPayment($organization)) {
            return false;
        }
        if (! $this->hasActiveGatewaySubscription($organization)) {
            return false;
        }
        if ($this->isTrialWindowOpen($organization)) {
            return false;
        }

        return true;
    }

    public function billingUiShowsManagedActiveSubscription(Organization $organization): bool
    {
        if ($this->isAwaitingFirstBillingPayment($organization)) {
            return false;
        }

        return $this->hasActiveGatewaySubscription($organization);
    }

    public function billingUiShowsPlanSelection(Organization $organization): bool
    {
        if ($this->billingUiShowsManagedActiveSubscription($organization)) {
            return false;
        }

        return ! $this->hasActiveGatewaySubscription($organization)
            || $this->isAwaitingFirstBillingPayment($organization);
    }

    /**
     * @return array{
     *     show_managed_subscription_card: bool,
     *     show_pending_first_payment: bool,
     *     show_plan_selection: bool,
     *     pending_first_payment_message: string|null
     * }
     */
    public function billingUiState(Organization $organization): array
    {
        $pending = $this->isAwaitingFirstBillingPayment($organization);

        return [
            'show_managed_subscription_card' => $this->billingUiShowsManagedActiveSubscription($organization),
            'show_pending_first_payment' => $pending,
            'show_plan_selection' => $this->billingUiShowsPlanSelection($organization),
            'pending_first_payment_message' => $pending
                ? 'Seu trial encerrou e a assinatura ainda não teve pagamento confirmado. Pague via PIX ou boleto abaixo, ou assine novamente para gerar uma nova cobrança.'
                : null,
        ];
    }

    public function trialCalendarDaysRemaining(Organization $organization): int
    {
        if (! $organization->trial_ends_at) {
            return 0;
        }
        $today = now()->copy()->startOfDay();
        $lastTrialDay = $organization->trial_ends_at->copy()->startOfDay();

        return max(0, (int) $today->diffInDays($lastTrialDay, false));
    }

    /**
     * @return array{visible: true, days_remaining: int, trial_ends_at: string, message: string}|null
     */
    public function trialEndingNoticeMeta(Organization $organization): ?array
    {
        if (! $organization->trial_ends_at || ! $this->isTrialWindowOpen($organization)) {
            return null;
        }
        if ($this->hasConfirmedBillingPayment($organization)) {
            return null;
        }
        $threshold = (int) config('asaas.trial_warning_days', 3);
        $daysLeft = $this->trialCalendarDaysRemaining($organization);
        if ($daysLeft > $threshold) {
            return null;
        }

        $message = $daysLeft === 0
            ? 'Seu período de trial encerra hoje. Acesse Assinatura para evitar a suspensão do acesso.'
            : sprintf(
                'Seu trial encerra em %d %s. Acesse Assinatura para evitar a suspensão do acesso.',
                $daysLeft,
                $daysLeft === 1 ? 'dia' : 'dias'
            );

        return [
            'visible' => true,
            'days_remaining' => $daysLeft,
            'trial_ends_at' => $organization->trial_ends_at->toIso8601String(),
            'message' => $message,
        ];
    }

    public function isOnTrial(Organization $organization): bool
    {
        return $organization->subscription_status === 'trial'
            && $organization->trial_ends_at !== null
            && $this->isTrialWindowOpen($organization);
    }

    public function canAccessTenantAppFeatures(Organization $organization): bool
    {
        if ($this->isBillingBlocked($organization)) {
            return false;
        }

        if ($this->isAwaitingFirstBillingPayment($organization)) {
            return false;
        }

        if ($organization->subscription_status === 'past_due') {
            if ($organization->grace_ends_at && now()->lte($organization->grace_ends_at)) {
                return true;
            }
            if ($organization->grace_ends_at && now()->gt($organization->grace_ends_at) && $organization->billing_status !== 'blocked') {
                $organization->forceFill(['billing_status' => 'blocked'])->save();
            }

            return false;
        }

        if ($organization->trial_ends_at !== null) {
            if ($this->isTrialWindowOpen($organization)) {
                return true;
            }

            return $this->hasConfirmedBillingPayment($organization);
        }

        return $organization->subscription_status === 'active'
            && in_array((string) $organization->billing_status, ['ok', 'attention'], true);
    }

    public function isPastDueInGrace(Organization $organization): bool
    {
        if ($organization->subscription_status !== 'past_due' || ! $organization->grace_ends_at) {
            return false;
        }

        return now()->lte($organization->grace_ends_at);
    }

    public function isBillingBlocked(Organization $organization): bool
    {
        return $organization->billing_status === 'blocked';
    }

    /**
     * @return array<string, mixed>
     */
    public function planDefinition(Organization $organization): array
    {
        $key = $organization->plan_key;
        $plans = config('asaas.plans', []);
        if ($key !== null && $key !== '' && isset($plans[$key]) && is_array($plans[$key])) {
            return $plans[$key];
        }

        return [];
    }

    public function planMaxUsers(Organization $organization): ?int
    {
        $v = $this->planDefinition($organization)['max_users'] ?? null;

        return $v === null ? null : (int) $v;
    }

    public function planMaxOrganizationsPerTenant(Organization $organization): ?int
    {
        $v = $this->planDefinition($organization)['max_organizations_per_tenant'] ?? null;

        return $v === null ? null : (int) $v;
    }

    public function organizationsInTenantCount(Organization $organization): int
    {
        if (! $organization->tenant_id) {
            return 1;
        }

        return (int) Organization::withoutGlobalScopes()->where('tenant_id', $organization->tenant_id)->count();
    }

    public function canAddAnotherUser(Organization $organization): bool
    {
        $max = $this->planMaxUsers($organization);
        if ($max === null) {
            return true;
        }

        return $organization->users()->count() < $max;
    }

    public function canAddOrganizationInTenant(Organization $organization): bool
    {
        $max = $this->planMaxOrganizationsPerTenant($organization);
        if ($max === null) {
            return true;
        }

        return $this->organizationsInTenantCount($organization) < $max;
    }

    /**
     * @param  array<string, mixed>  $planConfig
     */
    public function meetsLimitsForPlanConfig(Organization $organization, array $planConfig): bool
    {
        $maxUsers = $planConfig['max_users'] ?? null;
        if ($maxUsers !== null && $organization->users()->count() > (int) $maxUsers) {
            return false;
        }
        $maxOrgs = $planConfig['max_organizations_per_tenant'] ?? null;
        if ($maxOrgs !== null && $this->organizationsInTenantCount($organization) > (int) $maxOrgs) {
            return false;
        }

        return true;
    }

    /**
     * @return array{
     *     plan_key: string|null,
     *     max_users: int|null,
     *     max_organizations_per_tenant: int|null,
     *     users_count: int,
     *     organizations_in_tenant: int,
     *     can_add_user: bool,
     *     can_add_organization_in_tenant: bool
     * }
     */
    public function planLimitsForApi(Organization $organization): array
    {
        $def = $this->planDefinition($organization);
        $maxUsers = $def['max_users'] ?? null;
        $maxOrgs = $def['max_organizations_per_tenant'] ?? null;
        $linkBioEnabled = $def['link_bio_enabled'] ?? true;

        return [
            'plan_key' => $organization->plan_key,
            'max_users' => $maxUsers !== null ? (int) $maxUsers : null,
            'max_organizations_per_tenant' => $maxOrgs !== null ? (int) $maxOrgs : null,
            'link_bio_enabled' => (bool) $linkBioEnabled,
            'users_count' => $organization->users()->count(),
            'organizations_in_tenant' => $this->organizationsInTenantCount($organization),
            'can_add_user' => $this->canAddAnotherUser($organization),
            'can_add_organization_in_tenant' => $this->canAddOrganizationInTenant($organization),
        ];
    }

    public function syncExpiredTrialStateIfNeeded(Organization $organization): void
    {
        if (! $organization->trial_ends_at) {
            return;
        }

        if ($this->isTrialWindowOpen($organization)) {
            return;
        }

        if ($this->hasConfirmedBillingPayment($organization)) {
            if (in_array($organization->subscription_status, ['trial', 'inactive'], true)) {
                $organization->forceFill([
                    'subscription_status' => 'active',
                    'billing_status' => 'ok',
                ])->save();
            }

            return;
        }

        $organization->forceFill([
            'subscription_status' => 'inactive',
            'billing_status' => 'blocked',
        ])->save();
    }
}
