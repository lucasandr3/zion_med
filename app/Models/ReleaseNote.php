<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseNote extends Model
{
    protected $fillable = [
        'version',
        'title',
        'summary',
        'items',
        'released_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'released_at' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
