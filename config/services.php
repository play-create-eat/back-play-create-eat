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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pandadoc' => [
        'key' => env('PANDADOC_API_KEY'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET', ''),
        'public' => env('STRIPE_PUBLIC', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_url' => env('ONESIGNAL_REST_API_URL', 'https://api.onesignal.com'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
        'guzzle_client_timeout' => env('ONESIGNAL_GUZZLE_CLIENT_TIMEOUT', 0),
    ],

    'myinboxmedia' => [
        'user_id' => env('MYINBOXMEDIA_USER_ID', ''),
        'password' => env('MYINBOXMEDIA_USER_PASSWORD', ''),
        'sender_id' => env('MYINBOXMEDIA_USER_SENDER_ID', ''),
        'api_url' => env('MYINBOXMEDIA_USER_API_URL', 'https://myinboxmedia.in'),
    ],

];
