<?php

use Core\Routing\Router;

/** @var Router $router */

$router->get('/', function () {
    return [
        'area' => 'admin',
        'message' => 'Welcome to SyntaxCore Admin Panel',
    ];
});
