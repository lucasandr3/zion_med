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
            'is_active' => ['boolean'],
            'public_enabled' => ['boolean'],
            'public_require_person_link' => ['boolean'],
            'public_person_link_mode' => ['nullable', 'string', 'in:code,cpf'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'category' => 'categoria',
            'new_category' => 'nova categoria',
            'is_active' => 'ativo',
            'public_enabled' => 'formulário público',
            'public_require_person_link' => 'exigir código e data de nascimento no formulário público',
            'public_person_link_mode' => 'modo de identificação no formulário público',
        ];
    }
}
