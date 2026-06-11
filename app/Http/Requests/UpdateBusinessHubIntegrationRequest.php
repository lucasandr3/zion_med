<?php

namespace App\Http\Requests;

use App\Services\BusinessHubConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessHubIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'connector_type' => ['sometimes', 'string', Rule::in(BusinessHubConfigService::CONNECTOR_TYPES)],
            'system_name' => ['sometimes', 'string', 'max:128'],
            'version' => ['sometimes', 'string', 'max:32'],
        ];
    }
}
