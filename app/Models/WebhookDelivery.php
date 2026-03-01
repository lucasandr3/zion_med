<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'clinic_webhook_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'attempt',
        'delivered_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function clinicWebhook(): BelongsTo
    {
        return $this->belongsTo(ClinicWebhook::class);
    }
}
