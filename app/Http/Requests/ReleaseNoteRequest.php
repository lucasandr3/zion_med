<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:32'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'in:feature,improvement,fix'],
            'items.*.text' => ['required', 'string', 'max:1000'],
            'released_at' => ['required', 'date'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
