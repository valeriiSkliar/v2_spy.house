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
        'base_url' => env('PUSH_HOUSE_BASE_URL', 'https://api.push.house/v1'),
        'rate_limit' => env('PUSH_HOUSE_RATE_LIMIT', 1000), // requests per minute
        'timeout' => env('PUSH_HOUSE_TIMEOUT', 45),
        'max_retries' => env('PUSH_HOUSE_MAX_RETRIES', 3),
        'retry_delay' => env('PUSH_HOUSE_RETRY_DELAY', 2)
    ],
    'feedhouse' => [
        'api_key' => env('FEEDHOUSE_API_KEY'),
        'base_url' => env('FEEDHOUSE_BASE_URL', 'https://api.feed.house/internal/v1/feed-campaigns'),
        'rate_limit' => env('FEEDHOUSE_RATE_LIMIT', 100), // requests per minute  
        'timeout' => env('FEEDHOUSE_TIMEOUT', 60), // seconds
        'max_retries' => env('FEEDHOUSE_MAX_RETRIES', 3), // number of retries
        'retry_delay' => env('FEEDHOUSE_RETRY_DELAY', 10), // seconds
        'auth_method' => env('FEEDHOUSE_AUTH_METHOD', 'query'), // query (исправлено: API работает с query параметрами)
        'auth_header_name' => env('FEEDHOUSE_AUTH_HEADER_NAME', 'X-Api-Key'),

        // Параметры поиска по умолчанию
        'default_formats' => ['push', 'inpage'], // Ограничиваем форматы креативов 
        'default_networks' => null, // null = получать динамически из БД через AdvertismentNetwork::getDefaultNetworksForParser()
        'fallback_networks' => ['rollerads', 'richads'], // Fallback сети если БД недоступна
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

    /*
    |--------------------------------------------------------------------------
    | Creative Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for creative validation services including image
    | accessibility checks, content validation, and performance settings.
    |
    */

    'creative_validator' => [
        'image_validation' => [
            'enabled' => env('CREATIVE_IMAGE_VALIDATION_ENABLED', true),
            'timeout' => env('CREATIVE_IMAGE_VALIDATION_TIMEOUT', 15), // seconds
            'max_redirects' => env('CREATIVE_IMAGE_VALIDATION_MAX_REDIRECTS', 3),
            'max_image_size' => env('CREATIVE_IMAGE_VALIDATION_MAX_SIZE', 10485760), // 10MB in bytes
            'min_image_size' => env('CREATIVE_IMAGE_VALIDATION_MIN_SIZE', 1024), // 1KB in bytes
            'verify_ssl' => env('CREATIVE_IMAGE_VALIDATION_VERIFY_SSL', false),
            'allowed_types' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
                'image/tiff'
            ],
        ],

        'performance' => [
            'async_validation' => env('CREATIVE_ASYNC_VALIDATION', false), // Future feature
            'cache_results' => env('CREATIVE_CACHE_VALIDATION_RESULTS', true),
            'cache_ttl' => env('CREATIVE_VALIDATION_CACHE_TTL', 3600), // 1 hour in seconds
        ],

        'fallback' => [
            'skip_on_error' => env('CREATIVE_VALIDATION_SKIP_ON_ERROR', false),
            'log_errors' => env('CREATIVE_VALIDATION_LOG_ERRORS', true),
            'max_validation_time' => env('CREATIVE_MAX_VALIDATION_TIME', 30), // seconds per creative
        ],

        'statistics' => [
            'enabled' => env('CREATIVE_VALIDATION_STATS_ENABLED', true),
            'log_interval' => env('CREATIVE_VALIDATION_STATS_LOG_INTERVAL', 100), // Логировать каждые N валидаций
            'auto_recommendations' => env('CREATIVE_VALIDATION_AUTO_RECOMMENDATIONS', true),
            'detailed_domain_analysis' => env('CREATIVE_VALIDATION_DETAILED_DOMAIN_ANALYSIS', true),
        ]
    ],

];
