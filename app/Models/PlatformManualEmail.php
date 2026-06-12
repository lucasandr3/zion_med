<?php

namespace App\Models;

use App\Enums\PlatformManualEmailCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformManualEmail extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'tenant_id',
        'organization_id',
        'lead_id',
        'meta_json',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => PlatformManualEmailCategory::class,
            'meta_json' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(DemonstrationRequest::class, 'lead_id');
    }
}
