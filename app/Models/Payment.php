<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'organization_id',
        'subscription_id',
        'asaas_payment_id',
        'status',
        'due_date',
        'paid_at',
        'value',
        'bank_slip_url',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime',
        'value'    => 'decimal:2',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Cobranças exibidas no app: ignora vínculos com assinatura cancelada/inativa (evita duplicata após novo checkout).
     */
    public function scopeVisibleOnTenantBilling(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('subscription_id')
                ->orWhereHas('subscription', fn (Builder $sq) => $sq->forTenantBillingListing());
        });
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);
    }
}
