<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicLink extends Model
{
    protected $fillable = [
        'organization_id',
        'label',
        'url',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public static function availableIcons(): array
    {
        return [
            'link'           => 'Link genérico',
            'phone'          => 'Telefone',
            'chat'           => 'WhatsApp / Chat',
            'photo_camera'   => 'Instagram',
            'calendar_today' => 'Agendamento',
            'location_on'    => 'Endereço',
            'mail'           => 'E-mail',
            'public'         => 'Site',
            'videocam'       => 'Video / Telemedicina',
            'star'           => 'Destaque',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @deprecated Use organization(). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'organization_id');
    }

    public function getClinicIdAttribute(): ?int
    {
        return $this->attributes['organization_id'] ?? null;
    }

    public function setClinicIdAttribute($value): void
    {
        $this->attributes['organization_id'] = $value;
    }

    public function linkClicks(): HasMany
    {
        return $this->hasMany(LinkBioLinkClick::class);
    }
}
