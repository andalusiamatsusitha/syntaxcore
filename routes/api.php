<?php

use Core\Routing\Router;

/** @var Router $router */

$router->get('/v1/status', function () {
    return [
        'status' => 'success',
        'api_version' => 'v1',
        'message' => 'SyntaxCore API is online',
    ];
});
