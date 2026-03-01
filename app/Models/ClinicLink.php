<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicLink extends Model
{
    protected $fillable = [
        'clinic_id',
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

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function linkClicks(): HasMany
    {
        return $this->hasMany(LinkBioLinkClick::class);
    }
}
