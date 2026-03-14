<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Events\AuditEvent;
use App\Http\Requests\ClinicSettingsRequest;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Models\Clinic;
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

        $clinic = Clinic::findOrFail(session('current_clinic_id'));

        if ($this->asaasService->isConfigured()) {
            $this->asaasService->syncClinicPaymentsFromAsaas($clinic);
        }

        $billingPlans = config('asaas.plans', []);
        $billingSubscriptions = $clinic->subscriptions()->latest()->get()->map(fn ($s) => [
            'id' => $s->id,
            'asaas_subscription_id' => $s->asaas_subscription_id,
            'plan_key' => $s->plan_key,
            'status' => $s->status,
            'next_due_date' => $s->next_due_date,
            'created_at' => $s->created_at?->toIso8601String(),
        ]);
        $billingPayments = $clinic->payments()->latest()->limit(10)->get()->map(fn ($p) => [
            'id' => $p->id,
            'status' => $p->status,
            'due_date' => $p->due_date,
            'paid_at' => $p->paid_at?->toIso8601String(),
            'value' => $p->value,
            'bank_slip_url' => $p->bank_slip_url,
        ]);

        $multiEmpresaPlan = config('asaas.multi_empresa_plan', 'enterprise');
        $canAddMultiEmpresa = $clinic->plan_key === $multiEmpresaPlan;
        $tenantClinics = $canAddMultiEmpresa && $clinic->tenant_id
            ? Clinic::withoutGlobalScopes()->where('tenant_id', $clinic->tenant_id)->orderBy('name')->withCount('users')->get()
            : collect();

        return response()->json([
            'data' => [
                'clinic' => new ClinicResource($clinic),
                'available_themes' => $this->themeService->getAvailableThemes(),
                'billing_plans' => $billingPlans,
                'billing_subscriptions' => $billingSubscriptions,
                'billing_payments' => $billingPayments,
                'asaas_configured' => $this->asaasService->isConfigured(),
                'active_config_tab' => $request->query('tab', 'dados'),
                'can_add_multi_empresa' => $canAddMultiEmpresa,
                'tenant_clinics' => ClinicResource::collection($tenantClinics),
            ],
        ]);
    }

    /**
     * Atualiza configurações da clínica.
     */
    public function update(ClinicSettingsRequest $request): JsonResponse
    {
        $clinic = Clinic::findOrFail(session('current_clinic_id'));
        $this->authorize('update-clinic', $clinic);
        $data = $request->validated();

        if ($request->boolean('dark_mode_only')) {
            $clinic->update(['dark_mode' => $request->boolean('dark_mode')]);
            return response()->json(['data' => new ClinicResource($clinic->fresh())]);
        }

        if ($request->boolean('theme_only')) {
            $clinic->update(['theme' => $request->input('theme')]);
            return response()->json(['data' => new ClinicResource($clinic->fresh())]);
        }

        if ($request->hasFile('logo')) {
            if ($clinic->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('minio_assets')->delete($clinic->logo_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clinic->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store(
                'organizations/' . $clinic->id . '/logos',
                'minio_assets'
            );
        }
        unset($data['logo']);

        if ($request->hasFile('cover_image')) {
            if ($clinic->cover_image_path) {
                \Illuminate\Support\Facades\Storage::disk('minio_assets')->delete($clinic->cover_image_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clinic->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store(
                'organizations/' . $clinic->id . '/covers',
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

        foreach (['phone', 'contact_email', 'short_description', 'specialties', 'founded_year', 'meta_description', 'maps_url', 'billing_name', 'billing_email', 'billing_document', 'public_theme', 'cover_color'] as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        $data['whatsapp_notifications_enabled'] = $request->boolean('whatsapp_notifications_enabled');
        $data['whatsapp_notify_cobranca'] = $request->boolean('whatsapp_notify_cobranca');
        $data['whatsapp_notify_faturas_boleto'] = $request->boolean('whatsapp_notify_faturas_boleto');
        $data['whatsapp_notify_avisos'] = $request->boolean('whatsapp_notify_avisos');

        $clinic->update($data);
        \Illuminate\Support\Facades\Event::dispatch(new AuditEvent('clinic.updated', Clinic::class, $clinic->id, null, $clinic->id, $request->user()?->id));

        return response()->json([
            'data' => new ClinicResource($clinic->fresh()),
        ]);
    }

}
