<?php

namespace App\Http\Requests;

use App\Support\ApiPagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-notifications') ?? false;
    }

    public function rules(): array
    {
        return [
            'filtro' => ['nullable', 'string', Rule::in(['todas', 'nao_lidas'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filter(): string
    {
        return $this->input('filtro', 'todas');
    }

    public function perPage(int $default = 20, int $max = 50): int
    {
        $requested = $this->filled('per_page') ? (int) $this->input('per_page') : null;

        return ApiPagination::perPage($requested, $default, $max);
    }
}
