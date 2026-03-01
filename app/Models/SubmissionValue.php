<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionValue extends Model
{
    protected $fillable = [
        'submission_id',
        'field_id',
        'key',
        'value_text',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }
}
