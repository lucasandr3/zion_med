<?php

return [
    'base_url' => env('CLINICORP_BASE_URL', 'https://api.clinicorp.com/rest/v1'),
    'timeout_seconds' => (int) env('CLINICORP_TIMEOUT_SECONDS', 15),
    'connect_timeout_seconds' => (int) env('CLINICORP_CONNECT_TIMEOUT_SECONDS', 8),
];
