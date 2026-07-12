<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormTemplate;
use Illuminate\Support\Collection;

class SignatureFieldResolver
{
    public const ASSISTED_COSIGN_FIELD_KEY = 'assinatura_profissional';

    /**
     * @param  Collection<int, FormField>  $signatureFields
     */
    public function pickProfessionalSlot(Collection $signatureFields): ?FormField
    {
        if ($signatureFields->isEmpty()) {
            return null;
        }

        $positive = ['profissional', 'responsável', 'responsavel', 'clínica', 'clinica', 'equipe', 'médico', 'medico', 'dr.', 'dra.', 'doctor', 'prestador', 'cirurgião', 'cirurgiao'];
        $negativePhrases = ['paciente', 'cliente', 'titular', 'genitor', 'genitora', 'acompanhante', 'assistido', 'responsável legal', 'responsavel legal'];

        $best = null;
        $bestScore = PHP_INT_MIN;

        foreach ($signatureFields as $field) {
            if (! $field instanceof FormField) {
                continue;
            }

            $haystack = mb_strtolower((string) $field->name_key.' '.(string) $field->label);
            $score = 0;
            foreach ($positive as $word) {
                if (str_contains($haystack, $word)) {
                    $score += 3;
                }
            }
            foreach ($negativePhrases as $word) {
                if (str_contains($haystack, $word)) {
                    $score -= 5;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $field;
            }
        }

        if ($bestScore > 0 && $best instanceof FormField) {
            return $best;
        }

        return null;
    }

    public function resolveProfessionalFieldKey(FormTemplate $template): string
    {
        $template->loadMissing('fields');

        /** @var Collection<int, FormField> $slots */
        $slots = collect($template->fields)
            ->filter(fn ($f) => $f instanceof FormField && $f->type === 'signature')
            ->sortBy(fn ($f) => $f instanceof FormField ? ($f->sort_order ?? 0) : 0)
            ->values();

        $chosen = $this->pickProfessionalSlot($slots);

        return $chosen instanceof FormField
            ? (string) $chosen->name_key
            : self::ASSISTED_COSIGN_FIELD_KEY;
    }

    public function isProfessionalCosignField(string $fieldKey, FormTemplate $template): bool
    {
        return $fieldKey === $this->resolveProfessionalFieldKey($template)
            || $fieldKey === self::ASSISTED_COSIGN_FIELD_KEY;
    }

    /**
     * @param  array<string, mixed>  $signatures
     */
    public function hasProfessionalCosignSignature(FormTemplate $template, array $signatures): bool
    {
        $fieldKey = $this->resolveProfessionalFieldKey($template);
        $value = $signatures[$fieldKey] ?? null;

        return is_string($value) && trim($value) !== '';
    }
}
