<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    |
    | Gere o par com: php artisan webpush:vapid
    | Ou: npx web-push generate-vapid-keys
    |
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:suporte@gestgo.com.br'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],
];
