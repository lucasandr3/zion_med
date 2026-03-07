<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('asaas.base_url'), '/');
        $this->apiKey  = config('asaas.api_key') ?? '';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    private function request(string $method, string $path, array $data = []): Response
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
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
     * Cria ou retorna customer no ASAAS. Se clinic já tem asaas_customer_id, atualiza e retorna.
     */
    public function ensureCustomer(Clinic $clinic): array
    {
        $doc = $this->normalizeCpfCnpj($clinic->billing_document ?? '');
        if ($doc === '') {
            $doc = '00000000000';
        }
        $payload = [
            'name' => $clinic->billing_name ?: $clinic->name,
            'cpfCnpj' => $doc,
            'email' => $clinic->billing_email ?: $clinic->notification_email ?: $clinic->contact_email ?: 'contato@clinicazionmed.local',
            'phone' => $clinic->phone,
            'externalReference' => (string) $clinic->id,
        ];
        if ($clinic->address) {
            $payload['address'] = $clinic->address;
        }

        if ($clinic->asaas_customer_id) {
            $resp = $this->request('POST', '/customers/' . $clinic->asaas_customer_id, $payload);
            if ($resp->successful()) {
                return $resp->json();
            }
        }

        $resp = $this->request('POST', '/customers', $payload);
        $resp->throw();
        $data = $resp->json();
        $clinic->update(['asaas_customer_id' => $data['id'] ?? null]);
        return $data;
    }

    /**
     * Cria assinatura no ASAAS (cycle MONTHLY, primeira cobrança em nextDueDate).
     */
    public function createSubscription(Clinic $clinic, string $planKey, float $value, string $billingType = 'BOLETO'): array
    {
        $customer = $this->ensureCustomer($clinic);
        $customerId = $customer['id'] ?? $clinic->asaas_customer_id;
        if (! $customerId) {
            throw new \InvalidArgumentException('Cliente ASAAS não encontrado.');
        }

        $productName = config('asaas.product_name', 'ZionMed');
        $nextDue = now()->format('Y-m-d');

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
     * Lista cobranças de uma assinatura.
     */
    public function getSubscriptionPayments(string $asaasSubscriptionId): array
    {
        $resp = $this->request('GET', '/subscriptions/' . $asaasSubscriptionId . '/payments');
        $resp->throw();
        $data = $resp->json();
        return $data['data'] ?? [];
    }

    /**
     * Retorna dados da assinatura no ASAAS.
     */
    public function getSubscription(string $asaasSubscriptionId): array
    {
        $resp = $this->request('GET', '/subscriptions/' . $asaasSubscriptionId);
        $resp->throw();
        return $resp->json();
    }

    /**
     * Cancela assinatura no ASAAS.
     */
    public function cancelSubscription(string $asaasSubscriptionId): array
    {
        $resp = $this->request('DELETE', '/subscriptions/' . $asaasSubscriptionId);
        $resp->throw();
        return $resp->json();
    }

    /**
     * Sincroniza pagamentos da clínica com a API do Asaas (status, paymentDate, etc.).
     * Útil quando o webhook não foi recebido (ex.: ambiente local ou cobrança marcada como paga no painel Asaas).
     */
    public function syncClinicPaymentsFromAsaas(Clinic $clinic): void
    {
        $subscriptions = $clinic->subscriptions()->whereNotNull('asaas_subscription_id')->get();
        foreach ($subscriptions as $subscription) {
            try {
                $payments = $this->getSubscriptionPayments($subscription->asaas_subscription_id);
            } catch (\Throwable $e) {
                Log::warning('Asaas sync payments failed', [
                    'clinic_id' => $clinic->id,
                    'subscription_id' => $subscription->asaas_subscription_id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
            foreach ($payments as $item) {
                $asaasPaymentId = $item['id'] ?? null;
                if (! $asaasPaymentId) {
                    continue;
                }
                $status = strtoupper($item['status'] ?? 'PENDING');
                Payment::updateOrCreate(
                    ['asaas_payment_id' => $asaasPaymentId],
                    [
                        'organization_id' => $clinic->id,
                        'subscription_id' => $subscription->id,
                        'status' => $status,
                        'due_date' => $item['dueDate'] ?? null,
                        'paid_at' => $item['paymentDate'] ?? null,
                        'value' => $item['value'] ?? null,
                        'bank_slip_url' => $item['bankSlipUrl'] ?? $item['invoiceUrl'] ?? null,
                    ]
                );
            }
            // Atualiza next_due_date da assinatura a partir da API
            try {
                $subData = $this->getSubscription($subscription->asaas_subscription_id);
                $subscription->update([
                    'next_due_date' => $subData['nextDueDate'] ?? $subscription->next_due_date,
                    'current_period_end' => $subData['currentPeriodEnd'] ?? $subscription->current_period_end,
                ]);
            } catch (\Throwable $e) {
                // ignora falha ao atualizar subscription
            }
        }
        // Se algum pagamento está pago e a clínica estava em atraso, normaliza status
        $hasPaidPending = $clinic->payments()
            ->whereIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
            ->exists();
        if ($hasPaidPending && in_array($clinic->subscription_status, ['past_due'], true)) {
            $clinic->update([
                'subscription_status' => 'active',
                'billing_status' => 'ok',
                'grace_ends_at' => null,
            ]);
        }
    }

    private function normalizeCpfCnpj(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}
