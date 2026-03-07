<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\AsaasService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private AsaasService $asaasService,
        private WhatsAppNotificationService $whatsAppNotificationService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $clinic = $this->currentClinic($request);
        if (! $clinic) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('clinica.configuracoes.edit', ['tab' => 'assinatura']);
    }

    public function checkout(Request $request): RedirectResponse
    {
        // #region agent log
        $logPath = storage_path('logs/debug-1a3ef3.log');
        $log = function ($msg, $data, $h) use ($logPath) {
            try {
                file_put_contents($logPath, json_encode(['sessionId'=>'1a3ef3','id'=>'log_'.time().'_'.uniqid(),'timestamp'=>(int)(microtime(true)*1000),'location'=>'BillingController.php:checkout','message'=>$msg,'data'=>$data,'hypothesisId'=>$h])."\n", FILE_APPEND | LOCK_EX);
            } catch (\Throwable $ignored) {}
        };
        $log('checkout entry', ['plan_key_raw'=>$request->input('plan_key'),'has_csrf'=>$request->has('_token')], 'A');
        // #endregion

        try {
            $request->validate([
                'plan_key' => ['required', 'string', 'in:core,executive,enterprise'],
            ], [
                'plan_key.required' => 'Nenhum plano foi selecionado.',
                'plan_key.in' => 'Plano inválido. Escolha Core, Executive ou Enterprise.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $log('validation failed', ['errors'=>$e->errors(),'plan_key'=>$request->input('plan_key')], 'A');
            throw $e;
        }

        $clinic = $this->currentClinic($request);
        // #region agent log
        $log('currentClinic result', ['clinic_id'=>$clinic?->id,'session_clinic'=>session('current_clinic_id'),'user_clinic'=>$request->user()?->clinic_id], 'B');
        // #endregion
        if (! $clinic) {
            return redirect()->route('dashboard')->with('error', 'Nenhuma empresa selecionada. Escolha uma empresa em Empresa > Escolher empresa.');
        }

        // #region agent log
        $log('asaas configured', ['is_configured'=>$this->asaasService->isConfigured()], 'C');
        // #endregion
        if (! $this->asaasService->isConfigured()) {
            return redirect()->route('clinica.configuracoes.edit', ['tab' => 'assinatura'])->with('error', 'O gateway de pagamento (ASAAS) não está configurado. Entre em contato com o suporte para ativar cobranças.');
        }

        $plans = config('asaas.plans', []);
        $plan = $plans[$request->input('plan_key')] ?? null;
        if (! $plan) {
            return redirect()->route('clinica.configuracoes.edit', ['tab' => 'assinatura'])->with('error', 'Plano selecionado não existe. Escolha Core, Executive ou Enterprise.');
        }

        $doc = preg_replace('/\D/', '', $clinic->billing_document ?? '');
        if (strlen($doc) !== 11 && strlen($doc) !== 14) {
            return redirect()->route('clinica.configuracoes.edit', ['tab' => 'dados'])->with('error', 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido em Configurações > Dados Gerais > Dados para Faturamento antes de assinar.');
        }

        try {
            $payload = $this->asaasService->createSubscription(
                $clinic,
                $request->input('plan_key'),
                (float) $plan['value'],
                'BOLETO'
            );
        } catch (\Throwable $e) {
            // #region agent log
            try {
                file_put_contents(storage_path('logs/debug-1a3ef3.log'), json_encode(['sessionId'=>'1a3ef3','id'=>'log_'.time().'_'.uniqid(),'timestamp'=>(int)(microtime(true)*1000),'location'=>'BillingController.php:checkout catch','message'=>'createSubscription exception','data'=>['error'=>$e->getMessage(),'class'=>get_class($e),'clinic_id'=>$clinic->id],'hypothesisId'=>'D'])."\n", FILE_APPEND | LOCK_EX);
            } catch (\Throwable $ignored) {}
            // #endregion
            Log::warning('Asaas createSubscription failed', ['clinic_id' => $clinic->id, 'error' => $e->getMessage()]);

            $errorMessage = $this->extractAsaasErrorMessage($e);
            return redirect()->route('clinica.configuracoes.edit', ['tab' => 'dados'])->with('error', $errorMessage);
        }

        // #region agent log
        $logPath = storage_path('logs/debug-1a3ef3.log');
        $log = function ($msg, $data, $h) use ($logPath) {
            try {
                file_put_contents($logPath, json_encode(['sessionId'=>'1a3ef3','id'=>'log_'.time().'_'.uniqid(),'timestamp'=>(int)(microtime(true)*1000),'location'=>'BillingController.php:checkout','message'=>$msg,'data'=>$data,'hypothesisId'=>$h])."\n", FILE_APPEND | LOCK_EX);
            } catch (\Throwable $ignored) {}
        };
        $log('createSubscription success', ['payload_keys'=>array_keys($payload),'has_id'=>isset($payload['id']),'asaas_id'=>$payload['id'] ?? null], 'E');
        // #endregion

        $asaasId = $payload['id'] ?? null;
        if (! $asaasId) {
            // #region agent log
            try {
                file_put_contents(storage_path('logs/debug-1a3ef3.log'), json_encode(['sessionId'=>'1a3ef3','id'=>'log_'.time().'_'.uniqid(),'timestamp'=>(int)(microtime(true)*1000),'location'=>'BillingController.php:checkout','message'=>'payload missing id','data'=>['payload_keys'=>array_keys($payload)],'hypothesisId'=>'E'])."\n", FILE_APPEND | LOCK_EX);
            } catch (\Throwable $ignored) {}
            // #endregion
            return redirect()->route('clinica.configuracoes.edit', ['tab' => 'assinatura'])->with('error', 'O gateway de pagamento não retornou o ID da assinatura. Tente novamente ou entre em contato com o suporte.');
        }

        $subscription = Subscription::create([
            'organization_id' => $clinic->id,
            'asaas_subscription_id' => $asaasId,
            'plan_key' => $request->input('plan_key'),
            'status' => 'active',
            'next_due_date' => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
        ]);

        $this->syncSubscriptionPaymentsFromAsaas($clinic, $subscription, $asaasId);

        $clinic->update([
            'plan_key' => $request->input('plan_key'),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
            'grace_ends_at' => null,
        ]);

        $this->whatsAppNotificationService->notifySubscriptionCreated($clinic->fresh(), [
            'plan_key' => $request->input('plan_key'),
            'plan_name' => $plan['name'] ?? $request->input('plan_key'),
            'asaas_subscription_id' => $asaasId,
            'next_due_date' => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
        ]);

        return redirect()->route('clinica.configuracoes.edit', ['tab' => 'assinatura'])->with('success', 'Assinatura ativa. Sua primeira cobrança foi gerada e em breve você receberá o boleto por e-mail.');
    }

    /**
     * Busca cobranças da assinatura no ASAAS e persiste no banco (boletos/faturas).
     */
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

    private function currentClinic(Request $request): ?Clinic
    {
        $clinicId = session('current_clinic_id') ?? $request->user()?->clinic_id;
        if (! $clinicId) {
            return null;
        }
        return Clinic::find($clinicId);
    }

    /**
     * Extrai mensagem descritiva da API ASAAS a partir da exceção.
     * Retorna as descrições do JSON de erro quando disponível.
     */
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
