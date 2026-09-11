<?php

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use Core\Routing\Router;

/** @var Router $router */

// Admin routes wrapped with CSRF protection
$router->group(['middleware' => 'csrf'], function (Router $router) {
    // Guest routes (accessible only by unauthenticated visitors)
    $router->group(['middleware' => 'guest'], function (Router $router) {
        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'login']);
    });

    // Protected admin routes (accessible only by authenticated users)
    $router->group(['middleware' => 'auth'], function (Router $router) {
        $router->get('/', [DashboardController::class, 'index']);
        $router->get('/api/menus', [DashboardController::class, 'menus']);
        $router->get('/notifications', [DashboardController::class, 'notifications']);
        $router->post('/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead']);
        $router->post('/notifications/{id}/read', [DashboardController::class, 'markNotificationRead']);
        $router->post('/logout', [AuthController::class, 'logout']);

        // Routes accessible by: admin and superadmin (Level 2+)
        $router->group(['middleware' => 'role:admin,superadmin'], function (Router $router) {
            $router->get('/users', [DashboardController::class, 'users']);
            $router->post('/users', [DashboardController::class, 'storeUser']);
            $router->put('/users/{id}', [DashboardController::class, 'updateUser']);
            $router->delete('/users/{id}', [DashboardController::class, 'deleteUser']);
            $router->get('/roles', [DashboardController::class, 'roles']);
            $router->post('/roles', [DashboardController::class, 'storeRole']);
            $router->put('/roles/{id}', [DashboardController::class, 'updateRole']);
            $router->delete('/roles/{id}', [DashboardController::class, 'deleteRole']);
            $router->get('/reports', [DashboardController::class, 'reports']);
        });

        // Routes accessible ONLY by: superadmin (Level 3)
        $router->group(['middleware' => 'role:superadmin'], function (Router $router) {
            $router->get('/database', [DashboardController::class, 'database']);
            $router->get('/settings', [DashboardController::class, 'settings']);
        });
    });
});
