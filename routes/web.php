<?php

use App\Controllers\Web\HomeController;
use Core\Routing\Router;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);

$router->get('/health', function () {
    return [
        'status' => 'healthy',
        'framework' => 'SyntaxCore',
        'timestamp' => date('c'),
    ];
});
