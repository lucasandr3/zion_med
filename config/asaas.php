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
    'grace_days' => (int) env('ASAAS_GRACE_DAYS', 7),
    'block_mode' => env('ASAAS_BLOCK_MODE', 'soft'), // soft = bloqueia app, libera /billing; hard = bloqueia tudo exceto logout

    'product_name' => env('ASAAS_PRODUCT_NAME', 'ZionMed'),

    'multi_empresa_plan' => env('ASAAS_MULTI_EMPRESA_PLAN', 'enterprise'),

    'plans' => [
        // Teste com preço único e plano principal simplificado para a landing e o fluxo de cadastro.
        'executive' => [
            'name' => 'ZionMed Clínica',
            'value' => 247.00,
            'description' => 'Plano único com fichas e consentimentos digitais, assinatura, protocolo automático, PDF e templates prontos.',
        ],
    ],

];
