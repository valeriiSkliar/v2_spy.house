<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resend API Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('RESEND_API_KEY'),

    'from' => env('RESEND_FROM', 'SPY.HOUSE <noreply@spy.house>'),

    'audience_id' => env('RESEND_AUDIENCE_ID'),


    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    */

    'timeout' => env('RESEND_TIMEOUT', 30),

    'verify_ssl' => env('RESEND_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Resend API Endpoints
    |--------------------------------------------------------------------------
    */

    'api_url' => env('RESEND_API_URL', 'https://api.resend.com'),

    'endpoints' => [
        'emails' => '/emails',
        'audiences' => '/audiences',
        'broadcasts' => '/broadcasts',
        'contacts' => '/contacts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */


    'default_tags' => env('RESEND_DEFAULT_TAGS', []),

    'default_headers' => [
        'X-Mailer' => 'Laravel-Resend',
        'X-Application' => env('APP_NAME', 'spy.house'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audience Management
    |--------------------------------------------------------------------------
    */

    'auto_add_contacts' => env('RESEND_AUTO_ADD_CONTACTS', false),

    'unsubscribe_url' => env('RESEND_UNSUBSCRIBE_URL', env('APP_URL') . '/unsubscribe'),

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */

    'log_errors' => env('RESEND_LOG_ERRORS', true),

    'throw_exceptions' => env('RESEND_THROW_EXCEPTIONS', true),

];
