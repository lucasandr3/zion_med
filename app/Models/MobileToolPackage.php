<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileToolPackage extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'person_id',
        'client_name',
        'label',
        'total_sessions',
        'done_sessions',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_sessions' => 'integer',
            'done_sessions' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
