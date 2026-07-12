<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Support\ClinicalStepKind;

class ClinicalStepValidationService
{
    /**
     * Valida estrutura clínica antes de publicar consentimentos.
     * Alinhado à Recomendação CFM nº 1/2016 (orientação ética) e boas práticas de TCI.
     *
     * @return list<array{level: string, code: string, message: string}>
     */
    public function validateForPublish(FormTemplate $template): array
    {
        $template->loadMissing('fields');
        $issues = [];

        $isConsent = ($template->document_kind ?? '') === 'consentimento'
            || ($template->category ?? '') === 'consentimento'
            || str_contains(mb_strtolower((string) $template->name), 'tcle');

        $kinds = $template->fields
            ->pluck('clinical_step_kind')
            ->filter(fn ($k) => is_string($k) && $k !== '')
            ->unique()
            ->values()
            ->all();

        if ($template->uses_clinical_steps && count($kinds) === 0) {
            $issues[] = [
                'level' => 'error',
                'code' => 'clinical_steps_enabled_without_kinds',
                'message' => 'Etapas clínicas estão ativas, mas nenhum campo possui tipo de etapa clínica.',
            ];
        }

        if (! $isConsent) {
            return $issues;
        }

        $requiredKinds = [
            ClinicalStepKind::DESCRICAO_PROCEDIMENTO,
            ClinicalStepKind::RISCOS_BENEFICIOS,
            ClinicalStepKind::ALTERNATIVAS,
            ClinicalStepKind::ASSINATURAS,
        ];

        foreach ($requiredKinds as $required) {
            if (! $this->hasInformativeStep($template, $required) && ! $this->hasStepByHeuristic($template, $required)) {
                $issues[] = [
                    'level' => 'error',
                    'code' => 'missing_clinical_step_'.$required,
                    'message' => 'Consentimento deve incluir conteúdo visível ao paciente na etapa: '.ClinicalStepKind::labelFor($required).' (Recomendação CFM nº 1/2016 — boa prática).',
                ];
            }
        }

        if (! $this->hasPatientFacingDisclosure($template)) {
            $issues[] = [
                'level' => 'error',
                'code' => 'consent_missing_patient_disclosure',
                'message' => 'Inclua textos informativos (notice) ou campos de preenchimento (textarea) com procedimento, riscos/benefícios e alternativas visíveis ao paciente antes da assinatura.',
            ];
        }

        $hasSignature = $template->fields->contains(fn ($f) => $f->type === 'signature');
        if (! $hasSignature) {
            $issues[] = [
                'level' => 'error',
                'code' => 'missing_signature_field',
                'message' => 'Consentimentos devem incluir ao menos um campo de assinatura.',
            ];
        }

        $hasAuthorization = $template->fields->contains(function ($f) {
            $blob = mb_strtolower(($f->label ?? '').' '.($f->name_key ?? ''));

            return in_array($f->type, ['checkbox', 'radio'], true)
                && (str_contains($blob, 'autoriz') || str_contains($blob, 'aceito') || str_contains($blob, 'consent'));
        });
        if (! $hasAuthorization) {
            $issues[] = [
                'level' => 'error',
                'code' => 'missing_authorization_checkbox',
                'message' => 'Inclua declaração explícita de autorização/aceite do procedimento pelo paciente.',
            ];
        }

        $hasRefusal = $template->fields->contains(function ($f) {
            $blob = mb_strtolower(($f->label ?? '').' '.($f->name_key ?? ''));

            return str_contains($blob, 'recusa') || str_contains($blob, 'revog') || str_contains($blob, 'desistir');
        });
        if (! $hasRefusal) {
            $issues[] = [
                'level' => 'warning',
                'code' => 'missing_refusal_language',
                'message' => 'Recomendado informar o direito de recusa/revogação do consentimento.',
            ];
        }

        if ($this->mixesMarketingImageConsent($template)) {
            $issues[] = [
                'level' => 'warning',
                'code' => 'bundled_image_marketing_consent',
                'message' => 'Evite misturar autorização de imagem/marketing neste TCLE clínico. Prefira o documento separado de imagem/LGPD (finalidade específica — LGPD).',
            ];
        }

        return $issues;
    }

    /** @return list<string> */
    public function blockingCodes(): array
    {
        return [
            'clinical_steps_enabled_without_kinds',
            'missing_clinical_step_'.ClinicalStepKind::DESCRICAO_PROCEDIMENTO,
            'missing_clinical_step_'.ClinicalStepKind::RISCOS_BENEFICIOS,
            'missing_clinical_step_'.ClinicalStepKind::ALTERNATIVAS,
            'missing_clinical_step_'.ClinicalStepKind::ASSINATURAS,
            'consent_missing_patient_disclosure',
            'missing_signature_field',
            'missing_authorization_checkbox',
        ];
    }

    private function hasInformativeStep(FormTemplate $template, string $kind): bool
    {
        return $template->fields->contains(function ($f) use ($kind) {
            if (($f->clinical_step_kind ?? null) !== $kind) {
                return false;
            }
            if (in_array($f->type, ['section_break', 'page_break', 'step_break'], true)) {
                return false;
            }

            return true;
        });
    }

    private function hasStepByHeuristic(FormTemplate $template, string $kind): bool
    {
        return match ($kind) {
            ClinicalStepKind::ASSINATURAS => $template->fields->contains(fn ($f) => $f->type === 'signature'),
            ClinicalStepKind::DESCRICAO_PROCEDIMENTO => $this->blobContains($template, ['procedimento', 'tratamento', 'cirurgia', 'diagnóst', 'diagnost']),
            ClinicalStepKind::RISCOS_BENEFICIOS => $this->blobContains($template, ['risco', 'intercorr', 'complica', 'benefício', 'beneficio', 'prognóst', 'prognost']),
            ClinicalStepKind::ALTERNATIVAS => $this->blobContains($template, ['alternativa', 'não realização', 'nao realizacao', 'opção de tratamento', 'opcao de tratamento']),
            default => false,
        };
    }

    /** @param list<string> $needles */
    private function blobContains(FormTemplate $template, array $needles): bool
    {
        $blob = $template->fields
            ->filter(fn ($f) => in_array($f->type, ['notice', 'textarea', 'heading', 'text'], true))
            ->map(fn ($f) => mb_strtolower((string) ($f->label ?? '').' '.($f->name_key ?? '')))
            ->implode(' | ');

        foreach ($needles as $needle) {
            if (str_contains($blob, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function hasPatientFacingDisclosure(FormTemplate $template): bool
    {
        $informative = $template->fields->filter(function ($f) {
            if (! in_array($f->type, ['notice', 'textarea', 'heading'], true)) {
                return false;
            }
            $blob = mb_strtolower((string) ($f->label ?? ''));

            return mb_strlen(trim($blob)) >= 40;
        });

        if ($informative->count() < 2) {
            return false;
        }

        $blob = $informative->map(fn ($f) => mb_strtolower((string) $f->label))->implode(' | ');
        $hasProc = str_contains($blob, 'procedimento') || str_contains($blob, 'tratamento') || str_contains($blob, 'cirurgia') || str_contains($blob, 'diagnóst') || str_contains($blob, 'diagnost');
        $hasRisk = str_contains($blob, 'risco') || str_contains($blob, 'intercorr') || str_contains($blob, 'complica');
        $hasAlt = str_contains($blob, 'alternativa') || str_contains($blob, 'opção') || str_contains($blob, 'opcao') || str_contains($blob, 'não realização') || str_contains($blob, 'nao realizacao');

        return $hasProc && $hasRisk && $hasAlt;
    }

    private function mixesMarketingImageConsent(FormTemplate $template): bool
    {
        return $template->fields->contains(function ($f) {
            $blob = mb_strtolower(($f->label ?? '').' '.($f->name_key ?? ''));

            return (str_contains($blob, 'marketing') || str_contains($blob, 'publicidade') || str_contains($blob, 'rede social') || str_contains($blob, 'sem identificação') || str_contains($blob, 'sem identificacao'))
                && (str_contains($blob, 'imagem') || str_contains($blob, 'foto') || str_contains($blob, 'foto_'));
        });
    }
}
