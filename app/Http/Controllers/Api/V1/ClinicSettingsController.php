<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Events\AuditEvent;
use App\Http\Requests\ClinicSettingsRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use App\Services\AsaasService;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicSettingsController extends Controller
{
    public function __construct(
        private ThemeService $themeService,
        private AsaasService $asaasService,
    ) {}

    /**
     * Dados da página de configurações da clínica (mesmo que a view web).
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');

        $organizationId = session('current_organization_id') ?? session('current_clinic_id');
        $organization = Organization::query()->findOrFail($organizationId);

        $organization->syncExpiredTrialStateIfNeeded();
        $organization->refresh();

        if ($this->asaasService->isConfigured()) {
            $this->asaasService->syncOrganizationPaymentsFromAsaas($organization);
        }

        $billingPlans = config('asaas.plans', []);
        $billingSubscriptions = $organization->subscriptions()->forTenantBillingListing()->latest()->get()->map(fn ($s) => [
            'id' => $s->id,
            'asaas_subscription_id' => $s->asaas_subscription_id,
            'plan_key' => $s->plan_key,
            'status' => $s->status,
            'next_due_date' => $s->next_due_date,
            'created_at' => $s->created_at?->toIso8601String(),
        ]);
        $billingPayments = $organization->payments()->visibleOnTenantBilling()->latest()->limit(10)->get()->map(fn ($p) => [
            'id' => $p->id,
            'status' => $p->status,
            'due_date' => $p->due_date,
            'paid_at' => $p->paid_at?->toIso8601String(),
            'value' => $p->value,
            'bank_slip_url' => $p->bank_slip_url,
        ]);

        $multiEmpresaPlan = config('asaas.multi_empresa_plan', 'enterprise');
        $canAddMultiEmpresa = $organization->plan_key === $multiEmpresaPlan;
        $tenantOrganizations = $canAddMultiEmpresa && $organization->tenant_id
            ? Organization::withoutGlobalScopes()->where('tenant_id', $organization->tenant_id)->orderBy('name')->withCount('users')->get()
            : collect();

        return response()->json([
            'data' => [
                'organization' => new OrganizationResource($organization),
                'available_themes' => $this->themeService->getAvailableThemes(),
                'billing_plans' => $billingPlans,
                'billing_ui' => $organization->billingUiState(),
                'billing_subscriptions' => $billingSubscriptions,
                'billing_payments' => $billingPayments,
                'asaas_configured' => $this->asaasService->isConfigured(),
                'active_config_tab' => $request->query('tab', 'dados'),
                'can_add_multi_empresa' => $canAddMultiEmpresa,
                'tenant_organizations' => OrganizationResource::collection($tenantOrganizations),
            ],
        ]);
    }

    /**
     * Atualiza configurações da clínica.
     */
    public function update(ClinicSettingsRequest $request): JsonResponse
    {
        $organizationId = session('current_organization_id') ?? session('current_clinic_id');
        $organization = Organization::query()->findOrFail($organizationId);
        $this->authorize('update-clinic', $organization);
        $data = $request->validated();

        if ($request->boolean('dark_mode_only')) {
            $organization->update(['dark_mode' => $request->boolean('dark_mode')]);
            return response()->json(['data' => new OrganizationResource($organization->fresh())]);
        }

        if ($request->boolean('theme_only')) {
            $organization->update(['theme' => $request->input('theme')]);
            return response()->json(['data' => new OrganizationResource($organization->fresh())]);
        }

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('minio_assets')->delete($organization->logo_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($organization->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store(
                'organizations/' . $organization->id . '/logos',
                'minio_assets'
            );
        }
        unset($data['logo']);

        if ($request->hasFile('cover_image')) {
            if ($organization->cover_image_path) {
                \Illuminate\Support\Facades\Storage::disk('minio_assets')->delete($organization->cover_image_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($organization->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store(
                'organizations/' . $organization->id . '/covers',
                'minio_assets'
            );
        }
        unset($data['cover_image']);

        if (isset($data['business_hours'])) {
            $cleaned = [];
            foreach ($data['business_hours'] as $d => $slot) {
                $open = trim($slot['open'] ?? '');
                $close = trim($slot['close'] ?? '');
                if ($open !== '' && $close !== '') {
                    $cleaned[(string) $d] = ['open' => $open, 'close' => $close];
                }
            }
            $data['business_hours'] = ! empty($cleaned) ? $cleaned : null;
        }

        foreach (['phone', 'contact_email', 'short_description', 'specialties', 'founded_year', 'meta_description', 'maps_url', 'billing_name', 'billing_email', 'billing_document', 'public_theme', 'cover_color', 'cover_mode'] as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        $data['whatsapp_notifications_enabled'] = $request->boolean('whatsapp_notifications_enabled');
        $data['whatsapp_notify_cobranca'] = $request->boolean('whatsapp_notify_cobranca');
        $data['whatsapp_notify_faturas_boleto'] = $request->boolean('whatsapp_notify_faturas_boleto');
        $data['whatsapp_notify_avisos'] = $request->boolean('whatsapp_notify_avisos');

        $organization->update($data);
        \Illuminate\Support\Facades\Event::dispatch(new AuditEvent('clinic.updated', Organization::class, $organization->id, null, $organization->id, $request->user()?->id));

        return response()->json([
            'data' => new OrganizationResource($organization->fresh()),
        ]);
    }

}
