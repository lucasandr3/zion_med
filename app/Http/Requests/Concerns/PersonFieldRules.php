<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait PersonFieldRules
{
    /** @return array<string, array<int, mixed>> */
    protected function personFieldRules(bool $forUpdate = false): array
    {
        $nameRule = $forUpdate
            ? ['sometimes', 'required', 'string', 'max:255']
            : ['required', 'string', 'max:255'];

        return [
            'name' => $nameRule,
            'phone' => ['nullable', 'string', 'max:50'],
            'phone_alt' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'sex' => ['nullable', 'string', Rule::in(['F', 'M', 'O'])],
            'cpf' => ['nullable', 'string', 'max:14'],
            'rg' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'profession' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:20'],
            'lead_source_instagram' => ['nullable', 'boolean'],
            'lead_source_google' => ['nullable', 'boolean'],
            'lead_source_facebook' => ['nullable', 'boolean'],
            'lead_source_indicacao_amigo' => ['nullable', 'boolean'],
            'lead_source_indicacao_medica' => ['nullable', 'boolean'],
            'lead_source_plano_saude' => ['nullable', 'boolean'],
            'lead_source_outro' => ['nullable', 'string', 'max:255'],
            'has_health_plan' => ['nullable', 'string', Rule::in(['sim', 'nao'])],
            'health_plan_operator' => ['nullable', 'string', 'max:255'],
            'health_plan_card_number' => ['nullable', 'string', 'max:100'],
            'lgpd_accept_comms' => ['nullable', 'boolean'],
            'lgpd_accept_reminders' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
