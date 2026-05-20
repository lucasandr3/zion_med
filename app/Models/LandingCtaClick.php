<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingCtaClick extends Model
{
    protected $table = 'landing_cta_clicks';

    protected $fillable = [
        'channel',
        'date',
        'clicks',
    ];

    protected $casts = [
        'date'   => 'date',
        'clicks' => 'integer',
    ];

    public static function incrementFor(string $channel, ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        $channel = mb_substr(trim($channel), 0, 80);
        if ($channel === '') {
            return;
        }

        $row = static::query()->firstOrCreate(
            ['channel' => $channel, 'date' => $date],
            ['clicks' => 0]
        );
        $row->increment('clicks');
    }
}
