<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-submissions') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'has_protocols' => ['nullable'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(int $default = 20, int $max = 100): int
    {
        $requested = $this->filled('per_page') ? (int) $this->input('per_page') : null;

        return \App\Support\ApiPagination::perPage($requested, $default, $max);
    }

    public function hasProtocolsFilter(): ?bool
    {
        if (! $this->has('has_protocols')) {
            return null;
        }

        $value = $this->query('has_protocols');
        if ($value === '1' || $value === 'true' || $value === true) {
            return true;
        }
        if ($value === '0' || $value === 'false' || $value === false) {
            return false;
        }

        return null;
    }
}
