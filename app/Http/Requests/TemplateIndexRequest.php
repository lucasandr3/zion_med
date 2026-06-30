<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TemplateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-submissions') ?? false;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
            'category' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function isActiveFilter(): ?bool
    {
        if (! $this->has('is_active')) {
            return null;
        }

        return $this->boolean('is_active');
    }
}
