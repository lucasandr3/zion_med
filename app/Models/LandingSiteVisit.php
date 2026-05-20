<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSiteVisit extends Model
{
    protected $table = 'landing_site_visits';

    protected $fillable = [
        'ip_hash',
        'visit_date',
        'path',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];
}
