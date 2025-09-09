<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration for Task Module
    |--------------------------------------------------------------------------
    |
    | Cấu hình cache cho module Task với Redis
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix cho tất cả cache keys của module Task
    |
    */
    'prefix' => env('CACHE_PREFIX', 'task_module'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Levels
    |--------------------------------------------------------------------------
    |
    | Các mức TTL khác nhau cho cache
    |
    */
    'ttl_levels' => [
        'critical' => 300,    // 5 minutes
        'important' => 900,   // 15 minutes
        'normal' => 1800,      // 30 minutes
        'background' => 3600, // 1 hour
        'long_term' => 7200   // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Size Limits
    |--------------------------------------------------------------------------
    |
    | Giới hạn kích thước cho cache values
    |
    */
    'max_value_size' => env('CACHE_MAX_VALUE_SIZE', 1048576), // 1MB

    /*
    |--------------------------------------------------------------------------
    | Cache Statistics
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho cache statistics
    |
    */
    'statistics' => [
        'enabled' => env('CACHE_STATISTICS_ENABLED', true),
        'ttl' => env('CACHE_STATISTICS_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Events
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho cache events
    |
    */
    'events' => [
        'enabled' => env('CACHE_EVENTS_ENABLED', true),
        'log_level' => env('CACHE_EVENTS_LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho cache tags
    |
    */
    'tags' => [
        'enabled' => env('CACHE_TAGS_ENABLED', true),
        'ttl' => env('CACHE_TAGS_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warm Up
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho cache warm up
    |
    */
    'warm_up' => [
        'enabled' => env('CACHE_WARM_UP_ENABLED', true),
        'batch_size' => env('CACHE_WARM_UP_BATCH_SIZE', 100),
        'delay_between_batches' => env('CACHE_WARM_UP_DELAY', 1000), // milliseconds
    ],
];
