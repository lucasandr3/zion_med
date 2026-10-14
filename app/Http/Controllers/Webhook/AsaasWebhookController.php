<?php

namespace App\Http\Controllers\Webhook;

use App\Services\ProductEventService;
use App\Http\Controllers\Controller;
use App\Models\AsaasWebhookEvent;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\AsaasService;
use App\Services\Billing\BillingStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function __construct(
        private BillingStateService $billingState,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = config('asaas.webhook_secret');
        if ($secret === '' || $secret === null) {
            Log::error('Asaas webhook: ASAAS_WEBHOOK_SECRET not configured');

            return response()->json(['received' => false, 'message' => 'Webhook not configured'], 503);
        }

        $provided = (string) $request->header('asaas-access-token', '');
        if ($provided === '' || ! hash_equals((string) $secret, $provided)) {
            Log::warning('Asaas webhook: invalid or missing token');

            return response()->json(['received' => false], 401);
        }

        $event = (string) $request->input('event', '');
        $payload = $request->all();
        $eventKey = $this->resolveEventKey($payload, $event);

        try {
            $existing = AsaasWebhookEvent::query()->where('event_key', $eventKey)->first();
            if ($existing && $existing->status === 'processed') {
                return response()->json(['received' => true, 'duplicate' => true], 200);
            }

            DB::transaction(function () use ($event, $payload, $eventKey, $existing): void {
                $row = $existing ?? new AsaasWebhookEvent(['event_key' => $eventKey]);
                $row->fill([
                    'event_type' => $event !== '' ? $event : null,
                    'asaas_resource_id' => $this->resourceIdFromPayload($payload),
                    'payload' => $payload,
                    'status' => 'processed',
                    'error_message' => null,
                    'processed_at' => now(),
                ]);

                if (str_starts_with($event, 'PAYMENT_')) {
                    $row->organization_id = $this->handlePaymentEvent($event, $payload);
                } elseif (str_starts_with($event, 'SUBSCRIPTION_')) {
                    $row->organization_id = $this->handleSubscriptionEvent($event, $payload);
                } else {
                    $row->status = 'ignored';
                }

                $row->save();
            });
        } catch (\Throwable $e) {
            Log::error('Asaas webhook processing failed', [
                'event' => $event,
                'event_key' => $eventKey,
                'error' => $e->getMessage(),
            ]);

            AsaasWebhookEvent::query()->updateOrCreate(
                ['event_key' => $eventKey],
                [
                    'event_type' => $event !== '' ? $event : null,
                    'asaas_resource_id' => $this->resourceIdFromPayload($payload),
                    'payload' => $payload,
                    'status' => 'failed',
                    'error_message' => mb_substr($e->getMessage(), 0, 1000),
                    'processed_at' => now(),
                ]
            );

            return response()->json(['received' => false, 'message' => 'Processing failed'], 500);
        }

        return response()->json(['received' => true], 200);
    }

    private function resolveEventKey(array $payload, string $event): string
    {
        $id = $payload['id'] ?? null;
        if (is_string($id) && $id !== '') {
            return $id;
        }

        $resource = $this->resourceIdFromPayload($payload) ?? 'none';
        $status = '';
        if (isset($payload['payment']['status'])) {
            $status = (string) $payload['payment']['status'];
        } elseif (isset($payload['subscription']['status'])) {
            $status = (string) $payload['subscription']['status'];
        }
        $date = (string) ($payload['dateCreated'] ?? '');

        return hash('sha256', implode('|', [$event, $resource, $status, $date]));
    }

    private function resourceIdFromPayload(array $payload): ?string
    {
        $id = $payload['payment']['id'] ?? $payload['subscription']['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function handlePaymentEvent(string $event, array $payload): ?int
    {
        $paymentData = $payload['payment'] ?? null;
        if (! $paymentData || ! is_array($paymentData)) {
            return null;
        }

        $customerId = $paymentData['customer'] ?? null;
        $organization = $customerId ? Organization::where('asaas_customer_id', $customerId)->first() : null;
        if (! $organization) {
            return null;
        }

        $asaasPaymentId = $paymentData['id'] ?? null;
        $payment = $asaasPaymentId ? Payment::where('asaas_payment_id', $asaasPaymentId)->first() : null;
        if (! $payment) {
            $subscriptionId = $paymentData['subscription'] ?? null;
            $sub = $subscriptionId ? Subscription::where('asaas_subscription_id', $subscriptionId)->first() : null;
            $payment = Payment::create([
                'organization_id' => $organization->id,
                'subscription_id' => $sub?->id,
                'asaas_payment_id' => $asaasPaymentId,
                'status' => $paymentData['status'] ?? 'PENDING',
                'due_date' => isset($paymentData['dueDate']) ? $paymentData['dueDate'] : null,
                'paid_at' => isset($paymentData['paymentDate']) ? $paymentData['paymentDate'] : null,
                'value' => $paymentData['value'] ?? null,
                'bank_slip_url' => $paymentData['bankSlipUrl'] ?? $paymentData['invoiceUrl'] ?? null,
            ]);
            if ($sub && strtoupper((string) ($sub->billing_type ?? '')) === 'PIX') {
                app(AsaasService::class)->syncPixQrCodeForPayment($payment);
            }
        } else {
            $payment->update([
                'status' => $paymentData['status'] ?? $payment->status,
                'due_date' => isset($paymentData['dueDate']) ? $paymentData['dueDate'] : $payment->due_date,
                'paid_at' => isset($paymentData['paymentDate']) ? $paymentData['paymentDate'] : $payment->paid_at,
                'bank_slip_url' => $paymentData['bankSlipUrl'] ?? $paymentData['invoiceUrl'] ?? $payment->bank_slip_url,
            ]);
        }

        $status = strtoupper((string) ($paymentData['status'] ?? ''));

        if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true)) {
            $organization->update([
                'subscription_status' => 'active',
                'billing_status' => 'ok',
                'grace_ends_at' => null,
            ]);
            app(ProductEventService::class)->record(
                'payment_confirmed',
                (int) $organization->id,
                null,
                ['asaas_payment_id' => $asaasPaymentId, 'status' => $status],
                'payment_confirmed:'.$asaasPaymentId,
                ['channel' => 'asaas_webhook']
            );
        } elseif ($status === 'OVERDUE') {
            $organization->update([
                'subscription_status' => 'past_due',
                'billing_status' => 'attention',
            ]);
            $organization->refresh();
            if (! $organization->grace_ends_at) {
                $organization->update([
                    'grace_ends_at' => now()->addDays((int) config('asaas.grace_days', 7)),
                ]);
            }
        } elseif ($this->isReversalStatus($status) || str_contains($event, 'REFUND') || str_contains($event, 'CHARGEBACK')) {
            $this->billingState->reevaluateAccessAfterPaymentChange($organization->fresh());
        }

        return (int) $organization->id;
    }

    private function handleSubscriptionEvent(string $event, array $payload): ?int
    {
        $subData = $payload['subscription'] ?? null;
        if (! $subData || ! is_array($subData)) {
            return null;
        }

        $asaasSubId = $subData['id'] ?? null;
        $subscription = $asaasSubId ? Subscription::where('asaas_subscription_id', $asaasSubId)->first() : null;
        if ($subscription) {
            $subscription->update([
                'status' => $this->mapSubscriptionStatus($subData['status'] ?? ''),
                'current_period_end' => $subData['currentPeriodEnd'] ?? null,
                'next_due_date' => $subData['nextDueDate'] ?? null,
            ]);
            if (strtoupper((string) ($subData['status'] ?? '')) === 'CANCELED') {
                $subscription->deleteUnpaidLocalPayments();
            }
        }

        $customerId = $subData['customer'] ?? null;
        $organization = $customerId ? Organization::where('asaas_customer_id', $customerId)->first() : null;
        if (! $organization) {
            return null;
        }

        $status = strtoupper((string) ($subData['status'] ?? ''));
        if ($status === 'ACTIVE') {
            if ($this->billingState->hasConfirmedBillingPayment($organization)) {
                $organization->update([
                    'subscription_status' => 'active',
                    'billing_status' => 'ok',
                    'grace_ends_at' => null,
                ]);
            }
        }
        if ($status === 'CANCELED' || $status === 'INACTIVE') {
            $hasOtherActive = $organization->subscriptions()
                ->where('id', '!=', $subscription?->id ?? 0)
                ->whereIn('status', ['active', 'ACTIVE'])
                ->exists();
            if (! $hasOtherActive) {
                $organization->update([
                    'subscription_status' => 'inactive',
                    'billing_status' => 'blocked',
                ]);
            }
        }

        return (int) $organization->id;
    }

    private function isReversalStatus(string $status): bool
    {
        return in_array($status, [
            'REFUNDED',
            'REFUND_REQUESTED',
            'CHARGEBACK_REQUESTED',
            'CHARGEBACK_DISPUTE',
            'AWAITING_CHARGEBACK_REVERSAL',
            'DUNNING_REQUESTED',
        ], true);
    }

    private function mapSubscriptionStatus(string $status): string
    {
        $map = ['ACTIVE' => 'active', 'INACTIVE' => 'inactive', 'CANCELED' => 'canceled', 'EXPIRED' => 'inactive'];

        return $map[strtoupper($status)] ?? strtolower($status);
    }
}
