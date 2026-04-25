<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Builder;
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
        'organization_id',
        'name',
        'description',
        'category',
        'is_active',
        'public_enabled',
        'public_require_person_link',
        'public_token',
        'public_token_expires_at',
        'created_by',
    ];

    public function getClinicIdAttribute(): ?int
    {
        return $this->attributes['organization_id'] ?? null;
    }

    public function setClinicIdAttribute($value): void
    {
        $this->attributes['organization_id'] = $value;
    }

    public static function categoryLabels(): array
    {
        return [
            'anamnese' => 'Anamnese',
            'anamneses' => 'Anamneses',
            'acompanhamento' => 'Acompanhamento',
            'acompanhamento_controle' => 'Acompanhamento & Controle',
            'cadastro_documentacao' => 'Cadastro & Documentação',
            'evolucao' => 'Evolução',
            'consentimento' => 'Consentimento',
            'triagem' => 'Triagem',
            'procedimento' => 'Procedimento',
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

    /**
     * Templates visíveis para o nicho da organização: sem categoria (personalizados), geral e especialidade.
     *
     * @param  Builder<FormTemplate>  $query
     * @return Builder<FormTemplate>
     */
    public function scopeVisibleForNiche(Builder $query, string $niche): Builder
    {
        $niche = $niche !== '' ? $niche : 'estetica';
        $categoriasGlobaisPorTipo = [
            'anamnese',
            'anamneses',
            'acompanhamento',
            'acompanhamento_controle',
            'cadastro_documentacao',
            'evolucao',
            'consentimento',
            'triagem',
            'procedimento',
        ];
        $categoriasConhecidas = array_keys(self::categoryLabels());

        return $query->where(function (Builder $w) use ($niche, $categoriasGlobaisPorTipo, $categoriasConhecidas): void {
            $w->whereNull('category')
                ->orWhere('category', '')
                ->orWhere('category', 'geral')
                ->orWhere('category', $niche)
                ->orWhereIn('category', $categoriasGlobaisPorTipo)
                ->orWhere(function (Builder $custom) use ($categoriasConhecidas): void {
                    $custom->whereNotNull('category')
                        ->where('category', '!=', '')
                        ->whereNotIn('category', $categoriasConhecidas);
                });
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'public_enabled' => 'boolean',
            'public_require_person_link' => 'boolean',
            'public_token_expires_at' => 'datetime',
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
