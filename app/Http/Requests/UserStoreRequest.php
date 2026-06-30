<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    public function rules(): array
    {
        $clinicId = $this->user()?->currentOrganizationId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where('organization_id', $clinicId),
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => [
                'required',
                'string',
                'max:64',
                Rule::exists('organization_roles', 'slug')->where(function ($query) use ($clinicId) {
                    return $query->where('organization_id', $clinicId)->where('is_assignable', true);
                }),
            ],
            'can_switch_clinic' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
            'role' => 'perfil',
            'can_switch_clinic' => 'acessar todas as clínicas',
        ];
    }
}
