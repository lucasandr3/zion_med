<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);
    }

    protected $fillable = [
        'clinic_id',
        'name',
        'description',
        'category',
        'is_active',
        'public_enabled',
        'public_token',
        'created_by',
    ];

    public static function categoryLabels(): array
    {
        return [
            'geral' => 'Geral (todos os tenants)',
            'clinica_medica' => 'Clínica Médica',
            'odontologia' => 'Odontologia',
            'estetica' => 'Estética / Harmonização',
            'fisioterapia' => 'Fisioterapia',
            'psicologia' => 'Psicologia / Psiquiatria',
            'pediatria' => 'Pediatria',
            'ginecologia' => 'Ginecologia / Obstetrícia',
            'oftalmologia' => 'Oftalmologia',
            'dermatologia' => 'Dermatologia',
            'laboratorio' => 'Laboratório / Coleta',
        ];
    }

    public function getCategoryLabelAttribute(): ?string
    {
        return $this->category ? (self::categoryLabels()[$this->category] ?? $this->category) : null;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'public_enabled' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'template_id')->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'template_id');
    }
}
