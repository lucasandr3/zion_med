<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ASAAS API (Sandbox / Produção)
    |--------------------------------------------------------------------------
    */

    'base_url' => env('ASAAS_BASE_URL', 'https://sandbox.asaas.com/api/v3'),
    'api_key'  => env('ASAAS_API_KEY'),
    'webhook_secret' => env('ASAAS_WEBHOOK_SECRET'),

    'trial_days' => (int) env('ASAAS_TRIAL_DAYS', 14),
    /** Dias antes do fim do trial (incluindo o último dia) para exibir aviso no app. */
    'trial_warning_days' => (int) env('ASAAS_TRIAL_WARNING_DAYS', 3),
    'grace_days' => (int) env('ASAAS_GRACE_DAYS', 7),
    'block_mode' => env('ASAAS_BLOCK_MODE', 'soft'), // soft = bloqueia app, libera /billing; hard = bloqueia tudo exceto logout

    'product_name' => env('ASAAS_PRODUCT_NAME', 'Gestgo'),

    'multi_empresa_plan' => env('ASAAS_MULTI_EMPRESA_PLAN', 'enterprise'),

    'plans' => [
        'solo' => [
            'name' => env('ASAAS_PLAN_SOLO_NAME', 'Gestgo Profissional'),
            'value' => (float) env('ASAAS_PLAN_SOLO_VALUE', 97),
            'description' => 'Para autônomos: fichas digitais, consentimentos, assinatura eletrônica, protocolo, PDF, link da bio e templates — até 2 usuários.',
            'max_users' => (int) env('ASAAS_PLAN_SOLO_MAX_USERS', 2),
            'max_organizations_per_tenant' => 1,
        ],
        'executive' => [
            'name' => env('ASAAS_PLAN_EXECUTIVE_NAME', 'Gestgo Business'),
            'value' => (float) env('ASAAS_PLAN_EXECUTIVE_VALUE', 247),
            'description' => 'Para clínicas e equipes: mesmo núcleo do Profissional, com usuários ilimitados no plano.',
            'max_users' => null,
            'max_organizations_per_tenant' => 1,
        ],
    ],

];
