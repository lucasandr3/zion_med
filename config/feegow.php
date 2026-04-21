<?php

return [
    'base_url' => env('FEEGOW_BASE_URL', 'https://api.feegow.com/v1/api'),
    'timeout_seconds' => (int) env('FEEGOW_TIMEOUT_SECONDS', 15),
    'connect_timeout_seconds' => (int) env('FEEGOW_CONNECT_TIMEOUT_SECONDS', 8),
];
