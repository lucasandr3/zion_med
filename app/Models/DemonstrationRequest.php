<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemonstrationRequest extends Model
{
    protected $table = 'demonstration_requests';

    protected $fillable = [
        'name',
        'clinic',
        'email',
        'phone',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
