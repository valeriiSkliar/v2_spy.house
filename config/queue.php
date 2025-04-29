<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE_DEFAULT', 'collect-ads'),
            'queues' => [
                'default' => env('RABBITMQ_QUEUE_DEFAULT', 'default'),
                'collect-ads' => env('RABBITMQ_QUEUE_PRIORITY', 'collect-ads'),
                'push-house-ads' => env('RABBITMQ_QUEUE_PUSH_HOUSE_ADS', 'push-house-ads'),
                'delayed' => env('RABBITMQ_QUEUE_DELAYED', 'delayed-queue'),
                'mail' => env('RABBITMQ_QUEUE_MAIL', 'mail-queue'),
                'website-downloads' => env('RABBITMQ_QUEUE_WEBSITE_DOWNLOADS', 'website-downloads'),
            ],

            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'worker' => env('RABBITMQ_WORKER', 'horizon'),
            'options' => [
                'queue' => [
                    'default' => [
                        'exchange' => 'default_exchange',
                        'exchange_type' => 'direct',
                        'routing_key' => 'default.key',
                        'prefetch_count' => 10,
                    ],
                    'collect-ads' => [
                        'exchange' => 'collect-ads',
                        'exchange_type' => 'topic',
                        'routing_key' => 'collect-ads.key',
                        'prefetch_count' => 10,
                    ],
                    'delayed' => [
                        'exchange' => 'delayed',
                        'exchange_type' => 'fanout',
                        'routing_key' => 'delayed.key',
                        'prefetch_count' => 5,
                    ],
                    'mail' => [
                        'exchange' => 'mail',
                        'exchange_type' => 'direct',
                        'routing_key' => 'mail.key',
                        'prefetch_count' => 10,
                    ],
                    'website-downloads' => [
                        'exchange' => 'website-downloads',
                        'exchange_type' => 'direct',
                        'routing_key' => 'website-downloads.key',
                        'prefetch_count' => 10,
                    ],
                    'max_queue_size' => env('RABBITMQ_MAX_QUEUE_SIZE', 100),
                    'prefetch_size' => 0,
                    'prefetch_count' => 10,
                    'prioritize_delayed' => false,
                    'queue_max_priority' => 10,
                    'exchange_type' => 'direct',
                    'reroute_failed' => true,
                    'failed_exchange' => 'mail.failed',
                ],
                'ssl_options' => [
                    'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'exchange' => [
                    'name' => env('RABBITMQ_EXCHANGE_NAME', 'mail'),
                    'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                    'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                    'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                    'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
                ],
            ],
        ],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
