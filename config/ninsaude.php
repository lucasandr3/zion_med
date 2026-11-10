<?php

return [
    'base_url' => env('NINSAUDE_BASE_URL', 'https://api.ninsaude.com/v1'),
    'timeout_seconds' => (int) env('NINSAUDE_TIMEOUT_SECONDS', 20),
    'connect_timeout_seconds' => (int) env('NINSAUDE_CONNECT_TIMEOUT_SECONDS', 8),
];
