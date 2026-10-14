<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormImport extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by',
        'status',
        'provider',
        'model_version',
        'source_disk',
        'source_path',
        'source_original_name',
        'source_mime',
        'source_size',
        'idempotency_key',
        'extracted_schema_json',
        'reviewed_schema_json',
        'warnings_json',
        'error_code',
        'error_message',
        'template_id',
        'processed_at',
        'committed_at',
    ];

    protected $casts = [
        'extracted_schema_json' => 'array',
        'reviewed_schema_json' => 'array',
        'warnings_json' => 'array',
        'processed_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'template_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(FormImportPage::class);
    }
}
