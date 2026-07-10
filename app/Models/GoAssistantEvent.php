<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoAssistantEvent extends Model
{
    public const KIND_SCREEN = 'screen';
    public const KIND_INTENT = 'intent';
    public const KIND_SEARCH = 'search';
    public const KIND_ACTION = 'action';
    public const KIND_TOUR = 'tour';
    public const KIND_NO_MATCH = 'no_match';

    public const KINDS = [
        self::KIND_SCREEN,
        self::KIND_INTENT,
        self::KIND_SEARCH,
        self::KIND_ACTION,
        self::KIND_TOUR,
        self::KIND_NO_MATCH,
    ];

    protected $fillable = [
        'user_id',
        'organization_id',
        'kind',
        'screen_id',
        'intent_id',
        'route',
        'title',
        'query',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
