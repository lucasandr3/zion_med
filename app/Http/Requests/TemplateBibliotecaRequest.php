<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TemplateBibliotecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-submissions') ?? false;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:80'],
        ];
    }
}
