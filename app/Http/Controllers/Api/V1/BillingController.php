<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AsaasService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(
        private AsaasService $asaasService,
        private WhatsAppNotificationService $whatsAppNotificationService,
    ) {}

    /**
     * Dados de assinatura e planos (mesmo que a aba assinatura nas configurações).
     */
    public function index(Request $request): JsonResponse
    {
        $clinic = $this->currentClinic($request);
        if (! $clinic) {
            return response()->json([
                'data' => [
                    'clinic' => null,
                    'plans' => [],
                    'subscriptions' => [],
                    'payments' => [],
                    'asaas_configured' => $this->asaasService->isConfigured(),
                ],
            ]);
        }

        if ($this->asaasService->isConfigured()) {
            $this->asaasService->syncClinicPaymentsFromAsaas($clinic);
        }

        $plans = config('asaas.plans', []);
        $subscriptions = $clinic->subscriptions()->latest()->get()->map(fn (Subscription $s) => [
            'id' => $s->id,
            'asaas_subscription_id' => $s->asaas_subscription_id,
            'plan_key' => $s->plan_key,
            'status' => $s->status,
            'next_due_date' => $s->next_due_date,
            'created_at' => $s->created_at?->toIso8601String(),
        ]);
        $payments = $clinic->payments()->latest()->limit(10)->get()->map(fn (Payment $p) => [
            'id' => $p->id,
            'status' => $p->status,
            'due_date' => $p->due_date,
            'paid_at' => $p->paid_at?->toIso8601String(),
            'value' => $p->value,
            'bank_slip_url' => $p->bank_slip_url,
        ]);

        return response()->json([
            'data' => [
                'clinic' => [
                    'id' => $clinic->id,
                    'plan_key' => $clinic->plan_key,
                    'subscription_status' => $clinic->subscription_status,
                    'billing_status' => $clinic->billing_status,
                ],
                'plans' => $plans,
                'subscriptions' => $subscriptions,
                'payments' => $payments,
                'asaas_configured' => $this->asaasService->isConfigured(),
            ],
        ]);
    }

    /**
     * Cria assinatura (checkout) para o plano informado.
     */
    public function checkout(Request $request): JsonResponse
    {
        $allowedPlanKeys = Plan::activeKeys();
        if (empty($allowedPlanKeys)) {
            $allowedPlanKeys = array_keys(config('asaas.plans', []));
        }

        $request->validate([
            'plan_key' => ['required', 'string', Rule::in($allowedPlanKeys)],
        ], [
            'plan_key.required' => 'Nenhum plano foi selecionado.',
            'plan_key.in' => 'Plano inválido. Escolha um dos planos disponíveis.',
        ]);

        $clinic = $this->currentClinic($request);
        if (! $clinic) {
            return response()->json(['message' => 'Nenhuma empresa selecionada. Escolha uma empresa (clínica/escolher ou header X-Clinic-Id).'], 422);
        }

        if (! $this->asaasService->isConfigured()) {
            return response()->json(['message' => 'O gateway de pagamento (ASAAS) não está configurado. Entre em contato com o suporte.'], 503);
        }

        $plans = config('asaas.plans', []);
        $planKey = $request->input('plan_key');
        $plan = $plans[$planKey] ?? null;
        if (! $plan) {
            return response()->json(['message' => 'Plano selecionado não existe.'], 422);
        }

        $doc = preg_replace('/\D/', '', $clinic->billing_document ?? '');
        if (strlen($doc) !== 11 && strlen($doc) !== 14) {
            return response()->json(['message' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido em Configurações > Dados Gerais > Dados para Faturamento antes de assinar.'], 422);
        }

        try {
            $payload = $this->asaasService->createSubscription(
                $clinic,
                $planKey,
                (float) $plan['value'],
                'BOLETO'
            );
        } catch (\Throwable $e) {
            Log::warning('Asaas createSubscription failed', ['clinic_id' => $clinic->id, 'error' => $e->getMessage()]);
            $errorMessage = $this->extractAsaasErrorMessage($e);
            return response()->json(['message' => $errorMessage], 422);
        }

        $asaasId = $payload['id'] ?? null;
        if (! $asaasId) {
            return response()->json(['message' => 'O gateway de pagamento não retornou o ID da assinatura. Tente novamente ou entre em contato com o suporte.'], 502);
        }

        $subscription = Subscription::create([
            'organization_id' => $clinic->id,
            'asaas_subscription_id' => $asaasId,
            'plan_key' => $planKey,
            'status' => 'active',
            'next_due_date' => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
        ]);

        $this->syncSubscriptionPaymentsFromAsaas($clinic, $subscription, $asaasId);

        $clinic->update([
            'plan_key' => $planKey,
            'subscription_status' => 'active',
            'billing_status' => 'ok',
            'grace_ends_at' => null,
        ]);

        $this->whatsAppNotificationService->notifySubscriptionCreated($clinic->fresh(), [
            'plan_key' => $planKey,
            'plan_name' => $plan['name'] ?? $planKey,
            'asaas_subscription_id' => $asaasId,
            'next_due_date' => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
        ]);

        return response()->json([
            'data' => [
                'message' => 'Assinatura ativa. Sua primeira cobrança foi gerada e em breve você receberá o boleto por e-mail.',
                'subscription_id' => $subscription->id,
                'plan_key' => $planKey,
                'next_due_date' => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
            ],
        ], 201);
    }

    private function currentClinic(Request $request): ?Clinic
    {
        $clinicId = session('current_clinic_id') ?? $request->user()?->clinic_id;
        if (! $clinicId) {
            return null;
        }
        return Clinic::find($clinicId);
    }

    private function syncSubscriptionPaymentsFromAsaas(Clinic $clinic, Subscription $subscription, string $asaasSubscriptionId): void
    {
        try {
            $payments = $this->asaasService->getSubscriptionPayments($asaasSubscriptionId);
        } catch (\Throwable $e) {
            Log::warning('Asaas getSubscriptionPayments failed', ['subscription_id' => $asaasSubscriptionId, 'error' => $e->getMessage()]);
            return;
        }

        foreach ($payments as $item) {
            $asaasPaymentId = $item['id'] ?? null;
            if (! $asaasPaymentId) {
                continue;
            }
            Payment::updateOrCreate(
                ['asaas_payment_id' => $asaasPaymentId],
                [
                    'organization_id' => $clinic->id,
                    'subscription_id' => $subscription->id,
                    'status' => $item['status'] ?? 'PENDING',
                    'due_date' => isset($item['dueDate']) ? $item['dueDate'] : null,
                    'paid_at' => isset($item['paymentDate']) ? $item['paymentDate'] : null,
                    'value' => $item['value'] ?? null,
                    'bank_slip_url' => $item['bankSlipUrl'] ?? $item['invoiceUrl'] ?? null,
                ]
            );
        }
    }

    private function extractAsaasErrorMessage(\Throwable $e): string
    {
        $response = $e instanceof RequestException ? $e->response : null;
        if ($response) {
            $body = $response->json() ?? [];
            $errors = $body['errors'] ?? [];
            if (! empty($errors) && is_array($errors)) {
                $descriptions = array_filter(array_map(fn ($err) => $err['description'] ?? null, $errors));
                if (! empty($descriptions)) {
                    return implode(' ', array_unique($descriptions));
                }
            }
        }

        $msg = $e->getMessage();
        if (str_contains($msg, 'CPF') || str_contains($msg, 'CNPJ') || str_contains($msg, 'inválido')) {
            return 'O CPF/CNPJ informado é inválido. Corrija em Configurações > Dados Gerais > Dados para Faturamento.';
        }
        if (str_contains($msg, '401') || str_contains($msg, 'Unauthorized')) {
            return 'Chave de API do gateway de pagamento inválida. Entre em contato com o suporte.';
        }
        if (str_contains($msg, '422') || str_contains($msg, 'validation')) {
            return 'Dados inválidos para o gateway de pagamento. Verifique os dados da empresa em Configurações.';
        }
        if (str_contains($msg, '500') || str_contains($msg, 'timeout') || str_contains($msg, 'Connection')) {
            return 'O gateway de pagamento está temporariamente indisponível. Tente novamente em alguns minutos.';
        }

        return 'Não foi possível criar a assinatura. Tente novamente ou entre em contato com o suporte.';
    }
}
