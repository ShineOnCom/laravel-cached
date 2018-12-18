<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Cache Decorator
    |--------------------------------------------------------------------------
    |
    | You may change the global cache decorator class used throughout the
    | laravel-cached API here.
    |
    */

    'decorator' => \More\Laravel\Cached\Support\CacheDecorator::class,

    /*
    |--------------------------------------------------------------------------
    | Builder Macros
    |--------------------------------------------------------------------------
    |
    | We add some special macros to builder to make laravel-cached more of a
    | joy to work about. If these collide with any existing macros, you may
    | change their names here.
    |
    */

    'macros' => [
        'builder' => [
            'decorate' => 'decorate',
            'cached' => 'cached',
            'cachedOrFail' => 'cachedOrFail',
        ],
    ],
];
