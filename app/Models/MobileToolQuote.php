<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileToolQuote extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'person_id',
        'client_name',
        'phone',
        'procedure',
        'sessions',
        'unit_price',
        'discount',
        'total',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'sessions' => 'integer',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
