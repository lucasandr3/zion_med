<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-templates') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:80'],
            'new_category' => ['nullable', 'string', 'max:120'],
            'document_kind' => ['nullable', 'string', 'in:ficha,consentimento,ciencia_lgpd'],
            'consent_validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'comprehension_quiz' => ['nullable', 'array', 'max:5'],
            'comprehension_quiz.*.id' => ['nullable', 'string', 'max:40'],
            'comprehension_quiz.*.prompt' => ['required_with:comprehension_quiz', 'string', 'max:500'],
            'comprehension_quiz.*.options' => ['required_with:comprehension_quiz', 'array', 'min:2', 'max:4'],
            'comprehension_quiz.*.options.*' => ['required', 'string', 'max:200'],
            'comprehension_quiz.*.correct_index' => ['required_with:comprehension_quiz', 'integer', 'min:0', 'max:3'],
            'is_active' => ['boolean'],
            'public_enabled' => ['boolean'],
            'public_require_person_link' => ['boolean'],
            'public_person_link_mode' => ['nullable', 'string', 'in:code,cpf'],
            'actors_visibility_rules' => ['nullable', 'array'],
            'actors_visibility_rules.show_when' => ['nullable', 'array'],
            'actors_visibility_rules.show_when.*.field' => ['required_with:actors_visibility_rules.show_when', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'actors_visibility_rules.show_when.*.operator' => ['required_with:actors_visibility_rules.show_when', 'string', 'in:equals,not_equals,filled,empty'],
            'actors_visibility_rules.show_when.*.value' => ['nullable', 'string', 'max:255'],
            'actors_visibility_rules.require_guardian' => ['nullable', 'boolean'],
            'uses_clinical_steps' => ['nullable', 'boolean'],

        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'category' => 'categoria',
            'new_category' => 'nova categoria',
            'document_kind' => 'tipo de documento',
            'consent_validity_days' => 'validade do consentimento (dias)',
            'comprehension_quiz' => 'quiz de compreensão',
            'is_active' => 'ativo',
            'public_enabled' => 'formulário público',
            'public_require_person_link' => 'exigir código e data de nascimento no formulário público',
            'public_person_link_mode' => 'modo de identificação no formulário público',
            'actors_visibility_rules' => 'visibilidade do bloco responsável',
        ];
    }
}
