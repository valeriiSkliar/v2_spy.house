<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PAY2.HOUSE Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for PAY2.HOUSE payment system.
    |
    */

    'api_key' => env('PAY2_HOUSE_API_KEY', '1jdnsdfnjkdnskfnksdnkfdksfkdQopTIC9Zf8UDx0RtZKNGeobEwdL1yyC5Q'),
    'api_url' => env('PAY2_HOUSE_API_URL', 'https://api.pay2.house'),
    'key_id' => env('PAY2_KEY_ID', 'KN2508182990'),
    'merchant_id' => env('PAY2_HOUSE_MERCHANT_ID', 'SN2633804563mk'),

    /*
    |--------------------------------------------------------------------------
    | Test Server Configuration (for local development)
    |--------------------------------------------------------------------------
    */

    'test_mode' => env('PAY2_TEST_MODE', true),
    'test_api_url' => env('PAY2_TEST_API_URL', 'http://localhost:3000'),
    'test_api_key' => env('PAY2_TEST_API_KEY', 'test_api_key_12345'),
    'test_secret_key' => env('PAY2_TEST_SECRET_KEY', 'test_secret_key_12345'),
    'test_merchant_id' => env('PAY2_TEST_MERCHANT_ID', 'SN6829106944'),

    /*
    |--------------------------------------------------------------------------
    | App URLs for payment returns
    |--------------------------------------------------------------------------
    */

    'return_url' => env('APP_URL', 'http://localhost:8000') . '/tariffs/payment/success',
    'cancel_url' => env('APP_URL', 'http://localhost:8000') . '/tariffs/payment/cancel',
    'webhook_url' => env('APP_URL', 'http://localhost:8000') . '/api/pay2/webhook',

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    */

    'default_currency' => 'USD',
    'payment_deadline_seconds' => 600, // 10 minutes
];
