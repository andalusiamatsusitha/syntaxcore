<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global HTTP Middleware Stack
    |--------------------------------------------------------------------------
    |
    | These middleware are run during every request to your application.
    | Listed middleware are executed in the order defined below.
    |
    */
    'global' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Middleware aliases can be assigned to specific routes or route groups.
    | Example: 'auth' => \App\Middleware\Authenticate::class
    |
    */
    'aliases' => [
        'auth' => \App\Middleware\Authenticate::class,
        'guest' => \App\Middleware\RedirectIfAuthenticated::class,
        'csrf' => \App\Middleware\VerifyCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Execution Priority
    |--------------------------------------------------------------------------
    |
    | If specified, the middleware listed here will execute in this exact
    | relative order when present in the stack.
    |
    */
    'priority' => [
        \App\Middleware\VerifyCsrfToken::class,
        \App\Middleware\Authenticate::class,
        \App\Middleware\RedirectIfAuthenticated::class,
    ],
];
