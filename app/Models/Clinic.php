<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $fillable = [
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
        'maps_url',
        'business_hours',
        'theme',
        'dark_mode',
    ];

    protected $casts = [
        'dark_mode'      => 'boolean',
        'business_hours' => 'array',
    ];

    /**
     * Dias da semana: 1=Segunda a 7=Domingo (date('N')).
     * business_hours: {"1":{"open":"08:00","close":"18:00"},"2":{...},"7":null}
     */
    public function isOpenNow(): ?bool
    {
        $hours = $this->business_hours;
        if (empty($hours) || ! is_array($hours)) {
            return null;
        }

        $day = (int) now()->format('N'); // 1=Mon .. 7=Sun
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
        // Horário que passa da meia-noite (ex: 22:00 - 02:00)
        return $now >= $open || $now <= $close;
    }

    /**
     * Retorna o horário formatado para exibição (ex: "Seg-Sex 08:00-18:00").
     */
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

    /**
     * Retorna array para grid de horários: ['1'=>['label'=>'Seg','text'=>'08–18'], ...]
     */
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

    public function getSpecialtiesList(): array
    {
        if (empty($this->specialties)) {
            return [];
        }
        return array_map('trim', explode(',', $this->specialties));
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function bioLinks(): HasMany
    {
        return $this->hasMany(ClinicLink::class)->orderBy('sort_order');
    }

    public function linkBioPageViews(): HasMany
    {
        return $this->hasMany(LinkBioPageView::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(ClinicWebhook::class);
    }
}
