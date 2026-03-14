<?php

namespace App\Http\Requests;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;

class PlatformStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $componentKeys = implode(',', array_keys(PlatformSetting::SERVICE_COMPONENTS));

        return [
            'status' => ['required', 'string', 'in:operational,degraded,outage,maintenance'],
            'severity' => ['required', 'string', 'in:none,low,medium,high,critical'],
            'message' => ['nullable', 'string', 'max:500'],
            'components' => ['nullable', 'array'],
            'components.*' => ['string', 'in:operational,degraded,outage,maintenance'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'status',
            'severity' => 'criticidade',
            'message' => 'mensagem',
            'components' => 'componentes',
        ];
    }
}
