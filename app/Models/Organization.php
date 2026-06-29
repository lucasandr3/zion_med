<?php

namespace App\Models;

use App\Services\ThemeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $table = 'organizations';

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if ($organization->theme === null || $organization->theme === '') {
                $organization->theme = ThemeService::DEFAULT_THEME;
            }
        });

        static::created(function (Organization $organization): void {
            OrganizationRole::seedDefaultsForOrganization((int) $organization->id);
        });
    }

    /**
     * Gera um slug único a partir de um texto base, garantindo que não conflita
     * com nenhuma outra organização nem com slugs reservados em aliases (ignora
     * $excludeId na tabela organizations ao regerar o slug da própria org).
     *
     * Fallback para "empresa" quando o nome só contém caracteres especiais.
     */
    public static function generateUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'empresa';
        }
        $candidate = $slug;
        $n = 1;
        while (static::query()
            ->where('slug', $candidate)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
            || OrganizationSlugAlias::query()->where('slug', $candidate)->exists()
        ) {
            $candidate = $slug.'-'.$n;
            $n++;
        }

        return $candidate;
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'niche',
        'logo_path',
        'professional_photo_path',
        'notification_email',
        'address',
        'phone',
        'contact_email',
        'short_description',
        'specialties',
        'founded_year',
        'meta_description',
        'cover_image_path',
        'cover_mode',
        'link_bio_model',
        'link_bio_extra',
        'public_theme',
        'accent_hex',
        'cover_color',
        'maps_url',
        'google_place_id',
        'google_reviews_enabled',
        'business_hours',
        'theme',
        'dark_mode',
        'asaas_customer_id',
        'billing_email',
        'billing_name',
        'billing_document',
        'plan_key',
        'trial_ends_at',
        'grace_ends_at',
        'subscription_status',
        'billing_status',
        'whatsapp_notifications_enabled',
        'whatsapp_notify_cobranca',
        'whatsapp_notify_faturas_boleto',
        'whatsapp_notify_avisos',
        'signing_security_level',
        'data_retention_years',
        'evolution_go_instance_name',
        'evolution_go_remote_id',
        'evolution_go_instance_token',
        'feegow_enabled',
        'feegow_base_url',
        'feegow_token',
        'feegow_last_check_at',
        'feegow_last_status',
        'feegow_last_error',
    ];

    protected $casts = [
        'dark_mode'      => 'boolean',
        'whatsapp_notifications_enabled' => 'boolean',
        'whatsapp_notify_cobranca' => 'boolean',
        'whatsapp_notify_faturas_boleto' => 'boolean',
        'whatsapp_notify_avisos' => 'boolean',
        'feegow_enabled' => 'boolean',
        'business_hours' => 'array',
        'link_bio_extra' => 'array',
        'google_reviews_enabled' => 'boolean',
        'trial_ends_at'   => 'datetime',
        'grace_ends_at'   => 'datetime',
        'evolution_go_instance_token' => 'encrypted',
        'feegow_token' => 'encrypted',
        'feegow_last_check_at' => 'datetime',
    ];

    public function isOpenNow(): ?bool
    {
        $hours = $this->business_hours;
        if (empty($hours) || ! is_array($hours)) {
            return null;
        }

        $day = (int) now()->format('N');
        $slot = $hours[$day] ?? $hours[(string) $day] ?? null;
        if (! $slot || empty($slot['open']) || empty($slot['close'])) {
            return false;
        }

        $now  = now()->format('H:i');
        $open = $slot['open'];
        $close = $slot['close'];

        if ($open <= $close) {
            return $now >= $open && $now <= $close;
        }
        return $now >= $open || $now <= $close;
    }

    public function getBusinessHoursFormatted(): ?string
    {
        $hours = $this->business_hours;
        if (empty($hours) || ! is_array($hours)) {
            return null;
        }

        $days = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom'];
        $slots = [];
        foreach ($days as $d => $label) {
            $slot = $hours[$d] ?? null;
            if ($slot && ! empty($slot['open']) && ! empty($slot['close'])) {
                $slots[$d] = $label . ' ' . $slot['open'] . '-' . $slot['close'];
            }
        }
        if (empty($slots)) {
            return null;
        }

        return implode(' · ', $slots);
    }

    public function getBusinessHoursGrid(): array
    {
        $hours = $this->business_hours;
        $days  = ['1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb', '7' => 'Dom'];
        $grid  = [];
        foreach ($days as $d => $label) {
            $slot = $hours[$d] ?? null;
            $text = '–';
            if ($slot && ! empty($slot['open']) && ! empty($slot['close'])) {
                $open  = substr($slot['open'], 0, 5);
                $close = substr($slot['close'], 0, 5);
                $text  = preg_replace('/:00$/', '', $open) . '–' . preg_replace('/:00$/', '', $close);
                $text  = str_replace(':', 'h', $text);
            }
            $grid[$d] = ['label' => $label, 'text' => $text];
        }
        return $grid;
    }

    public function getMapsUrl(): ?string
    {
        if ($this->maps_url) {
            return $this->maps_url;
        }
        if ($this->address) {
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($this->address);
        }
        return null;
    }

    /** URL assinada (15 min) para a logo no MinIO. Fallback para storage público se path antigo. */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }
        if (Storage::disk('minio_assets')->exists($this->logo_path)) {
            return Storage::disk('minio_assets')->temporaryUrl($this->logo_path, now()->addMinutes(15));
        }
        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->logo_path, '/');
    }

    /** URL assinada (15 min) para a capa no MinIO. Fallback para storage público se path antigo. */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }
        if (Storage::disk('minio_assets')->exists($this->cover_image_path)) {
            return Storage::disk('minio_assets')->temporaryUrl($this->cover_image_path, now()->addMinutes(15));
        }
        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->cover_image_path, '/');
    }

    /** Foto do profissional no Link Bio (avatar grande), separada da logo da clínica. */
    public function getProfessionalPhotoUrlAttribute(): ?string
    {
        if (! $this->professional_photo_path) {
            return null;
        }
        if (Storage::disk('minio_assets')->exists($this->professional_photo_path)) {
            return Storage::disk('minio_assets')->temporaryUrl($this->professional_photo_path, now()->addMinutes(15));
        }

        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->professional_photo_path, '/');
    }

    public function getSpecialtiesList(): array
    {
        if (empty($this->specialties)) {
            return [];
        }
        return array_map('trim', explode(',', $this->specialties));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Slugs antigos da página pública (Link Bio) que redirecionam para o slug atual. */
    public function slugAliases(): HasMany
    {
        return $this->hasMany(OrganizationSlugAlias::class, 'organization_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function addressData(): HasOne
    {
        return $this->hasOne(OrganizationAddress::class, 'organization_id');
    }

    public function organizationRoles(): HasMany
    {
        return $this->hasMany(OrganizationRole::class, 'organization_id');
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class, 'organization_id');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'organization_id');
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'organization_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'organization_id');
    }

    public function bioLinks(): HasMany
    {
        return $this->hasMany(ClinicLink::class, 'organization_id')->orderBy('sort_order');
    }

    /** @deprecated Use organization() - alias para compatibilidade de views. */
    public function getClinicAttribute(): self
    {
        return $this;
    }

    public function linkBioPageViews(): HasMany
    {
        return $this->hasMany(LinkBioPageView::class, 'organization_id');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(ClinicWebhook::class, 'organization_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'organization_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'organization_id');
    }

    /**
     * Último dia do trial conta inteiro (até 23:59:59 do dia de trial_ends_at).
     */
    public function isTrialWindowOpen(): bool
    {
        if (! $this->trial_ends_at) {
            return false;
        }

        return now()->lte($this->trial_ends_at->copy()->endOfDay());
    }

    public function hasConfirmedBillingPayment(): bool
    {
        return $this->payments()
            ->whereIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
            ->exists();
    }

    /**
     * Registro local de assinatura no Asaas ainda marcado como ativo (não cancelado).
     */
    public function hasActiveGatewaySubscription(): bool
    {
        return $this->subscriptions()
            ->whereNotNull('asaas_subscription_id')
            ->whereIn('status', ['active', 'ACTIVE'])
            ->exists();
    }

    /**
     * Trial já encerrado, existe assinatura no gateway, mas nenhum pagamento confirmado (acesso bloqueado até pagar ou refazer fluxo).
     */
    public function isAwaitingFirstBillingPayment(): bool
    {
        if ($this->hasConfirmedBillingPayment()) {
            return false;
        }
        if (! $this->hasActiveGatewaySubscription()) {
            return false;
        }
        if ($this->isTrialWindowOpen()) {
            return false;
        }

        return true;
    }

    /**
     * UI: cartão “assinatura ativa” com próxima cobrança / cancelar — não usar no estado “só boleto pendente pós-trial”.
     */
    public function billingUiShowsManagedActiveSubscription(): bool
    {
        if ($this->isAwaitingFirstBillingPayment()) {
            return false;
        }

        return $this->hasActiveGatewaySubscription();
    }

    /**
     * UI: exibir planos e ação de assinar (inclui refazer checkout após trial com assinatura Asaas ainda pendente).
     */
    public function billingUiShowsPlanSelection(): bool
    {
        if ($this->billingUiShowsManagedActiveSubscription()) {
            return false;
        }

        return ! $this->hasActiveGatewaySubscription() || $this->isAwaitingFirstBillingPayment();
    }

    /**
     * Estado para a aba Assinatura no SPA (evita misturar "inativo" da org com assinatura Asaas ainda pendente).
     *
     * @return array{
     *     show_managed_subscription_card: bool,
     *     show_pending_first_payment: bool,
     *     show_plan_selection: bool,
     *     pending_first_payment_message: string|null
     * }
     */
    public function billingUiState(): array
    {
        $pending = $this->isAwaitingFirstBillingPayment();

        return [
            'show_managed_subscription_card' => $this->billingUiShowsManagedActiveSubscription(),
            'show_pending_first_payment' => $pending,
            'show_plan_selection' => $this->billingUiShowsPlanSelection(),
            'pending_first_payment_message' => $pending
                ? 'Seu trial encerrou e a assinatura ainda não teve pagamento confirmado. Pague via PIX ou boleto abaixo, ou assine novamente para gerar uma nova cobrança.'
                : null,
        ];
    }

    /**
     * Dias corridos até o último dia do trial (0 = encerra hoje). Só faz sentido com trial ainda aberto.
     */
    public function trialCalendarDaysRemaining(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }
        $today = now()->copy()->startOfDay();
        $lastTrialDay = $this->trial_ends_at->copy()->startOfDay();

        return max(0, (int) $today->diffInDays($lastTrialDay, false));
    }

    /**
     * Aviso para o front (trial acabando, sem pagamento confirmado ainda).
     *
     * @return array{visible: true, days_remaining: int, trial_ends_at: string, message: string}|null
     */
    public function trialEndingNoticeMeta(): ?array
    {
        if (! $this->trial_ends_at || ! $this->isTrialWindowOpen()) {
            return null;
        }
        if ($this->hasConfirmedBillingPayment()) {
            return null;
        }
        $threshold = (int) config('asaas.trial_warning_days', 3);
        $daysLeft = $this->trialCalendarDaysRemaining();
        if ($daysLeft > $threshold) {
            return null;
        }

        $message = $daysLeft === 0
            ? 'Seu período de trial encerra hoje. Acesse Assinatura para evitar a suspensão do acesso.'
            : sprintf(
                'Seu trial encerra em %d %s. Acesse Assinatura para evitar a suspensão do acesso.',
                $daysLeft,
                $daysLeft === 1 ? 'dia' : 'dias'
            );

        return [
            'visible' => true,
            'days_remaining' => $daysLeft,
            'trial_ends_at' => $this->trial_ends_at->toIso8601String(),
            'message' => $message,
        ];
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at !== null
            && $this->isTrialWindowOpen();
    }

    /**
     * Pode usar o app (dashboard, protocolos, etc.): trial válido, ou pagamento confirmado após o trial, ou assinatura ativa sem trial configurado.
     */
    public function canAccessTenantAppFeatures(): bool
    {
        if ($this->isBillingBlocked()) {
            return false;
        }

        if ($this->isAwaitingFirstBillingPayment()) {
            return false;
        }

        if ($this->subscription_status === 'past_due') {
            if ($this->grace_ends_at && now()->lte($this->grace_ends_at)) {
                return true;
            }
            if ($this->grace_ends_at && now()->gt($this->grace_ends_at) && $this->billing_status !== 'blocked') {
                $this->forceFill(['billing_status' => 'blocked'])->save();
            }

            return false;
        }

        if ($this->trial_ends_at !== null) {
            if ($this->isTrialWindowOpen()) {
                return true;
            }

            return $this->hasConfirmedBillingPayment();
        }

        return $this->subscription_status === 'active'
            && in_array((string) $this->billing_status, ['ok', 'attention'], true);
    }

    public function isPastDueInGrace(): bool
    {
        if ($this->subscription_status !== 'past_due' || ! $this->grace_ends_at) {
            return false;
        }
        return now()->lte($this->grace_ends_at);
    }

    public function isBillingBlocked(): bool
    {
        return $this->billing_status === 'blocked';
    }

    /**
     * Definição do plano atual (nome, valor, limites) a partir de config('asaas.plans') já mesclado com o banco.
     *
     * @return array<string, mixed>
     */
    public function planDefinition(): array
    {
        $key = $this->plan_key;
        $plans = config('asaas.plans', []);
        if ($key !== null && $key !== '' && isset($plans[$key]) && is_array($plans[$key])) {
            return $plans[$key];
        }

        return [];
    }

    public function planMaxUsers(): ?int
    {
        $v = $this->planDefinition()['max_users'] ?? null;

        return $v === null ? null : (int) $v;
    }

    public function planMaxOrganizationsPerTenant(): ?int
    {
        $v = $this->planDefinition()['max_organizations_per_tenant'] ?? null;

        return $v === null ? null : (int) $v;
    }

    public function organizationsInTenantCount(): int
    {
        if (! $this->tenant_id) {
            return 1;
        }

        return (int) self::withoutGlobalScopes()->where('tenant_id', $this->tenant_id)->count();
    }

    public function canAddAnotherUser(): bool
    {
        $max = $this->planMaxUsers();
        if ($max === null) {
            return true;
        }

        return $this->users()->count() < $max;
    }

    public function canAddOrganizationInTenant(): bool
    {
        $max = $this->planMaxOrganizationsPerTenant();
        if ($max === null) {
            return true;
        }

        return $this->organizationsInTenantCount() < $max;
    }

    /**
     * @param  array<string, mixed>  $planConfig
     */
    public function meetsLimitsForPlanConfig(array $planConfig): bool
    {
        $maxUsers = $planConfig['max_users'] ?? null;
        if ($maxUsers !== null && $this->users()->count() > (int) $maxUsers) {
            return false;
        }
        $maxOrgs = $planConfig['max_organizations_per_tenant'] ?? null;
        if ($maxOrgs !== null && $this->organizationsInTenantCount() > (int) $maxOrgs) {
            return false;
        }

        return true;
    }

    /**
     * @return array{
     *     plan_key: string|null,
     *     max_users: int|null,
     *     max_organizations_per_tenant: int|null,
     *     users_count: int,
     *     organizations_in_tenant: int,
     *     can_add_user: bool,
     *     can_add_organization_in_tenant: bool
     * }
     */
    public function planLimitsForApi(): array
    {
        $def = $this->planDefinition();
        $maxUsers = $def['max_users'] ?? null;
        $maxOrgs = $def['max_organizations_per_tenant'] ?? null;

        return [
            'plan_key' => $this->plan_key,
            'max_users' => $maxUsers !== null ? (int) $maxUsers : null,
            'max_organizations_per_tenant' => $maxOrgs !== null ? (int) $maxOrgs : null,
            'users_count' => $this->users()->count(),
            'organizations_in_tenant' => $this->organizationsInTenantCount(),
            'can_add_user' => $this->canAddAnotherUser(),
            'can_add_organization_in_tenant' => $this->canAddOrganizationInTenant(),
        ];
    }

    /**
     * Alinha status local com o fim do trial: sem pagamento confirmado → inativo/bloqueado (mesmo com assinatura Asaas pendente).
     */
    public function syncExpiredTrialStateIfNeeded(): void
    {
        if (! $this->trial_ends_at) {
            return;
        }

        if ($this->isTrialWindowOpen()) {
            return;
        }

        if ($this->hasConfirmedBillingPayment()) {
            if (in_array($this->subscription_status, ['trial', 'inactive'], true)) {
                $this->forceFill([
                    'subscription_status' => 'active',
                    'billing_status' => 'ok',
                ])->save();
            }

            return;
        }

        $this->forceFill([
            'subscription_status' => 'inactive',
            'billing_status' => 'blocked',
        ])->save();
    }
}
