<?php

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use Core\Routing\Router;

/** @var Router $router */

// Guest routes (accessible only by unauthenticated visitors)
$router->group(['middleware' => 'guest'], function (Router $router) {
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// Protected admin routes (accessible only by authenticated admins)
$router->group(['middleware' => 'auth'], function (Router $router) {
    $router->get('/', [DashboardController::class, 'index']);
    $router->post('/logout', [AuthController::class, 'logout']);
});
