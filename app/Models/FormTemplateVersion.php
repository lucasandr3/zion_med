<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplateVersion extends Model
{
    protected $fillable = [
        'form_template_id',
        'version',
        'name',
        'description',
        'fields_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'fields_snapshot' => 'array',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function submissionSignatures(): HasMany
    {
        return $this->hasMany(SubmissionSignature::class, 'form_template_version_id');
    }
}
