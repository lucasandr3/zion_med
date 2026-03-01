<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-templates') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('type'), ['select', 'radio'], true) && $this->filled('options_text')) {
            $opts = array_values(array_filter(array_map('trim', explode("\n", $this->input('options_text')))));
            $this->merge(['options' => $opts]);
        }
    }

    public function rules(): array
    {
        $template = $this->route('template');
        $campo = $this->route('campo');

        $nameKeyRules = [
            'required',
            'string',
            'max:80',
            'regex:/^[a-z0-9_]+$/',
            Rule::unique('form_fields', 'name_key')
                ->where('template_id', $template->id)
                ->ignore($campo?->id),
        ];

        $rules = [
            'type' => ['required', 'string', 'in:text,textarea,number,date,select,checkbox,radio,file,signature'],
            'label' => ['required', 'string', 'max:255'],
            'name_key' => $nameKeyRules,
            'required' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
        if (in_array($this->input('type'), ['select', 'radio'], true)) {
            $rules['options_text'] = ['nullable', 'string'];
            $rules['options'] = ['required', 'array', 'min:1'];
            $rules['options.*'] = ['string', 'max:255'];
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name_key.regex' => 'A chave deve conter apenas letras minúsculas, números e underscore (ex: nome_completo).',
            'name_key.unique' => 'Já existe um campo com esta chave neste template.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'label' => 'rótulo',
            'name_key' => 'chave',
            'required' => 'obrigatório',
            'sort_order' => 'ordem',
        ];
    }
}
