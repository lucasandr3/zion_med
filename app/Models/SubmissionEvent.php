<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionEvent extends Model
{
    protected $fillable = [
        'form_submission_id',
        'type',
        'user_id',
        'body',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'created' => 'Protocolo criado',
            'comment' => 'Comentário',
            'approved' => 'Aprovado',
            'rejected' => 'Reprovado',
            default => $this->type,
        };
    }
}
