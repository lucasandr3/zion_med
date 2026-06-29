<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-clinic') ?? false;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(int $default = 50, int $max = 100): int
    {
        $requested = $this->filled('per_page') ? (int) $this->input('per_page') : null;

        return \App\Support\ApiPagination::perPage($requested, $default, $max);
    }
}
