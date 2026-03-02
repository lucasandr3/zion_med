<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'clinic_id',
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

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);
    }
}
