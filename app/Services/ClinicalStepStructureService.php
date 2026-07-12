<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Support\ClinicalStepKind;
use Illuminate\Support\Str;

class ClinicalStepStructureService
{
    /**
     * Infere e aplica etapas clínicas nos campos existentes; insere quebras quando necessário.
     * Para consentimentos incompletos, acrescenta notices-base alinhados à Rec. CFM 1/2016.
     */
    public function applyTcleStructure(FormTemplate $template): FormTemplate
    {
        $template->loadMissing('fields');
        $template->update(['uses_clinical_steps' => true]);

        if (($template->document_kind ?? '') === 'consentimento'
            || ($template->category ?? '') === 'consentimento'
            || str_contains(mb_strtolower((string) $template->name), 'tcle')) {
            $this->ensureCfmScaffoldNotices($template->fresh(['fields']));
            $template = $template->fresh(['fields']);
        }

        $order = 0;
        $lastKind = null;

        foreach ($template->fields->sortBy('sort_order') as $field) {
            $kind = $this->inferKindForField($field);
            if ($kind !== $lastKind && $lastKind !== null && $field->type !== 'section_break') {
                FormField::create([
                    'template_id' => $template->id,
                    'type' => 'section_break',
                    'label' => ClinicalStepKind::labelFor($kind),
                    'name_key' => 'sec_'.Str::snake($kind).'_'.Str::random(4),
                    'required' => false,
                    'clinical_step_kind' => $kind,
                    'sort_order' => ++$order,
                ]);
            }

            $field->update([
                'clinical_step_kind' => $kind,
                'sort_order' => ++$order,
            ]);
            $lastKind = $kind;
        }

        return $template->fresh(['fields']);
    }

    /**
     * Insere notices mínimos se faltarem etapas de esclarecimento (não duplica se já existirem).
     */
    private function ensureCfmScaffoldNotices(FormTemplate $template): void
    {
        $kinds = $template->fields
            ->pluck('clinical_step_kind')
            ->filter(fn ($k) => is_string($k) && $k !== '')
            ->unique()
            ->all();

        $blob = $template->fields->map(fn ($f) => mb_strtolower(($f->label ?? '').' '.($f->name_key ?? '')))->implode(' | ');
        $maxOrder = (int) $template->fields->max('sort_order');

        $scaffolds = [
            ClinicalStepKind::DESCRICAO_PROCEDIMENTO => [
                'needs' => ! in_array(ClinicalStepKind::DESCRICAO_PROCEDIMENTO, $kinds, true)
                    && ! str_contains($blob, 'procedimento') && ! str_contains($blob, 'tratamento'),
                'label' => 'Descrição do procedimento: explique em linguagem acessível o que será realizado, objetivos e justificativa (Recomendação CFM nº 1/2016). Personalize este texto ao caso clínico.',
                'key' => 'notice_scaffold_procedimento',
            ],
            ClinicalStepKind::RISCOS_BENEFICIOS => [
                'needs' => ! in_array(ClinicalStepKind::RISCOS_BENEFICIOS, $kinds, true)
                    && ! str_contains($blob, 'risco'),
                'label' => 'Riscos, desconfortos, intercorrências e benefícios esperados: liste os relevantes para este procedimento. Não há garantia absoluta de resultado; o profissional age com zelo e técnica adequados.',
                'key' => 'notice_scaffold_riscos',
            ],
            ClinicalStepKind::ALTERNATIVAS => [
                'needs' => ! in_array(ClinicalStepKind::ALTERNATIVAS, $kinds, true)
                    && ! str_contains($blob, 'alternativa'),
                'label' => 'Alternativas terapêuticas e consequências da não realização do procedimento: descreva outras opções (inclusive menos invasivas, se houver) e o que pode ocorrer se nada for feito.',
                'key' => 'notice_scaffold_alternativas',
            ],
            ClinicalStepKind::DECLARACOES => [
                'needs' => ! str_contains($blob, 'recusa') && ! str_contains($blob, 'revog'),
                'label' => 'Direito de recusa: você pode recusar o procedimento, total ou parcialmente, sem prejuízo ao cuidado adequado nas demais opções. Esclareça dúvidas com o profissional antes de assinar.',
                'key' => 'notice_scaffold_recusa',
            ],
        ];

        foreach ($scaffolds as $kind => $meta) {
            if (! $meta['needs']) {
                continue;
            }
            if ($template->fields->contains(fn ($f) => ($f->name_key ?? '') === $meta['key'])) {
                continue;
            }
            FormField::create([
                'template_id' => $template->id,
                'type' => 'notice',
                'label' => $meta['label'],
                'name_key' => $meta['key'],
                'required' => false,
                'clinical_step_kind' => $kind,
                'sort_order' => ++$maxOrder,
            ]);
        }
    }

    private function inferKindForField(FormField $field): string
    {
        if ($field->clinical_step_kind) {
            return (string) $field->clinical_step_kind;
        }

        $type = (string) $field->type;
        $key = Str::lower((string) $field->name_key);
        $label = Str::lower((string) $field->label);

        if ($type === 'section_break' && $field->clinical_step_kind) {
            return (string) $field->clinical_step_kind;
        }

        if ($type === 'signature') {
            return ClinicalStepKind::ASSINATURAS;
        }

        if ($type === 'notice') {
            if (Str::contains($label, ['alternativa', 'opção', 'opcao', 'não realização', 'nao realizacao', 'consequência da não', 'consequencia da nao'])) {
                return ClinicalStepKind::ALTERNATIVAS;
            }
            if (Str::contains($label, ['risco', 'benefício', 'beneficio', 'efeito colateral', 'complica', 'intercorr', 'prognóstico', 'prognostico'])) {
                return ClinicalStepKind::RISCOS_BENEFICIOS;
            }
            if (Str::contains($label, ['lgpd', 'privacidade', 'dados pessoais'])) {
                return ClinicalStepKind::PRIVACIDADE_LGPD;
            }
            if (Str::contains($label, ['recusa', 'revog', 'dúvida', 'duvida'])) {
                return ClinicalStepKind::DECLARACOES;
            }

            return ClinicalStepKind::DESCRICAO_PROCEDIMENTO;
        }

        if (Str::contains($key.$label, ['lgpd', 'privacidade', 'dados pessoais'])) {
            return ClinicalStepKind::PRIVACIDADE_LGPD;
        }

        if (Str::contains($key, ['decl_', 'declar']) || Str::contains($label, ['declaro', 'autorizo', 'ciência', 'ciencia', 'revog', 'recusa'])) {
            return ClinicalStepKind::DECLARACOES;
        }

        if (Str::contains($key.$label, ['quiz', 'compreens', 'entendi'])) {
            return ClinicalStepKind::COMPREENSAO;
        }

        if (Str::contains($key.$label, ['alternativa', 'nao_realizar', 'não realização', 'consequencias_nao'])) {
            return ClinicalStepKind::ALTERNATIVAS;
        }

        if (Str::contains($key.$label, ['risco', 'beneficio', 'benefício', 'intercorr', 'prognost'])) {
            return ClinicalStepKind::RISCOS_BENEFICIOS;
        }

        if (Str::contains($key.$label, ['nome', 'cpf', 'nascimento', 'paciente', 'crm', 'cro', 'profissional', 'endereco', 'emergencia'])) {
            return ClinicalStepKind::DADOS_PACIENTE;
        }

        if (Str::contains($label, ['procedimento', 'tratamento', 'interven', 'anestesia', 'diagnóst', 'diagnost', 'duração', 'duracao', 'desconforto'])) {
            return ClinicalStepKind::DESCRICAO_PROCEDIMENTO;
        }

        if ($type === 'checkbox' && Str::contains($label, ['foto', 'imagem'])) {
            return ClinicalStepKind::DECLARACOES;
        }

        return ClinicalStepKind::DADOS_PACIENTE;
    }
}
