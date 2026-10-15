<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TemplateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->can('view-submissions')
            || $user->can('manage-templates')
            || $user->can('manage-clinic')
        );
    }

    public function rules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
            'public_enabled' => ['nullable', 'boolean'],
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

    public function publicEnabledFilter(): ?bool
    {
        if (! $this->has('public_enabled')) {
            return null;
        }

        return $this->boolean('public_enabled');
    }
}
