<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);
    }

    protected $fillable = [
        'clinic_id',
        'template_id',
        'status',
        'submitted_by_user_id',
        'submitter_name',
        'submitter_email',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
        'review_comment',
        'protocol_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'template_id');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(SubmissionValue::class, 'submission_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class, 'submission_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(SubmissionSignature::class, 'submission_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubmissionEvent::class, 'form_submission_id')->orderBy('created_at');
    }

    public function getValueByKey(string $key): mixed
    {
        $v = $this->values->firstWhere('key', $key);
        if ($v && $v->value_json !== null) {
            return $v->value_json;
        }
        return $v?->value_text;
    }

    public function getValuesKeyed(): \Illuminate\Support\Collection
    {
        return $this->values->keyBy('key');
    }
}
