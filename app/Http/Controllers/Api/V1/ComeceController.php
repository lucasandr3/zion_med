<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AsaasService;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ComeceController extends Controller
{
    public function __construct(
        private AsaasService $asaasService,
    ) {}

    /**
     * Cadastro da landing (comece) — mesmo fluxo do ComeceController web, retorna token para a SPA.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name'      => ['required', 'string', 'max:255'],
            'responsible_name'  => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
            'plan_key'          => ['required', 'string', Rule::in(array_keys(config('asaas.plans', [])))],
            'accepted_terms'    => ['accepted'],
        ], [
            'company_name.required'      => 'Informe o nome da empresa.',
            'responsible_name.required'  => 'Informe o seu nome (responsável).',
            'email.unique'               => 'Este e-mail já está em uso. Faça login ou use outro e-mail.',
            'password.min'               => 'A senha deve ter no mínimo 8 caracteres.',
            'accepted_terms.accepted'    => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para continuar.',
        ]);

        try {
            $trialDays = (int) config('asaas.trial_days', 14);

            $tenantSlug = $this->uniqueTenantSlug(Str::slug($validated['company_name']));
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'slug' => $tenantSlug,
            ]);

            $clinicSlug = $this->uniqueClinicSlug(Str::slug($validated['company_name']));
            $clinic = Clinic::create([
                'tenant_id'          => $tenant->id,
                'name'               => $validated['company_name'],
                'slug'               => $clinicSlug,
                'notification_email' => $validated['email'],
                'billing_email'      => $validated['email'],
                'billing_name'       => $validated['company_name'],
                'plan_key'           => $validated['plan_key'],
                'trial_ends_at'      => now()->addDays($trialDays),
                'subscription_status'=> 'trial',
                'billing_status'     => 'ok',
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
                                'organization_id'       => $clinic->id,
                                'asaas_subscription_id' => $asaasId,
                                'plan_key'             => $validated['plan_key'],
                                'status'               => 'active',
                                'next_due_date'        => $payload['nextDueDate'] ?? now()->format('Y-m-d'),
                            ]);
                            $subscriptionCreated = true;
                        }
                    } catch (\Throwable $e) {
                        $this->notifyWebhookErroPagamento('payment', $request, $e->getMessage(), [
                            'company_name' => $validated['company_name'],
                            'email'        => $validated['email'],
                            'plan_key'     => $validated['plan_key'],
                        ]);
                    }
                }
            }

            $user->tokens()->where('name', 'spa')->delete();
            $token = $user->createToken('spa')->plainTextToken;

            $clinics = Clinic::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('name')->withCount('users')->get();
            if ($clinics->isEmpty()) {
                $clinics = Clinic::where('id', $clinic->id)->withCount('users')->get();
            }

            $successMessage = $subscriptionCreated
                ? 'Conta criada com sucesso! Seu boleto foi gerado e será enviado por e-mail. Enquanto isso, você já pode usar o trial.'
                : 'Conta criada! Você tem ' . $trialDays . ' dias de trial. Acesse Empresa > Configurações > Assinatura para ativar seu plano.';

            return response()->json([
                'data' => [
                    'token'             => $token,
                    'token_type'        => 'Bearer',
                    'user'              => new UserResource($user),
                    'current_clinic_id' => $clinic->id,
                    'clinics'           => ClinicResource::collection($clinics),
                    'message'           => $successMessage,
                ],
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->notifyWebhookErroPagamento('database', $request, $e->getMessage());
            Log::error('Erro no cadastro da API comece', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ocorreu um erro ao criar sua conta. Tente novamente ou entre em contato conosco.',
            ], 500);
        }
    }

    private function notifyWebhookErroPagamento(string $errorType, Request $request, ?string $message = null, array $extra = []): void
    {
        $url = config('services.n8n_webhook_erro_pagamento');
        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }
        $data = array_merge([
            'company_name'      => $request->input('company_name'),
            'responsible_name'  => $request->input('responsible_name'),
            'email'             => $request->input('email'),
            'plan_key'          => $request->input('plan_key'),
        ], $extra);
        $payload = [
            'source'      => 'comece_landing',
            'error_type'  => $errorType,
            'message'     => $message,
            'data'        => $data,
            'occurred_at' => now()->toIso8601String(),
        ];
        try {
            Http::timeout(10)->withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);
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
