<?php

return [
    'base_url' => env('TRINKS_BASE_URL', 'https://api.trinks.com'),
    'timeout_seconds' => (int) env('TRINKS_TIMEOUT_SECONDS', 15),
    'connect_timeout_seconds' => (int) env('TRINKS_CONNECT_TIMEOUT_SECONDS', 8),
];
