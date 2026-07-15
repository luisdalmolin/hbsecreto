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

];
