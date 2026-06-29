<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentSendIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-submissions') ?? false;
    }

    public function rules(): array
    {
        return [
            'caixa' => ['nullable', 'string', Rule::in(['pendentes', 'assinados', 'expirados', 'cancelados'])],
            'template_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'string', 'max:50'],
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
