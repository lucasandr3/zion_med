<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProtocolIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-submissions') ?? false;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'person_id' => ['nullable', 'integer'],
            'busca' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(int $default = 20, int $max = 100): int
    {
        $requested = $this->filled('per_page') ? (int) $this->input('per_page') : null;

        return \App\Support\ApiPagination::perPage($requested, $default, $max);
    }
}
