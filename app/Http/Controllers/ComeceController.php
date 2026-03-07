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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
        $selectedPlan = $request->query('plan', 'core');
        if (! array_key_exists($selectedPlan, $plans)) {
            $selectedPlan = 'core';
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

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_key' => ['required', 'string', 'in:core,executive,enterprise'],
        ], [
            'company_name.required' => 'Informe o nome da empresa.',
            'email.unique' => 'Este e-mail já está em uso. Faça login ou use outro e-mail.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
        ]);

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
            'name' => $validated['company_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => Role::Owner,
            'active' => true,
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
