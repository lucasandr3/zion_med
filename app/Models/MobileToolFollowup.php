<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileToolFollowup extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'person_id',
        'client_name',
        'phone',
        'detail',
        'due_on',
        'status',
        'contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'contacted_at' => 'datetime',
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
