<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsaasWebhookEvent extends Model
{
    protected $fillable = [
        'event_key',
        'event_type',
        'asaas_resource_id',
        'organization_id',
        'status',
        'payload',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
