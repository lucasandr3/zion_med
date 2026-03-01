<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkBioPageView extends Model
{
    protected $table = 'link_bio_page_views';

    protected $fillable = [
        'clinic_id',
        'date',
        'views',
    ];

    protected $casts = [
        'date'  => 'date',
        'views' => 'integer',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Incrementa o contador de views para a clínica na data dada (hoje por padrão).
     */
    public static function incrementForClinic(int $clinicId, ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        $row  = static::query()->firstOrCreate(
            ['clinic_id' => $clinicId, 'date' => $date],
            ['views' => 0]
        );
        $row->increment('views');
    }
}
