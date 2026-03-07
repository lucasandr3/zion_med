<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = config('asaas.webhook_secret');
        if ($secret !== '' && $request->header('asaas-access-token') !== $secret) {
            Log::warning('Asaas webhook: invalid or missing token');
            return response()->json(['received' => false], 401);
        }

        $event = $request->input('event');
        $payload = $request->all();

        try {
            if (str_starts_with($event ?? '', 'PAYMENT_')) {
                $this->handlePaymentEvent($event, $payload);
            }
            if (str_starts_with($event ?? '', 'SUBSCRIPTION_')) {
                $this->handleSubscriptionEvent($event, $payload);
            }
        } catch (\Throwable $e) {
            Log::error('Asaas webhook processing failed', ['event' => $event, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['received' => true], 200);
        }

        return response()->json(['received' => true], 200);
    }

    private function handlePaymentEvent(string $event, array $payload): void
    {
        $paymentData = $payload['payment'] ?? null;
        if (! $paymentData || ! is_array($paymentData)) {
            return;
        }

        $customerId = $paymentData['customer'] ?? null;
        $clinic = $customerId ? Clinic::where('asaas_customer_id', $customerId)->first() : null;
        if (! $clinic) {
            return;
        }

        $asaasPaymentId = $paymentData['id'] ?? null;
        $payment = $asaasPaymentId ? Payment::where('asaas_payment_id', $asaasPaymentId)->first() : null;
        if (! $payment) {
            $subscriptionId = $paymentData['subscription'] ?? null;
            $sub = $subscriptionId ? Subscription::where('asaas_subscription_id', $subscriptionId)->first() : null;
            $payment = Payment::create([
                'organization_id' => $clinic->id,
                'subscription_id' => $sub?->id,
                'asaas_payment_id' => $asaasPaymentId,
                'status' => $paymentData['status'] ?? 'PENDING',
                'due_date' => isset($paymentData['dueDate']) ? $paymentData['dueDate'] : null,
                'paid_at' => isset($paymentData['paymentDate']) ? $paymentData['paymentDate'] : null,
                'value' => $paymentData['value'] ?? null,
                'bank_slip_url' => $paymentData['bankSlipUrl'] ?? $paymentData['invoiceUrl'] ?? null,
            ]);
        } else {
            $payment->update([
                'status' => $paymentData['status'] ?? $payment->status,
                'due_date' => isset($paymentData['dueDate']) ? $paymentData['dueDate'] : $payment->due_date,
                'paid_at' => isset($paymentData['paymentDate']) ? $paymentData['paymentDate'] : $payment->paid_at,
                'bank_slip_url' => $paymentData['bankSlipUrl'] ?? $paymentData['invoiceUrl'] ?? $payment->bank_slip_url,
            ]);
        }

        $status = strtoupper($paymentData['status'] ?? '');

        if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true)) {
            $clinic->update([
                'subscription_status' => 'active',
                'billing_status' => 'ok',
                'grace_ends_at' => null,
            ]);
        }

        if ($status === 'OVERDUE') {
            $clinic->update([
                'subscription_status' => 'past_due',
                'billing_status' => 'attention',
            ]);
            if (! $clinic->grace_ends_at) {
                $clinic->update([
                    'grace_ends_at' => now()->addDays(config('asaas.grace_days', 7)),
                ]);
            }
        }
    }

    private function handleSubscriptionEvent(string $event, array $payload): void
    {
        $subData = $payload['subscription'] ?? null;
        if (! $subData || ! is_array($subData)) {
            return;
        }

        $asaasSubId = $subData['id'] ?? null;
        $subscription = $asaasSubId ? Subscription::where('asaas_subscription_id', $asaasSubId)->first() : null;
        if ($subscription) {
            $subscription->update([
                'status' => $this->mapSubscriptionStatus($subData['status'] ?? ''),
                'current_period_end' => $subData['currentPeriodEnd'] ?? null,
                'next_due_date' => $subData['nextDueDate'] ?? null,
            ]);
        }

        $customerId = $subData['customer'] ?? null;
        $clinic = $customerId ? Clinic::where('asaas_customer_id', $customerId)->first() : null;
        if (! $clinic) {
            return;
        }

        $status = strtoupper($subData['status'] ?? '');
        if ($status === 'ACTIVE') {
            $clinic->update([
                'subscription_status' => 'active',
                'billing_status' => 'ok',
                'grace_ends_at' => null,
            ]);
        }
        if ($status === 'CANCELED' || $status === 'INACTIVE') {
            $hasOtherActive = $clinic->subscriptions()->where('id', '!=', $subscription?->id ?? 0)->where('status', 'active')->exists();
            if (! $hasOtherActive) {
                $clinic->update(['subscription_status' => 'inactive', 'billing_status' => 'blocked']);
            }
        }
    }

    private function mapSubscriptionStatus(string $status): string
    {
        $map = ['ACTIVE' => 'active', 'INACTIVE' => 'inactive', 'CANCELED' => 'canceled', 'EXPIRED' => 'inactive'];
        return $map[strtoupper($status)] ?? strtolower($status);
    }
}
