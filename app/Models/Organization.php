<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    protected $table = 'organizations';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'logo_path',
        'notification_email',
        'address',
        'phone',
        'contact_email',
        'short_description',
        'specialties',
        'founded_year',
        'meta_description',
        'cover_image_path',
        'public_theme',
        'cover_color',
        'maps_url',
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
    ];

    protected $casts = [
        'dark_mode'      => 'boolean',
        'whatsapp_notifications_enabled' => 'boolean',
        'whatsapp_notify_cobranca' => 'boolean',
        'whatsapp_notify_faturas_boleto' => 'boolean',
        'whatsapp_notify_avisos' => 'boolean',
        'business_hours' => 'array',
        'trial_ends_at'   => 'datetime',
        'grace_ends_at'   => 'datetime',
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class, 'organization_id');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'organization_id');
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

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' && $this->trial_ends_at && now()->lte($this->trial_ends_at);
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
}
