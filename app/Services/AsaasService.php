<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('asaas.base_url'), '/');
        $this->apiKey = config('asaas.api_key') ?? '';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Normaliza tipo de cobrança aceito pelo Asaas (BOLETO ou PIX).
     */
    public function normalizeBillingType(?string $billingType): string
    {
        return strtoupper(trim((string) $billingType)) === 'PIX' ? 'PIX' : 'BOLETO';
    }

    private function request(string $method, string $path, array $data = []): Response
    {
        $url = $this->baseUrl.'/'.ltrim($path, '/');
        $http = Http::withHeaders([
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ]);

        return match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url),
            default => $http->get($url, $data),
        };
    }

    /**
     * Cria ou retorna customer no ASAAS. Se a organização já tem asaas_customer_id, atualiza e retorna.
     */
    public function ensureCustomer(Organization $organization): array
    {
        $doc = $this->normalizeCpfCnpj($organization->billing_document ?? '');
        if ($doc === '') {
            $doc = '00000000000';
        }
        $payload = [
            'name' => $organization->billing_name ?: $organization->name,
            'cpfCnpj' => $doc,
            'email' => $organization->billing_email ?: $organization->notification_email ?: $organization->contact_email ?: 'contato@gestgo.local',
            'phone' => $organization->phone,
            'externalReference' => (string) $organization->id,
        ];
        if ($organization->address) {
            $payload['address'] = $organization->address;
        }

        if ($organization->asaas_customer_id) {
            $resp = $this->request('POST', '/customers/'.$organization->asaas_customer_id, $payload);
            if ($resp->successful()) {
                return $resp->json();
            }
        }

        $resp = $this->request('POST', '/customers', $payload);
        $resp->throw();
        $data = $resp->json();
        $organization->update(['asaas_customer_id' => $data['id'] ?? null]);

        return $data;
    }

    /**
     * Data da primeira cobrança no Asaas: fim do trial + dias de carência (grace),
     * ou hoje se já não estiver em trial ou se a data calculada passou.
     */
    public function firstChargeDueDateForOrganization(Organization $organization): string
    {
        $graceDays = (int) config('asaas.grace_days', 7);
        if ($organization->trial_ends_at && $organization->isTrialWindowOpen()) {
            $due = $organization->trial_ends_at->copy()->addDays($graceDays)->startOfDay();
            $today = now()->startOfDay();
            if ($due->lt($today)) {
                $due = $today;
            }

            return $due->format('Y-m-d');
        }

        return now()->format('Y-m-d');
    }

    /**
     * Cria assinatura no ASAAS (cycle MONTHLY, primeira cobrança em nextDueDate).
     */
    public function createSubscription(Organization $organization, string $planKey, float $value, string $billingType = 'BOLETO', ?string $nextDueDate = null): array
    {
        $customer = $this->ensureCustomer($organization);
        $customerId = $customer['id'] ?? $organization->asaas_customer_id;
        if (! $customerId) {
            throw new \InvalidArgumentException('Cliente ASAAS não encontrado.');
        }

        $productName = config('asaas.product_name', 'Gestgo');
        $nextDue = $nextDueDate ?? now()->format('Y-m-d');
        $billingType = $this->normalizeBillingType($billingType);

        $payload = [
            'customer' => $customerId,
            'billingType' => $billingType,
            'nextDueDate' => $nextDue,
            'value' => $value,
            'cycle' => 'MONTHLY',
            'description' => "{$productName} - Plano {$planKey}",
        ];

        $resp = $this->request('POST', '/subscriptions', $payload);
        $resp->throw();

        return $resp->json();
    }

    /**
     * QR Code e copia-e-cola PIX de uma cobrança no Asaas.
     *
     * @return array{encodedImage?: string, payload?: string, expirationDate?: string}|null
     */
    public function getPaymentPixQrCode(string $asaasPaymentId): ?array
    {
        $resp = $this->request('GET', '/payments/'.$asaasPaymentId.'/pixQrCode');
        if (! $resp->successful()) {
            return null;
        }

        return $resp->json();
    }

    /**
     * Cria ou atualiza pagamento local a partir de item da API Asaas e sincroniza PIX quando aplicável.
     */
    public function upsertPaymentFromAsaasItem(
        Organization $organization,
        Subscription $subscription,
        array $item,
        ?string $billingType = null,
    ): Payment {
        $asaasPaymentId = $item['id'] ?? null;
        if (! $asaasPaymentId) {
            throw new \InvalidArgumentException('Cobrança ASAAS sem ID.');
        }

        $resolvedBillingType = $this->normalizeBillingType($item['billingType'] ?? $billingType ?? $subscription->billing_type ?? 'BOLETO');

        $payment = Payment::updateOrCreate(
            ['asaas_payment_id' => $asaasPaymentId],
            [
                'organization_id' => $organization->id,
                'subscription_id' => $subscription->id,
                'status' => $item['status'] ?? 'PENDING',
                'due_date' => $item['dueDate'] ?? null,
                'paid_at' => $item['paymentDate'] ?? null,
                'value' => $item['value'] ?? null,
                'bank_slip_url' => $item['bankSlipUrl'] ?? $item['invoiceUrl'] ?? null,
            ]
        );

        if ($resolvedBillingType === 'PIX' && ! $payment->isPaid()) {
            $this->syncPixQrCodeForPayment($payment);
        }

        return $payment;
    }

    /**
     * Busca QR Code / copia-e-cola PIX no Asaas e grava no pagamento local.
     */
    public function syncPixQrCodeForPayment(Payment $payment): void
    {
        if ($payment->isPaid() || ! $payment->asaas_payment_id) {
            return;
        }

        try {
            $pix = $this->getPaymentPixQrCode($payment->asaas_payment_id);
            if (! $pix) {
                return;
            }
            $payment->update([
                'pix_qr_encoded_image' => $pix['encodedImage'] ?? null,
                'pix_copy_paste' => $pix['payload'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Asaas pixQrCode failed', [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $payment->asaas_payment_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lista cobranças de uma assinatura.
     */
    public function getSubscriptionPayments(string $asaasSubscriptionId): array
    {
        $resp = $this->request('GET', '/subscriptions/'.$asaasSubscriptionId.'/payments');
        $resp->throw();
        $data = $resp->json();

        return $data['data'] ?? [];
    }

    /**
     * Retorna dados da assinatura no ASAAS.
     */
    public function getSubscription(string $asaasSubscriptionId): array
    {
        $resp = $this->request('GET', '/subscriptions/'.$asaasSubscriptionId);
        $resp->throw();

        return $resp->json();
    }

    /**
     * Cancela assinatura no ASAAS.
     */
    public function cancelSubscription(string $asaasSubscriptionId): array
    {
        $resp = $this->request('DELETE', '/subscriptions/'.$asaasSubscriptionId);
        $resp->throw();

        return $resp->json();
    }

    /**
     * Sincroniza pagamentos da clínica com a API do Asaas (status, paymentDate, etc.).
     * Útil quando o webhook não foi recebido (ex.: ambiente local ou cobrança marcada como paga no painel Asaas).
     */
    public function syncOrganizationPaymentsFromAsaas(Organization $organization): void
    {
        $subscriptions = $organization->subscriptions()
            ->whereNotNull('asaas_subscription_id')
            ->whereIn('status', ['active', 'ACTIVE'])
            ->get();
        foreach ($subscriptions as $subscription) {
            try {
                $subData = $this->getSubscription($subscription->asaas_subscription_id);
            } catch (\Throwable $e) {
                $httpStatus = $e instanceof RequestException ? $e->response?->status() : null;
                if ($httpStatus === 404) {
                    $subscription->deleteUnpaidLocalPayments();
                    $subscription->update([
                        'status' => 'canceled',
                        'next_due_date' => null,
                        'current_period_end' => null,
                    ]);
                } else {
                    Log::warning('Asaas getSubscription failed', [
                        'organization_id' => $organization->id,
                        'subscription_id' => $subscription->asaas_subscription_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                continue;
            }

            $localStatus = $this->mapAsaasSubscriptionStatusToLocal((string) ($subData['status'] ?? ''));
            $subscription->update([
                'status' => $localStatus,
                'next_due_date' => $subData['nextDueDate'] ?? $subscription->next_due_date,
                'current_period_end' => $subData['currentPeriodEnd'] ?? $subscription->current_period_end,
            ]);

            if ($localStatus === 'canceled') {
                $subscription->deleteUnpaidLocalPayments();
            }

            if ($localStatus !== 'active') {
                continue;
            }

            try {
                $payments = $this->getSubscriptionPayments($subscription->asaas_subscription_id);
            } catch (\Throwable $e) {
                Log::warning('Asaas sync payments failed', [
                    'organization_id' => $organization->id,
                    'subscription_id' => $subscription->asaas_subscription_id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
            foreach ($payments as $item) {
                try {
                    $this->upsertPaymentFromAsaasItem($organization, $subscription, $item);
                } catch (\Throwable $e) {
                    Log::warning('Asaas upsert payment failed', [
                        'organization_id' => $organization->id,
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        $hasPaidPending = $organization->payments()
            ->whereIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
            ->exists();
        if ($hasPaidPending && in_array($organization->subscription_status, ['past_due'], true)) {
            $organization->update([
                'subscription_status' => 'active',
                'billing_status' => 'ok',
                'grace_ends_at' => null,
            ]);
        }
    }

    /**
     * Alinha com o webhook Asaas (OrganizationResource / Subscription local).
     */
    public function mapAsaasSubscriptionStatusToLocal(string $asaasStatus): string
    {
        return match (strtoupper(trim($asaasStatus))) {
            'ACTIVE' => 'active',
            'CANCELED' => 'canceled',
            'INACTIVE', 'EXPIRED' => 'inactive',
            default => strtolower($asaasStatus),
        };
    }

    private function normalizeCpfCnpj(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}
