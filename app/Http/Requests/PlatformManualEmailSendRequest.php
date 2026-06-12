<?php

namespace App\Http\Requests;

use App\Enums\PlatformManualEmailCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformManualEmailSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::enum(PlatformManualEmailCategory::class)],
            'to_email' => ['required', 'email', 'max:255'],
            'to_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'lead_id' => ['nullable', 'integer', 'exists:demonstration_requests,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Selecione a categoria do e-mail.',
            'to_email.required' => 'Informe o e-mail do destinatário.',
            'to_email.email' => 'Informe um e-mail válido.',
            'subject.required' => 'Informe o assunto.',
            'body.required' => 'Informe a mensagem.',
            'body.max' => 'A mensagem deve ter no máximo 10.000 caracteres.',
        ];
    }
}
