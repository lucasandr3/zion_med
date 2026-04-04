<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AsaasService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $this->authorize('manage-billing');

        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json([
                'data' => [
                    'organization' => null,
                    'plan_limits' => null,
                    'billing_ui' => [
                        'show_managed_subscription_card' => false,
                        'show_pending_first_payment' => false,
                        'show_plan_selection' => false,
                        'pending_first_payment_message' => null,
                    ],
                    'plans' => [],
                    'subscriptions' => [],
                    'payments' => [],
                    'asaas_configured' => $this->asaasService->isConfigured(),
                ],
            ]);
        }

        $organization->syncExpiredTrialStateIfNeeded();
        $organization->refresh();

        if ($this->asaasService->isConfigured()) {
            $this->asaasService->syncOrganizationPaymentsFromAsaas($organization);
            $organization->refresh();
        }

        $plans = config('asaas.plans', []);
        $subscriptions = $organization->subscriptions()->forTenantBillingListing()->latest()->get()->map(fn (Subscription $s) => [
            'id' => $s->id,
            'asaas_subscription_id' => $s->asaas_subscription_id,
            'plan_key' => $s->plan_key,
            'status' => $s->status,
            'next_due_date' => $s->next_due_date,
            'created_at' => $s->created_at?->toIso8601String(),
        ]);
        $payments = $organization->payments()->visibleOnTenantBilling()->latest()->limit(10)->get()->map(fn (Payment $p) => [
            'id' => $p->id,
            'status' => $p->status,
            'due_date' => $p->due_date,
            'paid_at' => $p->paid_at?->toIso8601String(),
            'value' => $p->value,
            'bank_slip_url' => $p->bank_slip_url,
        ]);

        return response()->json([
            'data' => [
                'organization' => [
                    'id' => $organization->id,
                    'plan_key' => $organization->plan_key,
                    'subscription_status' => $organization->subscription_status,
                    'billing_status' => $organization->billing_status,
                    'trial_ends_at' => $organization->trial_ends_at?->toIso8601String(),
                    'is_on_trial' => $organization->isTrialWindowOpen() && ! $organization->hasConfirmedBillingPayment(),
                    'plan_limits' => $organization->planLimitsForApi(),
                ],
                'billing_ui' => $organization->billingUiState(),
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
        $this->authorize('manage-billing');

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

        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Nenhuma empresa selecionada. Escolha uma empresa (configurações/escolher ou header X-Organization-Id).'], 422);
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

        if (! $organization->meetsLimitsForPlanConfig($plan)) {
            return response()->json([
                'message' => 'Seu uso atual excede os limites do plano selecionado (usuários ou empresas no grupo). Remova usuários extras, ajuste o grupo ou escolha um plano superior.',
            ], 422);
        }

        $doc = preg_replace('/\D/', '', $organization->billing_document ?? '');
        if (strlen($doc) !== 11 && strlen($doc) !== 14) {
            return response()->json(['message' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido em Configurações > Dados Gerais > Dados para Faturamento antes de assinar.'], 422);
        }

        if ($organization->hasActiveGatewaySubscription() && ! $organization->isAwaitingFirstBillingPayment()) {
            return response()->json([
                'message' => 'Sua empresa já possui uma assinatura registrada no pagamento. Aguarde a cobrança ou use a lista de pagamentos. Para trocar de plano, use a opção de alteração de plano.',
            ], 422);
        }

        if ($organization->isAwaitingFirstBillingPayment()) {
            $this->cancelActiveGatewaySubscriptions($organization);
            $organization->refresh();
        }

        $wasOnTrial = $organization->isOnTrial();
        $firstDue = $this->asaasService->firstChargeDueDateForOrganization($organization);

        try {
            $payload = $this->asaasService->createSubscription(
                $organization,
                $planKey,
                (float) $plan['value'],
                'BOLETO',
                $firstDue
            );
        } catch (\Throwable $e) {
            Log::warning('Asaas createSubscription failed', ['organization_id' => $organization->id, 'error' => $e->getMessage()]);
            $errorMessage = $this->extractAsaasErrorMessage($e);

            return response()->json(['message' => $errorMessage], 422);
        }

        $asaasId = $payload['id'] ?? null;
        if (! $asaasId) {
            return response()->json(['message' => 'O gateway de pagamento não retornou o ID da assinatura. Tente novamente ou entre em contato com o suporte.'], 502);
        }

        $nextDue = $payload['nextDueDate'] ?? $firstDue;

        $subscription = Subscription::create([
            'organization_id' => $organization->id,
            'asaas_subscription_id' => $asaasId,
            'plan_key' => $planKey,
            'status' => 'active',
            'next_due_date' => $nextDue,
        ]);

        $this->syncSubscriptionPaymentsFromAsaas($organization, $subscription, $asaasId);

        $organizationUpdates = [
            'plan_key' => $planKey,
            'billing_status' => 'ok',
            'grace_ends_at' => null,
        ];
        if (! $wasOnTrial) {
            $organizationUpdates['subscription_status'] = 'active';
        }
        $organization->update($organizationUpdates);

        $this->whatsAppNotificationService->notifySubscriptionCreated($organization->fresh(), [
            'plan_key' => $planKey,
            'plan_name' => $plan['name'] ?? $planKey,
            'asaas_subscription_id' => $asaasId,
            'next_due_date' => $nextDue,
        ]);

        $dueFormatted = Carbon::parse($nextDue)->format('d/m/Y');
        $successMessage = $wasOnTrial
            ? 'Assinatura registrada. Você permanece em período de trial. A primeira cobrança vencerá em '.$dueFormatted.'.'
            : 'Assinatura ativa. Sua primeira cobrança foi gerada e em breve você receberá o boleto por e-mail.';

        return response()->json([
            'data' => [
                'message' => $successMessage,
                'subscription_id' => $subscription->id,
                'plan_key' => $planKey,
                'next_due_date' => $nextDue,
            ],
        ], 201);
    }

    /**
     * Cancela a assinatura atual (no ASAAS e localmente).
     */
    public function cancelSubscription(Request $request, Subscription $subscription): JsonResponse
    {
        $this->authorize('manage-billing');

        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }
        if ((string) $subscription->organization_id !== (string) $organization->id) {
            abort(404);
        }
        if (in_array($subscription->status, ['CANCELED', 'DELETED'], true)) {
            return response()->json([
                'data' => ['message' => 'Esta assinatura já está cancelada.'],
            ]);
        }

        if ($this->asaasService->isConfigured() && $subscription->asaas_subscription_id) {
            try {
                $this->asaasService->cancelSubscription($subscription->asaas_subscription_id);
            } catch (\Throwable $e) {
                Log::warning('Asaas cancelSubscription failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Não foi possível cancelar no gateway. Tente novamente ou entre em contato com o suporte.',
                ], 502);
            }
        }

        $subscription->deleteUnpaidLocalPayments();
        $subscription->update(['status' => 'CANCELED']);
        if ($organization->plan_key === $subscription->plan_key) {
            $organization->update([
                'plan_key' => null,
                'subscription_status' => 'canceled',
                'billing_status' => 'canceled',
            ]);
        }

        return response()->json([
            'data' => ['message' => 'Assinatura cancelada com sucesso.'],
        ]);
    }

    /**
     * Troca o plano: cancela a assinatura atual (se houver) e cria nova para o plan_key informado.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $this->authorize('manage-billing');

        $allowedPlanKeys = Plan::activeKeys();
        if (empty($allowedPlanKeys)) {
            $allowedPlanKeys = array_keys(config('asaas.plans', []));
        }

        $request->validate([
            'plan_key' => ['required', 'string', Rule::in($allowedPlanKeys)],
        ], [
            'plan_key.required' => 'Nenhum plano foi selecionado.',
            'plan_key.in' => 'Plano inválido.',
        ]);

        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }
        if (! $this->asaasService->isConfigured()) {
            return response()->json(['message' => 'Gateway de pagamento não configurado.'], 503);
        }

        $plans = config('asaas.plans', []);
        $planKey = $request->input('plan_key');
        $plan = $plans[$planKey] ?? null;
        if (! $plan) {
            return response()->json(['message' => 'Plano não encontrado.'], 422);
        }

        if (! $organization->meetsLimitsForPlanConfig($plan)) {
            return response()->json([
                'message' => 'Seu uso atual excede os limites do plano selecionado (usuários ou empresas no grupo). Remova usuários extras, ajuste o grupo ou escolha um plano superior.',
            ], 422);
        }

        $this->cancelActiveGatewaySubscriptions($organization);
        $organization->refresh();

        $doc = preg_replace('/\D/', '', $organization->billing_document ?? '');
        if (strlen($doc) !== 11 && strlen($doc) !== 14) {
            return response()->json(['message' => 'Informe CPF ou CNPJ em Configurações > Dados para Faturamento.'], 422);
        }

        $wasOnTrial = $organization->isOnTrial();
        $firstDue = $this->asaasService->firstChargeDueDateForOrganization($organization);

        try {
            $payload = $this->asaasService->createSubscription(
                $organization,
                $planKey,
                (float) $plan['value'],
                'BOLETO',
                $firstDue
            );
        } catch (\Throwable $e) {
            Log::warning('Asaas createSubscription (changePlan) failed', ['organization_id' => $organization->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => $this->extractAsaasErrorMessage($e)], 422);
        }

        $asaasId = $payload['id'] ?? null;
        if (! $asaasId) {
            return response()->json(['message' => 'Gateway não retornou ID da assinatura.'], 502);
        }

        $nextDue = $payload['nextDueDate'] ?? $firstDue;

        $subscription = Subscription::create([
            'organization_id' => $organization->id,
            'asaas_subscription_id' => $asaasId,
            'plan_key' => $planKey,
            'status' => 'active',
            'next_due_date' => $nextDue,
        ]);

        $this->syncSubscriptionPaymentsFromAsaas($organization, $subscription, $asaasId);

        $organizationUpdates = [
            'plan_key' => $planKey,
            'billing_status' => 'ok',
            'grace_ends_at' => null,
        ];
        if (! $wasOnTrial) {
            $organizationUpdates['subscription_status'] = 'active';
        }
        $organization->update($organizationUpdates);

        $dueFormatted = Carbon::parse($nextDue)->format('d/m/Y');
        $changeMessage = $wasOnTrial
            ? 'Plano alterado. Você permanece em trial. A primeira cobrança vencerá em '.$dueFormatted.'.'
            : 'Plano alterado. Nova cobrança será gerada em breve.';

        return response()->json([
            'data' => [
                'message' => $changeMessage,
                'subscription_id' => $subscription->id,
                'plan_key' => $planKey,
                'next_due_date' => $nextDue,
            ],
        ], 200);
    }

    private function cancelActiveGatewaySubscriptions(Organization $organization): void
    {
        $subs = $organization->subscriptions()
            ->whereNotNull('asaas_subscription_id')
            ->whereIn('status', ['active', 'ACTIVE'])
            ->get();

        foreach ($subs as $sub) {
            try {
                if ($this->asaasService->isConfigured() && $sub->asaas_subscription_id) {
                    $this->asaasService->cancelSubscription($sub->asaas_subscription_id);
                }
            } catch (\Throwable $e) {
                Log::warning('Asaas cancelSubscription failed', [
                    'subscription_id' => $sub->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $sub->deleteUnpaidLocalPayments();
            $sub->update(['status' => 'CANCELED']);
        }
    }

    private function currentOrganization(Request $request): ?Organization
    {
        $organizationId = session('current_organization_id') ?? session('current_clinic_id') ?? $request->user()?->clinic_id;
        if (! $organizationId) {
            return null;
        }

        return Organization::query()->find($organizationId);
    }

    private function syncSubscriptionPaymentsFromAsaas(Organization $organization, Subscription $subscription, string $asaasSubscriptionId): void
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
                    'organization_id' => $organization->id,
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
