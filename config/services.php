<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | n8n Webhooks
    |--------------------------------------------------------------------------
    */
    'n8n_whatsapp' => [
        'webhook_url' => env('N8N_WHATSAPP_WEBHOOK_URL'),
    ],

    'n8n_webhook_erro_pagamento' => env(
        'N8N_WEBHOOK_ERRO_PAGAMENTO',
        'https://n8n-webhook.gestgo.com.br/webhook/erro-pagamento'
    ),

    /*
    |--------------------------------------------------------------------------
    | n8n — lead formulário "Agendar demonstração" (API pública)
    |--------------------------------------------------------------------------
    */
    'n8n_demonstracao' => [
        'webhook_url' => env(
            'N8N_DEMONSTRACAO_WEBHOOK_URL',
            'https://n8n-webhook.gestgo.com.br/webhook/c08cd03e-0111-4583-af85-a29eb47298bc'
        ),
    ],

    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

];
