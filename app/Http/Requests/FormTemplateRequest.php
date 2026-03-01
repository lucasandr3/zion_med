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
            'is_active' => ['boolean'],
            'public_enabled' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'is_active' => 'ativo',
            'public_enabled' => 'formulário público',
        ];
    }
}
