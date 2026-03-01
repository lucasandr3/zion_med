<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $fillable = [
        'template_id',
        'type',
        'label',
        'name_key',
        'required',
        'options_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options_json' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'template_id');
    }

    public function submissionValues(): HasMany
    {
        return $this->hasMany(SubmissionValue::class, 'field_id');
    }

    public function getOptionsList(): array
    {
        $opts = $this->options_json;
        if (is_array($opts) && isset($opts['options']) && is_array($opts['options'])) {
            return $opts['options'];
        }
        if (is_array($opts)) {
            return $opts;
        }
        return [];
    }
}
