<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');
        $keyUnique = Rule::unique('plans', 'key');
        if ($plan) {
            $keyUnique->ignore($plan->id);
        }

        return [
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', $keyUnique],
            'name' => ['required', 'string', 'max:128'],
            'value' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'key' => 'chave',
            'name' => 'nome',
            'value' => 'valor (R$/mês)',
            'description' => 'descrição',
            'sort_order' => 'ordem',
            'is_active' => 'ativo',
        ];
    }
}
