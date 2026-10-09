<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Global Filters
    |--------------------------------------------------------------------------
    |
    | These filters run for every incoming request.
    |
    */
    'global' => [
        'before' => [
            // \App\Filters\BeforeRequestFilter::class,
        ],

        'after' => [
            // \App\Filters\AfterRequestFilter::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Aliases
    |--------------------------------------------------------------------------
    |
    | Use aliases when assigning filters to routes.
    |
    */
    'aliases' => [
       // 'auth' => \App\Filters\AuthFilter::class,
       // 'guest' => \App\Filters\GuestFilter::class,
       // 'csrf' => \App\Filters\CsrfFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Filters
    |--------------------------------------------------------------------------
    |
    | Assign filters to individual routes or route groups.
    |
    */
    'routes' => [
        // 'admin' => ['auth'],
        // 'account' => ['auth', 'csrf'],
    ],

];
