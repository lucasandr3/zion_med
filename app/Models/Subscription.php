<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id',
        'asaas_subscription_id',
        'plan_key',
        'billing_type',
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
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Assinaturas exibidas no app (billing / configurações): sem canceladas no Asaas ou localmente.
     */
    public function scopeForTenantBillingListing(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['CANCELED', 'canceled', 'DELETED', 'inactive']);
    }

    /**
     * Remove cobranças locais ainda não confirmadas (ex.: após cancelar assinatura no Asaas).
     */
    public function deleteUnpaidLocalPayments(): int
    {
        return $this->payments()
            ->whereNotIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
            ->delete();
    }
}
