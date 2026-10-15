<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AppointmentService extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'duration_minutes',
        'price',
        'show_price',
        'photo_path',
        'form_template_id',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected $hidden = [
        'photo_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_price' => 'boolean',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }
        if (Storage::disk('minio_assets')->exists($this->photo_path)) {
            return Storage::disk('minio_assets')->temporaryUrl($this->photo_path, now()->addMinutes(60));
        }

        return rtrim((string) config('app.url'), '/').'/storage/'.ltrim($this->photo_path, '/');
    }
}
