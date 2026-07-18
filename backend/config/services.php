<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'expo_push' => [
        'base_url' => env('EXPO_PUSH_BASE_URL', 'https://exp.host/--/api/v2'),
        'access_token' => env('EXPO_ACCESS_TOKEN', ''),
        'timeout' => (int) env('EXPO_PUSH_TIMEOUT', 10),
        'connect_timeout' => (int) env('EXPO_PUSH_CONNECT_TIMEOUT', 3),
        'receipt_delay_minutes' => (int) env('EXPO_PUSH_RECEIPT_DELAY_MINUTES', 15),
        'receipt_retry_minutes' => (int) env('EXPO_PUSH_RECEIPT_RETRY_MINUTES', 5),
        'receipt_expiry_hours' => (int) env('EXPO_PUSH_RECEIPT_EXPIRY_HOURS', 24),
        'stale_device_days' => (int) env('EXPO_PUSH_STALE_DEVICE_DAYS', 90),
        'delivery_retention_days' => (int) env('EXPO_PUSH_DELIVERY_RETENTION_DAYS', 30),
    ],

    'mercado_livre' => [
        'base_url' => env('MERCADO_LIVRE_BASE_URL', 'https://api.mercadolibre.com'),
        'site_id' => env('MERCADO_LIVRE_SITE_ID', 'MLB'),
        'access_token' => env('MERCADO_LIVRE_ACCESS_TOKEN', ''),
        'timeout' => (int) env('MERCADO_LIVRE_TIMEOUT', 10),
        'connect_timeout' => (int) env('MERCADO_LIVRE_CONNECT_TIMEOUT', 3),
    ],

    'payments' => [
        'pick_purchase_amount_cents' => (int) env('PICK_PURCHASE_AMOUNT_CENTS', 499),
    ],

    'mercado_pago' => [
        'base_url' => env('MERCADO_PAGO_BASE_URL', 'https://api.mercadopago.com'),
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN', ''),
        'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET', ''),
        'webhook_url' => env('MERCADO_PAGO_WEBHOOK_URL', rtrim((string) env('APP_URL', ''), '/').'/api/v1/payments/mercadopago/webhook'),
        'return_url' => env('MERCADO_PAGO_RETURN_URL', 'cpxsecreto://payments'),
        'checkout_expiry_minutes' => (int) env('MERCADO_PAGO_CHECKOUT_EXPIRY_MINUTES', 30),
        'webhook_tolerance_seconds' => (int) env('MERCADO_PAGO_WEBHOOK_TOLERANCE_SECONDS', 300),
        'timeout' => (int) env('MERCADO_PAGO_TIMEOUT', 10),
        'connect_timeout' => (int) env('MERCADO_PAGO_CONNECT_TIMEOUT', 3),
    ],

];
