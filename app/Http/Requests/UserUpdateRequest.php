<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update-user', $this->route('usuario')) ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('usuario');
        $clinicId = $this->user()?->clinic_id ?? session('current_clinic_id');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where('clinic_id', $clinicId)->ignore($user->id),
            ],
            'role' => ['required', 'string', 'in:owner,manager,staff'],
            'active' => ['boolean'],
            'can_switch_clinic' => ['sometimes', 'boolean'],
        ];
        if ($this->filled('password')) {
            $rules['password'] = ['nullable', 'string', 'confirmed', Password::defaults()];
        }
        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'role' => 'perfil',
            'active' => 'ativo',
            'can_switch_clinic' => 'acessar todas as clínicas',
        ];
    }
}
