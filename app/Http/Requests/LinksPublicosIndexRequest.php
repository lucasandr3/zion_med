<?php

namespace App\Http\Requests;

use App\Support\ApiPagination;
use Illuminate\Foundation\Http\FormRequest;

class LinksPublicosIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->can('manage-templates') || $user->can('view-submissions'));
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(int $default = 10, int $max = 50): int
    {
        $requested = $this->filled('per_page') ? (int) $this->input('per_page') : null;

        return ApiPagination::perPage($requested, $default, $max);
    }
}
