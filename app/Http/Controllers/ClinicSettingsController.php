<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClinicSettingsRequest;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Services\AsaasService;
use App\Services\AuditService;
use App\Services\ThemeService;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClinicSettingsController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ThemeService $themeService,
        private AsaasService $asaasService,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-clinic');

        $clinic = Clinic::findOrFail(session('current_clinic_id'));

        if ($this->asaasService->isConfigured()) {
            $this->asaasService->syncClinicPaymentsFromAsaas($clinic);
        }

        $billingPlans = config('asaas.plans', []);
        $billingSubscriptions = $clinic->subscriptions()->latest()->get();
        $billingPayments = $clinic->payments()->latest()->limit(10)->get();

        $multiEmpresaPlan = config('asaas.multi_empresa_plan', 'enterprise');
        $canAddMultiEmpresa = $clinic->plan_key === $multiEmpresaPlan;
        $tenantClinics = $canAddMultiEmpresa && $clinic->tenant_id
            ? Clinic::withoutGlobalScopes()->where('tenant_id', $clinic->tenant_id)->orderBy('name')->withCount('users')->get()
            : collect();

        return view('clinica.configuracoes', [
            'clinic'             => $clinic,
            'availableThemes'    => $this->themeService->getAvailableThemes(),
            'billingPlans'       => $billingPlans,
            'billingSubscriptions' => $billingSubscriptions,
            'billingPayments'    => $billingPayments,
            'asaasConfigured'    => $this->asaasService->isConfigured(),
            'activeConfigTab'    => $request->query('tab', 'dados'),
            'canAddMultiEmpresa' => $canAddMultiEmpresa,
            'tenantClinics'      => $tenantClinics,
        ]);
    }

    public function update(ClinicSettingsRequest $request): RedirectResponse
    {
        $clinic = Clinic::findOrFail(session('current_clinic_id'));
        $this->authorize('update-clinic', $clinic);
        $data = $request->validated();

        // Dark-mode-only AJAX toggle (sent from the layout toggle button)
        if ($request->boolean('dark_mode_only')) {
            $clinic->update(['dark_mode' => $request->boolean('dark_mode')]);
            return back();
        }

        // Theme-only AJAX change (sent from the header theme picker)
        if ($request->boolean('theme_only')) {
            $clinic->update(['theme' => $request->input('theme')]);
            return back();
        }

        if ($request->hasFile('logo')) {
            if ($clinic->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clinic->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);

        if ($request->hasFile('cover_image')) {
            if ($clinic->cover_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clinic->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }
        unset($data['cover_image']);

        if (isset($data['business_hours'])) {
            $cleaned = [];
            foreach ($data['business_hours'] as $d => $slot) {
                $open  = trim($slot['open'] ?? '');
                $close = trim($slot['close'] ?? '');
                if ($open !== '' && $close !== '') {
                    $cleaned[$d] = ['open' => $open, 'close' => $close];
                }
            }
            $data['business_hours'] = ! empty($cleaned) ? $cleaned : null;
        }

        foreach (['phone', 'contact_email', 'short_description', 'specialties', 'founded_year', 'meta_description', 'maps_url', 'billing_name', 'billing_email', 'billing_document'] as $key) {
            if (isset($data[$key]) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        $data['whatsapp_notifications_enabled'] = $request->boolean('whatsapp_notifications_enabled');
        $data['whatsapp_notify_cobranca'] = $request->boolean('whatsapp_notify_cobranca');
        $data['whatsapp_notify_faturas_boleto'] = $request->boolean('whatsapp_notify_faturas_boleto');
        $data['whatsapp_notify_avisos'] = $request->boolean('whatsapp_notify_avisos');

        $clinic->update($data);
        $this->auditService->log('clinic.updated', Clinic::class, $clinic->id);

        return redirect()->route('clinica.configuracoes.edit')
            ->with('success', 'Configurações salvas com sucesso.');
    }

    /**
     * Adiciona uma nova empresa/filial ao mesmo tenant. Apenas plano enterprise.
     */
    public function storeEmpresa(Request $request): RedirectResponse
    {
        $clinic = Clinic::findOrFail(session('current_clinic_id'));
        $this->authorize('manage-clinic');

        $multiEmpresaPlan = config('asaas.multi_empresa_plan', 'enterprise');
        if ($clinic->plan_key !== $multiEmpresaPlan) {
            return redirect()->route('clinica.configuracoes.edit', ['tab' => 'dados'])
                ->with('error', 'Adicionar várias empresas está disponível apenas no plano ' . (config('asaas.plans')[$multiEmpresaPlan]['name'] ?? $multiEmpresaPlan) . '.');
        }

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'notification_email' => ['nullable', 'string', 'email', 'max:255'],
            ], [
                'name.required' => 'Informe o nome da empresa/filial.',
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('clinica.configuracoes.edit', ['tab' => 'empresas']));
        }

        $tenant = $clinic->tenant;
        if (! $tenant) {
            $tenantSlug = $this->uniqueTenantSlug(Str::slug($clinic->name));
            $tenant = Tenant::create([
                'name' => $clinic->name,
                'slug' => $tenantSlug,
            ]);
            $clinic->update(['tenant_id' => $tenant->id]);
        }

        $slug = $this->uniqueClinicSlug(Str::slug($validated['name']));
        $newClinic = Clinic::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'notification_email' => $validated['notification_email'] ?? $clinic->notification_email,
            'billing_email' => $validated['notification_email'] ?? $clinic->billing_email ?? $clinic->notification_email,
            'billing_name' => $validated['name'],
            'plan_key' => $clinic->plan_key,
            'trial_ends_at' => $clinic->trial_ends_at,
            'subscription_status' => $clinic->subscription_status,
            'billing_status' => $clinic->billing_status ?? 'ok',
        ]);

        $owner = $request->user();
        FormTemplateSeeder::seedTemplatesForClinic($newClinic, $owner);

        $this->auditService->log('clinic.created', Clinic::class, $newClinic->id);

        // Não alterar a clínica atual: o usuário continua na empresa em que estava.
        // Para usar a nova empresa, ele deve clicar em "Trocar empresa" no menu.

        return redirect()->route('clinica.configuracoes.edit', ['tab' => 'empresas'])
            ->with('success', 'Empresa "' . $newClinic->name . '" adicionada. Use "Trocar empresa" no menu (ícone do seu nome) para acessá-la.');
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
