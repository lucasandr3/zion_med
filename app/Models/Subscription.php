<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id',
        'asaas_subscription_id',
        'plan_key',
        'status',
        'current_period_end',
        'next_due_date',
    ];

    protected $casts = [
        'current_period_end' => 'date',
        'next_due_date'      => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @deprecated Use organization(). Alias para compatibilidade (Clinic = Organization). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'organization_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
