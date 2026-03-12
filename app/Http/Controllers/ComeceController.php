<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AsaasService;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class ComeceController extends Controller
{
    public function __construct(
        private AsaasService $asaasService,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $plans = config('asaas.plans', []);
        // Para o teste com preço único, sempre usamos o primeiro plano configurado como padrão.
        $selectedPlan = $request->query('plan');
        $defaultPlanKey = ! empty($plans) ? array_key_first($plans) : null;
        if (! $selectedPlan || ! isset($plans[$selectedPlan])) {
            $selectedPlan = $defaultPlanKey;
        }

        return view('comece', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $validator = Validator::make($request->all(), [
            'company_name'     => ['required', 'string', 'max:255'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'plan_key'         => ['required', 'string', Rule::in(array_keys(config('asaas.plans', [])))],
            'accepted_terms'   => ['accepted'],
        ], [
            'company_name.required'     => 'Informe o nome da empresa.',
            'responsible_name.required' => 'Informe o seu nome (responsável).',
            'email.unique'              => 'Este e-mail já está em uso. Faça login ou use outro e-mail.',
            'password.min'              => 'A senha deve ter no mínimo 8 caracteres.',
            'accepted_terms.accepted'   => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para continuar.',
        ]);

        if ($validator->fails()) {
            $this->notifyWebhookErroPagamento('validation', $request, $validator->errors()->first());
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            $trialDays = (int) config('asaas.trial_days', 14);

            $tenantSlug = $this->uniqueTenantSlug(Str::slug($validated['company_name']));
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'slug' => $tenantSlug,
            ]);

            $clinicSlug = $this->uniqueClinicSlug(Str::slug($validated['company_name']));
            $clinic = Clinic::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['company_name'],
                'slug' => $clinicSlug,
                'notification_email' => $validated['email'],
                'billing_email' => $validated['email'],
                'billing_name' => $validated['company_name'],
                'plan_key' => $validated['plan_key'],
                'trial_ends_at' => now()->addDays($trialDays),
                'subscription_status' => 'trial',
                'billing_status' => 'ok',
            ]);

            $user = User::create([
                'organization_id' => $clinic->id,
                'name'            => $validated['responsible_name'],
                'email'           => $validated['email'],
                'password'        => Hash::make($validated['password']),
                'role'            => Role::Owner,
                'active'          => true,
            ]);

            FormTemplateSeeder::seedTemplatesForClinic($clinic, $user);

            $subscriptionCreated = false;
            if ($this->asaasService->isConfigured()) {
                $plans = config('asaas.plans', []);
                $plan = $plans[$validated['plan_key']] ?? null;
                if ($plan) {
                    try {
                        $payload = $this->asaasService->createSubscription(
                            $clinic,
                            $validated['plan_key'],
                            (float) $plan['value'],
                            'BOLETO'
                        );
                        $asaasId = $payload['id'] ?? null;
                        if ($asaasId) {
                            Subscription::create([
                                'organization_id' => $clinic->id,
                                'asaas_subscription_id' => $asaasId,
                                'plan_key' => $validated['plan_key'],
                                'status' => 'active',
                                'next_due_date' => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
                            ]);
                            $subscriptionCreated = true;
                        }
                    } catch (\Throwable $e) {
                        $this->notifyWebhookErroPagamento('payment', $request, $e->getMessage(), [
                            'company_name' => $validated['company_name'],
                            'email' => $validated['email'],
                            'plan_key' => $validated['plan_key'],
                        ]);
                        // Trial já ativo; cobrança pode ser feita depois na área logada
                    }
                }
            }

            Auth::login($user);
            $request->session()->regenerate();
            session(['current_clinic_id' => $clinic->id]);

            if ($subscriptionCreated) {
                return redirect()->route('dashboard')
                    ->with('success', 'Conta criada com sucesso! Seu boleto foi gerado e será enviado por e-mail. Enquanto isso, você já pode usar o trial.');
            }

            return redirect()->route('dashboard')
                ->with('success', 'Conta criada! Você tem ' . $trialDays . ' dias de trial. Acesse Empresa > Configurações > Assinatura para ativar seu plano.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->notifyWebhookErroPagamento('database', $request, $e->getMessage());
            Log::error('Erro no cadastro da landing (comece)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('comece.show')
                ->withInput($request->except('password'))
                ->with('error', 'Ocorreu um erro ao criar sua conta. Tente novamente ou entre em contato conosco.');
        }
    }

    /**
     * Envia notificação de erro no cadastro da landing para o webhook n8n (erro-pagamento).
     * Não envia senha no payload.
     */
    private function notifyWebhookErroPagamento(
        string $errorType,
        Request $request,
        ?string $message = null,
        array $extra = []
    ): void {
        $url = config('services.n8n_webhook_erro_pagamento');
        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        $data = array_merge([
            'company_name' => $request->input('company_name'),
            'responsible_name' => $request->input('responsible_name'),
            'email' => $request->input('email'),
            'plan_key' => $request->input('plan_key'),
        ], $extra);

        $payload = [
            'source' => 'comece_landing',
            'error_type' => $errorType,
            'message' => $message,
            'data' => $data,
            'occurred_at' => now()->toIso8601String(),
        ];

        try {
            Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar webhook erro-pagamento', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    private function uniqueTenantSlug(string $base): string
    {
        $slug = $base;
        $n = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }

    private function uniqueClinicSlug(string $base): string
    {
        $slug = $base;
        $n = 1;
        while (Clinic::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }
}
