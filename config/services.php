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

    'whatsapp' => [
        'default' => env('WHATSAPP_DEFAULT_DRIVER', 'twilio'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_AUTH_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
        'bypass_signature' => env('TWILIO_BYPASS_SIGNATURE', false),
    ],

    'meta' => [
        'phone_number_id' => env('META_PHONE_NUMBER_ID'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN', 'q4i_lotto_meta_secret'),
        'bypass_signature' => env('META_BYPASS_SIGNATURE', false),
    ],

    'modern_lottery' => [
        'url' => env('MODERN_LOTTERY_API_URL', 'https://api.modernlottery.ng/v1'),
        'key' => env('MODERN_LOTTERY_API_KEY', 'mock_key'),
    ],

];
