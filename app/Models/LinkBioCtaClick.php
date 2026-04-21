<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkBioCtaClick extends Model
{
    protected $table = 'link_bio_cta_clicks';

    protected $fillable = [
        'organization_id',
        'channel',
        'ref',
        'date',
        'clicks',
    ];

    protected $casts = [
        'date'   => 'date',
        'clicks' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param  string  $ref  vazio para CTAs globais; índice da equipe (ex. "0") para team_whatsapp
     */
    public static function incrementFor(int $organizationId, string $channel, string $ref = '', ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        $ref  = $ref === '' ? '' : (string) $ref;

        $row = static::query()->firstOrCreate(
            [
                'organization_id' => $organizationId,
                'channel'         => $channel,
                'ref'             => $ref,
                'date'            => $date,
            ],
            ['clicks' => 0]
        );
        $row->increment('clicks');
    }
}
