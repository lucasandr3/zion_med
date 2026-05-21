<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class OrganizationBillingCancellationService
{
    public function __construct(
        private AsaasService $asaasService,
    ) {}

    /**
     * Cancela assinaturas ativas no Asaas e marca como CANCELED localmente.
     */
    public function cancelActiveGatewaySubscriptions(Organization $organization): void
    {
        $subs = $organization->subscriptions()
            ->whereNotNull('asaas_subscription_id')
            ->whereIn('status', ['active', 'ACTIVE'])
            ->get();

        $canceledPlanKeys = [];

        foreach ($subs as $sub) {
            try {
                if ($this->asaasService->isConfigured() && $sub->asaas_subscription_id) {
                    $this->asaasService->cancelSubscription($sub->asaas_subscription_id);
                }
            } catch (\Throwable $e) {
                Log::warning('Asaas cancelSubscription failed', [
                    'subscription_id' => $sub->id,
                    'organization_id' => $organization->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $sub->deleteUnpaidLocalPayments();
            $sub->update(['status' => 'CANCELED']);
            $canceledPlanKeys[] = $sub->plan_key;
        }

        if ($subs->isEmpty()) {
            return;
        }

        $organization->refresh();
        $updates = [];

        if (! $organization->hasActiveGatewaySubscription()) {
            $updates['plan_key'] = null;
            $updates['subscription_status'] = 'canceled';
            $updates['billing_status'] = 'canceled';
        } elseif (in_array($organization->plan_key, array_filter($canceledPlanKeys), true)) {
            $updates['plan_key'] = null;
            $updates['subscription_status'] = 'canceled';
            $updates['billing_status'] = 'canceled';
        }

        if ($updates !== []) {
            $organization->update($updates);
        }
    }
}
