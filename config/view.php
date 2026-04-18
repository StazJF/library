<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views.
    |
    */
    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application.
    |
    */
    'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),

    /*
    |--------------------------------------------------------------------------
    | Blade View Cache
    |--------------------------------------------------------------------------
    |
    | When enabled, Blade will cache compiled templates for performance.
    | Disabling this may be useful during local development.
    |
    */
    'cache' => env('VIEW_CACHE', true),
];
