<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;

class BillingOverviewController extends Controller
{
    public function subscriptions(): JsonResponse
    {
        $subscriptions = Subscription::with(['clinic:id,name,tenant_id,plan_key,subscription_status,billing_status'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $subscriptions->map(fn (Subscription $s) => [
                'id' => $s->id,
                'organization_id' => $s->organization_id,
                'asaas_subscription_id' => $s->asaas_subscription_id,
                'plan_key' => $s->plan_key,
                'status' => $s->status,
                'current_period_end' => $s->current_period_end?->toIso8601String(),
                'next_due_date' => $s->next_due_date?->toIso8601String(),
                'created_at' => $s->created_at?->toIso8601String(),
                'clinic' => $s->clinic ? [
                    'id' => $s->clinic->id,
                    'name' => $s->clinic->name,
                    'tenant_id' => $s->clinic->tenant_id,
                    'plan_key' => $s->clinic->plan_key,
                    'subscription_status' => $s->clinic->subscription_status,
                    'billing_status' => $s->clinic->billing_status,
                ] : null,
            ]),
        ]);
    }

    public function payments(): JsonResponse
    {
        $payments = Payment::with(['clinic:id,name,tenant_id', 'subscription:id,plan_key,status'])
            ->orderByDesc('due_date')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $payments->map(fn (Payment $p) => [
                'id' => $p->id,
                'organization_id' => $p->organization_id,
                'subscription_id' => $p->subscription_id,
                'asaas_payment_id' => $p->asaas_payment_id,
                'status' => $p->status,
                'due_date' => $p->due_date?->toIso8601String(),
                'paid_at' => $p->paid_at?->toIso8601String(),
                'value' => $p->value,
                'bank_slip_url' => $p->bank_slip_url,
                'created_at' => $p->created_at?->toIso8601String(),
                'clinic' => $p->clinic ? [
                    'id' => $p->clinic->id,
                    'name' => $p->clinic->name,
                    'tenant_id' => $p->clinic->tenant_id,
                ] : null,
                'subscription' => $p->subscription ? [
                    'id' => $p->subscription->id,
                    'plan_key' => $p->subscription->plan_key,
                    'status' => $p->subscription->status,
                ] : null,
            ]),
        ]);
    }
}
