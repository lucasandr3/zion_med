<?php

return [

    'enabled' => env('ERRORHUB_ENABLED', false),

    'api_url' => env('ERRORHUB_API_URL', 'https://api-errorhub.zionai.com.br/api/v1/events'),

    'api_key' => env('ERRORHUB_API_KEY'),

    'environment' => env('ERRORHUB_ENVIRONMENT', env('APP_ENV', 'production')),

    'feature' => env('ERRORHUB_FEATURE', 'Gestgo Back'),

];
