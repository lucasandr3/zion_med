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
        'organization_id',
        'template_id',
        'template_version_id',
        'status',
        'submitted_by_user_id',
        'submitter_name',
        'submitter_email',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
        'review_comment',
        'protocol_number',
        'document_hash',
        'document_snapshot_hash',
        'signing_channel',
        'signing_status',
        'locale',
        'timezone',
        'accepted_text_at',
    ];

    public function getClinicIdAttribute(): ?int
    {
        return $this->attributes['organization_id'] ?? null;
    }

    public function setClinicIdAttribute($value): void
    {
        $this->attributes['organization_id'] = $value;
    }

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'accepted_text_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @deprecated Use organization(). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(FormTemplateVersion::class, 'template_version_id');
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
