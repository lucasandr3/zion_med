<?php

$defaultOrigins = [
    'https://app.gestgo.com.br',
    'https://gestgo.com.br',
    'https://site-homolog.gestgo.com.br',
    'https://homolog.gestgo.com.br',
];

$localOrigins = env('APP_ENV', 'production') !== 'production';

// Não usar app() aqui — config carrega cedo demais no bootstrap.
if ($localOrigins) {
    $defaultOrigins[] = 'http://localhost:4200';
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

    'allowed_origins_patterns' => $localOrigins ? [
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
    ] : [],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'Accept',
        'X-Organization-Id',
        'X-Clinic-Id',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
