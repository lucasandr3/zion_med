<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubmissionSignature extends Model
{
    protected $fillable = [
        'submission_id',
        'image_path',
        'field_key',
        'signed_name',
        'signed_ip',
        'signed_user_agent',
        'signed_hash',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function getUrlAttribute(): string
    {
        if (Storage::disk('minio_submissions')->exists($this->image_path)) {
            return Storage::disk('minio_submissions')->temporaryUrl(
                $this->image_path,
                now()->addMinutes(15)
            );
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
