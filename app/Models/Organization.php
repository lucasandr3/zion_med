<?php

namespace App\Models;

use App\Services\Billing\BillingStateService;
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
        'form_public_theme',
        'form_accent_hex',
        'hide_platform_branding',
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
        'hide_platform_branding' => 'boolean',
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
        return $this->billingState()->isTrialWindowOpen($this);
    }

    public function hasConfirmedBillingPayment(): bool
    {
        return $this->billingState()->hasConfirmedBillingPayment($this);
    }

    public function hasActiveGatewaySubscription(): bool
    {
        return $this->billingState()->hasActiveGatewaySubscription($this);
    }

    public function isAwaitingFirstBillingPayment(): bool
    {
        return $this->billingState()->isAwaitingFirstBillingPayment($this);
    }

    public function billingUiShowsManagedActiveSubscription(): bool
    {
        return $this->billingState()->billingUiShowsManagedActiveSubscription($this);
    }

    public function billingUiShowsPlanSelection(): bool
    {
        return $this->billingState()->billingUiShowsPlanSelection($this);
    }

    /**
     * @return array{
     *     show_managed_subscription_card: bool,
     *     show_pending_first_payment: bool,
     *     show_plan_selection: bool,
     *     pending_first_payment_message: string|null
     * }
     */
    public function billingUiState(): array
    {
        return $this->billingState()->billingUiState($this);
    }

    public function trialCalendarDaysRemaining(): int
    {
        return $this->billingState()->trialCalendarDaysRemaining($this);
    }

    /**
     * @return array{visible: true, days_remaining: int, trial_ends_at: string, message: string}|null
     */
    public function trialEndingNoticeMeta(): ?array
    {
        return $this->billingState()->trialEndingNoticeMeta($this);
    }

    public function isOnTrial(): bool
    {
        return $this->billingState()->isOnTrial($this);
    }

    public function canAccessTenantAppFeatures(): bool
    {
        return $this->billingState()->canAccessTenantAppFeatures($this);
    }

    public function isPastDueInGrace(): bool
    {
        return $this->billingState()->isPastDueInGrace($this);
    }

    public function isBillingBlocked(): bool
    {
        return $this->billingState()->isBillingBlocked($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function planDefinition(): array
    {
        return $this->billingState()->planDefinition($this);
    }

    public function planMaxUsers(): ?int
    {
        return $this->billingState()->planMaxUsers($this);
    }

    public function planMaxOrganizationsPerTenant(): ?int
    {
        return $this->billingState()->planMaxOrganizationsPerTenant($this);
    }

    public function organizationsInTenantCount(): int
    {
        return $this->billingState()->organizationsInTenantCount($this);
    }

    public function canAddAnotherUser(): bool
    {
        return $this->billingState()->canAddAnotherUser($this);
    }

    public function canAddOrganizationInTenant(): bool
    {
        return $this->billingState()->canAddOrganizationInTenant($this);
    }

    /**
     * @param  array<string, mixed>  $planConfig
     */
    public function meetsLimitsForPlanConfig(array $planConfig): bool
    {
        return $this->billingState()->meetsLimitsForPlanConfig($this, $planConfig);
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
        return $this->billingState()->planLimitsForApi($this);
    }

    public function syncExpiredTrialStateIfNeeded(): void
    {
        $this->billingState()->syncExpiredTrialStateIfNeeded($this);
    }

    protected function billingState(): BillingStateService
    {
        return app(BillingStateService::class);
    }
}
