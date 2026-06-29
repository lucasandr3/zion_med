<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AsaasService;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $billingDocumentRules = [
            'nullable',
            'string',
            'max:25',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                $doc = preg_replace('/\D/', '', (string) $value);
                if (strlen($doc) !== 11 && strlen($doc) !== 14) {
                    $fail('Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido para faturamento.');
                }
            },
        ];

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_key' => ['required', 'string', Rule::in(array_keys(config('asaas.plans', [])))],
            'billing_document' => $billingDocumentRules,
            'billing_type' => ['nullable', 'string', Rule::in(['BOLETO', 'PIX'])],
            'phone' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = preg_replace('/\D/', '', (string) $value);
                    if (strlen($digits) < 10 || strlen($digits) > 11) {
                        $fail('Informe um WhatsApp válido com DDD.');
                    }
                },
            ],
            'niche' => ['nullable', 'string', 'max:64', Rule::in(array_keys(FormTemplate::categoryLabels()))],
            'accepted_terms' => ['accepted'],
        ], [
            'company_name.required' => 'Informe o nome da empresa.',
            'responsible_name.required' => 'Informe o seu nome (responsável).',
            'email.unique' => 'Este e-mail já está em uso. Faça login ou use outro e-mail.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'billing_document.required' => 'Informe CPF ou CNPJ apenas se quiser já configurar faturamento.',
            'phone.required' => 'Informe um WhatsApp válido com DDD.',
            'accepted_terms.accepted' => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para continuar.',
        ]);

        $validated['phone'] = preg_replace('/\D/', '', (string) $validated['phone']);

        try {
            $trialDays = (int) config('asaas.trial_days', 14);
            $niche = isset($validated['niche']) && is_string($validated['niche']) && $validated['niche'] !== ''
                ? $validated['niche']
                : 'estetica';

            $tenantSlug = $this->uniqueTenantSlug(Str::slug($validated['company_name']));
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'slug' => $tenantSlug,
            ]);

            $organizationSlug = Organization::generateUniqueSlug($validated['company_name'], null);
            $organization = Organization::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['company_name'],
                'slug' => $organizationSlug,
                'niche' => $niche,
                'notification_email' => $validated['email'],
                'billing_email' => $validated['email'],
                'billing_name' => $validated['company_name'],
                'billing_document' => isset($validated['billing_document']) && $validated['billing_document'] !== ''
                    ? preg_replace('/\D/', '', (string) $validated['billing_document'])
                    : null,
                'phone' => $validated['phone'],
                'plan_key' => $validated['plan_key'],
                'trial_ends_at' => now()->addDays($trialDays),
                'subscription_status' => 'trial',
                'billing_status' => 'ok',
            ]);

            $user = User::create([
                'organization_id' => $organization->id,
                'name' => $validated['responsible_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => Role::Owner->value,
                'active' => true,
            ]);

            FormTemplateSeeder::seedTemplatesForOrganization($organization, $user);

            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::warning('Comece API: falha ao enviar e-mail de verificação', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $subscriptionCreated = false;
            $firstInvoiceDueDate = null;
            $billingDoc = $organization->billing_document;
            if ($this->asaasService->isConfigured() && $billingDoc !== null && $billingDoc !== '') {
                $plans = config('asaas.plans', []);
                $plan = $plans[$validated['plan_key']] ?? null;
                if ($plan) {
                    try {
                        $organization->refresh();
                        $firstDue = $this->asaasService->firstChargeDueDateForOrganization($organization);
                        $billingType = $this->asaasService->normalizeBillingType($validated['billing_type'] ?? 'PIX');
                        $payload = $this->asaasService->createSubscription(
                            $organization,
                            $validated['plan_key'],
                            (float) $plan['value'],
                            $billingType,
                            $firstDue
                        );
                        $asaasId = $payload['id'] ?? null;
                        if ($asaasId) {
                            $firstInvoiceDueDate = $payload['nextDueDate'] ?? $firstDue;
                            Subscription::create([
                                'organization_id' => $organization->id,
                                'asaas_subscription_id' => $asaasId,
                                'plan_key' => $validated['plan_key'],
                                'billing_type' => $billingType,
                                'status' => 'active',
                                'next_due_date' => $firstInvoiceDueDate,
                            ]);
                            $organization->update([
                                'plan_key' => $validated['plan_key'],
                                'billing_status' => 'ok',
                                'grace_ends_at' => null,
                            ]);
                            $subscriptionCreated = true;
                        }
                    } catch (\Throwable $e) {
                        $this->notifyWebhookErroPagamento('payment', $request, $e->getMessage(), [
                            'company_name' => $validated['company_name'],
                            'email' => $validated['email'],
                            'plan_key' => $validated['plan_key'],
                        ]);
                    }
                }
            }

            $user->tokens()->where('name', 'spa')->delete();
            $token = $user->createToken('spa')->plainTextToken;

            $organizations = Organization::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('name')->withCount('users')->get();
            if ($organizations->isEmpty()) {
                $organizations = Organization::query()->where('id', $organization->id)->withCount('users')->get();
            }

            $successMessage = $subscriptionCreated && $firstInvoiceDueDate
                ? 'Conta criada com sucesso! Período de trial de '.$trialDays.' dias. A primeira cobrança vencerá em '
                    .Carbon::parse($firstInvoiceDueDate)->format('d/m/Y').'.'
                : 'Conta criada! Você tem '.$trialDays.' dias de trial. Acesse Empresa > Configurações > Assinatura para ativar seu plano.';

            return response()->json([
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => new UserResource($user),
                    'current_organization_id' => $organization->id,
                    'organizations' => OrganizationResource::collection($organizations),
                    'message' => $successMessage,
                ],
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->notifyWebhookErroPagamento('database', $request, $e->getMessage());
            Log::error('Erro no cadastro da API comece', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

}
