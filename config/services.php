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

    'paymongo' => [
        'mode' => env('PAYMONGO_MODE', 'test'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'test_public_key' => env('PAYMONGO_TEST_PUBLIC_KEY'),
        'test_secret_key' => env('PAYMONGO_TEST_SECRET_KEY'),
        'test_webhook_secret' => env('PAYMONGO_TEST_WEBHOOK_SECRET'),
        'live_public_key' => env('PAYMONGO_LIVE_PUBLIC_KEY'),
        'live_secret_key' => env('PAYMONGO_LIVE_SECRET_KEY'),
        'live_webhook_secret' => env('PAYMONGO_LIVE_WEBHOOK_SECRET'),
        'success_url' => env('PAYMONGO_SUCCESS_URL', env('APP_URL').'/payments/success'),
        'cancel_url' => env('PAYMONGO_CANCEL_URL', env('APP_URL').'/payments/cancel'),
        'default_payment_methods' => env('PAYMONGO_DEFAULT_PAYMENT_METHODS', 'card,gcash'),
        'timeout' => (int) env('PAYMONGO_TIMEOUT', 30),
        'webhook_tolerance' => (int) env('PAYMONGO_WEBHOOK_TOLERANCE', 300),
    ],

    'google' => [
        // Comma/space/semicolon separated list of accepted OAuth client IDs (audiences).
        'auth_audiences' => env('GOOGLE_AUTH_AUDIENCES', ''),
        // Optional single fallback audience value.
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
    ],

];
