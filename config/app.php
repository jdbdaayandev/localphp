<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env(
        'APP_NAME',
        'LocalPHP'
    ),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    'env' => env(
        'APP_ENV',
        'local'
    ),

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    */

    'debug' => filter_var(
        env(
            'APP_DEBUG',
            env('APP_ENV', 'local') !== 'production'
        ),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => rtrim(
        env(
            'APP_URL',
            'http://localhost/localphp'
        ),
        '/'
    ),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */

    'timezone' => env(
        'APP_TIMEZONE',
        'Asia/Manila'
    ),

    // PHP strict_types is a per-file declaration, not a runtime switch.
    // This setting controls generated application files and conventions.
    'strict_types' => filter_var(env('APP_STRICT_TYPES', true), FILTER_VALIDATE_BOOLEAN),

    'maintenance' => filter_var(env('APP_MAINTENANCE', false), FILTER_VALIDATE_BOOLEAN),

    'log_level' => env('LOG_LEVEL', 'error'),

    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    */

    'session' => [

        'name' => env(
            'SESSION_NAME',
            'localphp_session'
        ),

        'lifetime' => (int) env(
            'SESSION_LIFETIME',
            120
        ),

        'path' => env(
            'SESSION_PATH',
            '/'
        ),

        'domain' => env(
            'SESSION_DOMAIN',
            ''
        ),

        'secure' => filter_var(
            env(
                'SESSION_SECURE',
                false
            ),
            FILTER_VALIDATE_BOOLEAN
        ),

        'http_only' => true,

        'same_site' => env(
            'SESSION_SAME_SITE',
            'Lax'
        ),
    ],

];