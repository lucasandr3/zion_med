<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkBioLinkClick extends Model
{
    protected $table = 'link_bio_link_clicks';

    protected $fillable = [
        'clinic_link_id',
        'date',
        'clicks',
    ];

    protected $casts = [
        'date'   => 'date',
        'clicks' => 'integer',
    ];

    public function clinicLink(): BelongsTo
    {
        return $this->belongsTo(ClinicLink::class);
    }

    /**
     * Incrementa o contador de cliques para o link na data dada (hoje por padrão).
     */
    public static function incrementForLink(int $clinicLinkId, ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        $row  = static::query()->firstOrCreate(
            ['clinic_link_id' => $clinicLinkId, 'date' => $date],
            ['clicks' => 0]
        );
        $row->increment('clicks');
    }
}
