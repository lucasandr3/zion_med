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
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }

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
            'viewed' => 'Documento visualizado',
            'accepted' => 'Texto aceito',
            'signed' => 'Assinado',
            'comment' => 'Comentário',
            'approved' => 'Aprovado',
            'rejected' => 'Reprovado',
            default => $this->type,
        };
    }
}
