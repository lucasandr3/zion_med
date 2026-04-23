<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use App\Support\PersonPiiHasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);

        static::saving(function (Person $person): void {
            $cpfPlain = $person->cpf;
            if (is_string($cpfPlain) && $cpfPlain !== '') {
                $digits = preg_replace('/\D+/', '', $cpfPlain) ?? '';
                $person->cpf_hash = strlen($digits) === 11 ? PersonPiiHasher::cpf($digits) : null;
            } else {
                $person->cpf_hash = null;
            }
            $emailPlain = $person->email;
            if (is_string($emailPlain) && trim($emailPlain) !== '') {
                $person->email_hash = PersonPiiHasher::email($emailPlain);
            } else {
                $person->email_hash = null;
            }
        });
    }

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'phone',
        'email',
        'birth_date',
        'cpf',
        'notes',
        'status',
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
            'birth_date' => 'date',
            'cpf' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'person_id');
    }

    public function documentSends(): HasMany
    {
        return $this->hasMany(DocumentSend::class, 'person_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
