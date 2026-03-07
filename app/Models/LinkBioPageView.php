<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkBioPageView extends Model
{
    protected $table = 'link_bio_page_views';

    protected $fillable = [
        'organization_id',
        'date',
        'views',
    ];

    protected $casts = [
        'date'  => 'date',
        'views' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @deprecated Use organization(). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'organization_id');
    }

    /**
     * Incrementa o contador de views para a organização na data dada (hoje por padrão).
     */
    public static function incrementForClinic(int $clinicId, ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        $row  = static::query()->firstOrCreate(
            ['organization_id' => $clinicId, 'date' => $date],
            ['views' => 0]
        );
        $row->increment('views');
    }
}
