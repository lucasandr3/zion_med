<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'meta_json',
    ];

    public function getClinicIdAttribute(): ?int
    {
        return $this->attributes['organization_id'] ?? null;
    }

    public function setClinicIdAttribute($value): void
    {
        $this->attributes['organization_id'] = $value;
    }

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @deprecated Use organization(). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
