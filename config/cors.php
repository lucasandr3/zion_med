<?php

$defaultOrigins = [
    'https://app.gestgo.com.br',
    'https://gestgo.com.br',
    'https://site-homolog.gestgo.com.br',
    'https://homolog.gestgo.com.br',
];

// Não usar app() aqui — config carrega cedo demais no bootstrap.
if (env('APP_ENV', 'production') !== 'production') {
    $defaultOrigins[] = 'http://localhost:4200';
    $defaultOrigins[] = 'http://localhost:59698';
    $defaultOrigins[] = 'http://127.0.0.1:4200';
}

$fromEnv = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
)));

$configuredOrigins = $fromEnv !== [] ? $fromEnv : $defaultOrigins;

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Origens do SPA Angular (dev e produção). Ajuste CORS_ALLOWED_ORIGINS no .env
    | (separado por vírgula) ao publicar em novos domínios.
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
