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
        'phone_alt',
        'email',
        'birth_date',
        'age',
        'sex',
        'cpf',
        'rg',
        'marital_status',
        'profession',
        'referred_by',
        'address',
        'neighborhood',
        'city',
        'cep',
        'lead_source_instagram',
        'lead_source_google',
        'lead_source_facebook',
        'lead_source_indicacao_amigo',
        'lead_source_indicacao_medica',
        'lead_source_plano_saude',
        'lead_source_outro',
        'has_health_plan',
        'health_plan_operator',
        'health_plan_card_number',
        'lgpd_accept_comms',
        'lgpd_accept_reminders',
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
            'age' => 'integer',
            'lead_source_instagram' => 'boolean',
            'lead_source_google' => 'boolean',
            'lead_source_facebook' => 'boolean',
            'lead_source_indicacao_amigo' => 'boolean',
            'lead_source_indicacao_medica' => 'boolean',
            'lead_source_plano_saude' => 'boolean',
            'lgpd_accept_comms' => 'boolean',
            'lgpd_accept_reminders' => 'boolean',
            'cpf' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'phone_alt' => 'encrypted',
            'rg' => 'encrypted',
            'address' => 'encrypted',
            'neighborhood' => 'encrypted',
            'city' => 'encrypted',
            'cep' => 'encrypted',
            'health_plan_card_number' => 'encrypted',
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
