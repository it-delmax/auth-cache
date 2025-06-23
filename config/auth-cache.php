<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings
    |--------------------------------------------------------------------------
    |
    | Define cache time-to-live (TTL) values in seconds for different 
    | data types. Adjust these values based on your application needs.
    |
    */

    'ttl' => [
        'user' => env('AUTH_CACHE_USER_TTL', 6 * 60 * 60), // 6 hours
        'token' => env('AUTH_CACHE_TOKEN_TTL', 12 * 60 * 60), // 12 hours  
        'api_access' => env('AUTH_CACHE_API_ACCESS_TTL', 24 * 60 * 60), // 24 hours
        'api_slug' => env('AUTH_CACHE_API_SLUG_TTL', 24 * 60 * 60), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes used for cache keys to avoid collisions and organize cache data.
    | These prefixes are used internally by the package.
    |
    */

    'prefixes' => [
        'user' => env('AUTH_CACHE_USER_PREFIX', 'user:'),
        'token' => env('AUTH_CACHE_TOKEN_PREFIX', 'token:'),
        'api_access' => env('AUTH_CACHE_API_ACCESS_PREFIX', 'api_access:'),
        'api_slug' => env('AUTH_CACHE_API_SLUG_PREFIX', 'api_slug:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Database connections used by the package models. Configure these
    | to match your application's database setup.
    |
    */

    'connections' => [
        'users' => env('AUTH_CACHE_USER_CONNECTION', 'etg_utf8'),
        'tokens' => env('AUTH_CACHE_TOKEN_CONNECTION', 'mysql'),
        'api_data' => env('AUTH_CACHE_API_CONNECTION', 'etg_utf8'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize table names used by the package models if needed.
    |
    */

    'tables' => [
        'users' => env('AUTH_CACHE_USERS_TABLE', 'USERS_VIEW'),
        'tokens' => env('AUTH_CACHE_TOKENS_TABLE', 'personal_access_tokens'),
        'apis' => env('AUTH_CACHE_APIS_TABLE', 'ETG_API'),
        'api_users' => env('AUTH_CACHE_API_USERS_TABLE', 'ETG_API_USERS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic cache warming functionality.
    |
    */

    'warming' => [
        'enabled' => env('AUTH_CACHE_WARMING_ENABLED', true),
        'chunk_size' => env('AUTH_CACHE_WARMING_CHUNK_SIZE', 100),
        'queue' => env('AUTH_CACHE_WARMING_QUEUE', 'cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic cache invalidation and cleanup.
    |
    */

    'invalidation' => [
        'enabled' => env('AUTH_CACHE_INVALIDATION_ENABLED', true),
        'schedule' => env('AUTH_CACHE_INVALIDATION_SCHEDULE', 'hourly'), // hourly, daily, etc.
        'queue' => env('AUTH_CACHE_INVALIDATION_QUEUE', 'cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure logging for cache operations.
    |
    */

    'logging' => [
        'enabled' => env('AUTH_CACHE_LOGGING_ENABLED', true),
        'level' => env('AUTH_CACHE_LOG_LEVEL', 'info'), // debug, info, warning, error
        'channel' => env('AUTH_CACHE_LOG_CHANNEL', null), // null uses default log channel
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize cache performance.
    |
    */

    'performance' => [
        'serialize_cache' => env('AUTH_CACHE_SERIALIZE', true),
        'compress_cache' => env('AUTH_CACHE_COMPRESS', false),
        'fallback_to_db' => env('AUTH_CACHE_FALLBACK_DB', true),
    ],
];