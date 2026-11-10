<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFeedback extends Model
{
    protected $table = 'product_feedback';

    protected $fillable = [
        'user_id',
        'organization_id',
        'type',
        'title',
        'message',
        'source',
        'page_url',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
