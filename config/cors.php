<?php

$defaultOrigins = [
    'http://localhost:4200',
    'http://127.0.0.1:4200',
    'http://zion_med.test',
    'https://app.gestgo.com.br',
];

$configuredOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', implode(',', $defaultOrigins)))
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Origens do SPA Angular (dev e produção). Ajuste CORS_ALLOWED_ORIGINS no .env
    | (separado por vírgula) ao publicar no Easy Panel ou em novos domínios.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $configuredOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'Accept',
        'X-Organization-Id',
        'X-Clinic-Id',
        'X-Requested-With',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
