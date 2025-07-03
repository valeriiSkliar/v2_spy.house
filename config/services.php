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
        'key' => env('RESEND_API_KEY'),
        'from' => env('RESEND_FROM', 'noreply@spy.house'),
        'audience_id' => env('RESEND_AUDIENCE_ID'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Parsers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various API parsers including authentication,
    | rate limits, and endpoint settings.
    |
    */

    'push_house' => [
        'api_key' => env('PUSH_HOUSE_API_KEY'),
        'base_url' => env('PUSH_HOUSE_BASE_URL', 'https://api.push.house'),
        'rate_limit' => env('PUSH_HOUSE_RATE_LIMIT', 1000), // requests per minute
        'timeout' => env('PUSH_HOUSE_TIMEOUT', 45),
        'max_retries' => env('PUSH_HOUSE_MAX_RETRIES', 3),
        'retry_delay' => env('PUSH_HOUSE_RETRY_DELAY', 2)
    ],

    // Template for new parsers
    'parser_template' => [
        'api_key' => env('PARSER_API_KEY'),
        'base_url' => env('PARSER_BASE_URL'),
        'rate_limit' => env('PARSER_RATE_LIMIT', 60),
        'timeout' => env('PARSER_TIMEOUT', 30),
        'max_retries' => env('PARSER_MAX_RETRIES', 3),
        'retry_delay' => env('PARSER_RETRY_DELAY', 1)
    ],

];
