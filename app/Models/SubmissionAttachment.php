<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubmissionAttachment extends Model
{
    protected $fillable = [
        'submission_id',
        'file_path',
        'original_name',
        'mime',
        'size',
        'field_key',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function getUrlAttribute(): string
    {
        if (Storage::disk('minio_attachments')->exists($this->file_path)) {
            return Storage::disk('minio_attachments')->temporaryUrl(
                $this->file_path,
                now()->addMinutes(15)
            );
        }

        return Storage::disk('public')->url($this->file_path);
    }
}
