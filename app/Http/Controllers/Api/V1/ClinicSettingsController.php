<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Events\AuditEvent;
use App\Http\Requests\ClinicSettingsRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use App\Models\OrganizationSlugAlias;
use App\Services\AsaasService;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ClinicSettingsController extends Controller
{
    use ResolvesOrganizationContext;

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

        $organizationId = $this->currentOrganizationId($request);
        $organization = Organization::query()->findOrFail($organizationId);
        $organization->load('addressData');

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
            'billing_type' => $s->billing_type ?? 'BOLETO',
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
            'pix_qr_encoded_image' => $p->pix_qr_encoded_image,
            'pix_copy_paste' => $p->pix_copy_paste,
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
                'available_public_themes' => array_merge(
                    $this->themeService->getAvailableThemes(),
                    $this->themeService->getPublicOnlyThemes(),
                ),
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
        $organizationId = $this->currentOrganizationId($request);
        $organization = Organization::query()->findOrFail($organizationId);
        $organization->load('addressData');
        $this->authorize('update-clinic', $organization);
        $data = $request->validated();

        if ($request->boolean('dark_mode_only')) {
            $organization->update(['dark_mode' => $request->boolean('dark_mode')]);
            return response()->json(['data' => new OrganizationResource($organization->fresh())]);
        }

        if ($request->boolean('theme_only')) {
            $theme = $this->themeService->normalizeThemeValue($request->input('theme'));
            $organization->update(['theme' => $theme]);

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

        $addressDataInput = null;
        if (array_key_exists('address_data', $data)) {
            $addressDataInput = is_array($data['address_data']) ? $data['address_data'] : [];
            unset($data['address_data']);
        }

        foreach (['phone', 'contact_email', 'short_description', 'specialties', 'founded_year', 'meta_description', 'maps_url', 'billing_name', 'billing_email', 'billing_document', 'public_theme', 'form_public_theme', 'cover_color', 'cover_mode', 'signing_security_level'] as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        if (array_key_exists('theme', $data)) {
            $data['theme'] = $this->themeService->normalizeThemeValue($data['theme']);
        }
        if (array_key_exists('public_theme', $data)) {
            $data['public_theme'] = $this->themeService->normalizeThemeValue($data['public_theme']);
        }
        if (array_key_exists('form_public_theme', $data)) {
            $raw = trim((string) ($data['form_public_theme'] ?? ''));
            $data['form_public_theme'] = $raw === '' ? null : $this->themeService->normalizePublicThemeValue($raw);
        }
        if (array_key_exists('form_accent_hex', $data)) {
            $resolvedFormTheme = $data['form_public_theme'] ?? $organization->form_public_theme;
            if ($resolvedFormTheme !== 'custom') {
                $data['form_accent_hex'] = null;
            } elseif ($data['form_accent_hex'] !== null) {
                $data['form_accent_hex'] = strtolower((string) $data['form_accent_hex']);
            }
        }
        if (array_key_exists('hide_platform_branding', $data)) {
            $data['hide_platform_branding'] = $request->boolean('hide_platform_branding');
        }

        $data['whatsapp_notifications_enabled'] = $request->boolean('whatsapp_notifications_enabled');
        $data['whatsapp_notify_cobranca'] = $request->boolean('whatsapp_notify_cobranca');
        $data['whatsapp_notify_faturas_boleto'] = $request->boolean('whatsapp_notify_faturas_boleto');
        $data['whatsapp_notify_avisos'] = $request->boolean('whatsapp_notify_avisos');

        if ($request->has('data_retention_years')) {
            $raw = $request->input('data_retention_years');
            $data['data_retention_years'] = $raw === null || $raw === '' ? null : (int) $raw;
        }

        $slugAntes = (string) $organization->slug;
        $slugPublicoMudou = false;
        if (array_key_exists('name', $data)) {
            $novoNome = trim((string) $data['name']);
            if ($novoNome !== '' && $novoNome !== (string) $organization->name) {
                $novoSlug = Organization::generateUniqueSlug($novoNome, (int) $organization->id);
                if ($novoSlug !== $slugAntes) {
                    $data['slug'] = $novoSlug;
                    $slugPublicoMudou = true;
                }
            }
        }

        $organization->update($data);

        if ($slugPublicoMudou && $slugAntes !== '') {
            OrganizationSlugAlias::query()->updateOrCreate(
                ['slug' => $slugAntes],
                ['organization_id' => (int) $organization->id],
            );
        }

        if ($addressDataInput !== null) {
            $cleanAddressData = $this->normalizeAddressData($addressDataInput);
            $hasAddressData = collect($cleanAddressData)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
            if ($hasAddressData) {
                $organization->addressData()->updateOrCreate(
                    ['organization_id' => $organization->id],
                    $cleanAddressData
                );
                if (! array_key_exists('address', $data) || ! filled((string) Arr::get($data, 'address'))) {
                    $organization->forceFill([
                        'address' => $this->composeLegacyAddress($cleanAddressData),
                    ])->save();
                }
            } else {
                $organization->addressData()->delete();
            }
        }

        \Illuminate\Support\Facades\Event::dispatch(new AuditEvent('clinic.updated', Organization::class, $organization->id, null, $organization->id, $request->user()?->id));

        return response()->json([
            'data' => new OrganizationResource($organization->fresh()->load('addressData')),
        ]);
    }

    /**
     * @param array<string, mixed> $addressData
     * @return array<string, string|null>
     */
    private function normalizeAddressData(array $addressData): array
    {
        $cep = preg_replace('/\D+/', '', (string) ($addressData['cep'] ?? '')) ?: null;

        return [
            'cep' => $cep,
            'logradouro' => $this->normalizeNullableString($addressData['logradouro'] ?? null),
            'numero' => $this->normalizeNullableString($addressData['numero'] ?? null),
            'complemento' => $this->normalizeNullableString($addressData['complemento'] ?? null),
            'bairro' => $this->normalizeNullableString($addressData['bairro'] ?? null),
            'cidade' => $this->normalizeNullableString($addressData['cidade'] ?? null),
            'uf' => $this->normalizeNullableString($addressData['uf'] ?? null, true),
        ];
    }

    private function normalizeNullableString(mixed $value, bool $uppercase = false): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return $uppercase ? mb_strtoupper($text) : $text;
    }

    /**
     * @param array<string, string|null> $addressData
     */
    private function composeLegacyAddress(array $addressData): ?string
    {
        $logradouroNumero = collect([
            $addressData['logradouro'] ?? null,
            $addressData['numero'] ?? null,
        ])->filter()->implode(', ');
        $local = collect([
            $addressData['bairro'] ?? null,
            $addressData['cidade'] ?? null,
            $addressData['uf'] ?? null,
        ])->filter()->implode(' - ');
        $base = collect([
            $logradouroNumero !== '' ? $logradouroNumero : null,
            $addressData['complemento'] ?? null,
            $local !== '' ? $local : null,
        ])->filter()->implode(' - ');
        $final = collect([
            $base !== '' ? $base : null,
            $addressData['cep'] ?? null,
        ])->filter()->implode(' - ');

        return $final !== '' ? $final : null;
    }

}
