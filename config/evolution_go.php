<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Evolution Go (WhatsApp)
    |--------------------------------------------------------------------------
    |
    | URL base da API (sem barra final), ex.:
    | https://automacao-evolution-go-new.xxx.easypanel.host
    |
    | GLOBAL_API_KEY = variável de ambiente do container Evolution Go (.env).
    |
    */
    'base_url' => rtrim((string) env('EVOLUTION_GO_BASE_URL', ''), '/'),
    'api_key' => (string) env('EVOLUTION_GO_API_KEY', ''),
];
