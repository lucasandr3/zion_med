<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:128'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
            'block_mode' => ['required', 'string', 'in:soft,hard'],
            'multi_empresa_plan' => ['required', 'string', 'max:64'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_name' => 'nome do produto',
            'trial_days' => 'trial (dias)',
            'grace_days' => 'grace (dias)',
            'block_mode' => 'modo de bloqueio',
            'multi_empresa_plan' => 'plano multi-empresa',
        ];
    }
}
