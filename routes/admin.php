<?php

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\CmsController;
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
        $router->get('/profile', [DashboardController::class, 'profile']);
        $router->put('/profile', [DashboardController::class, 'updateProfile']);
        $router->post('/wallpaper', [DashboardController::class, 'uploadWallpaper']);
        $router->delete('/wallpaper', [DashboardController::class, 'deleteWallpaper']);
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

            // CMS Endpoints
            $router->get('/cms/categories', [CmsController::class, 'categories']);
            $router->post('/cms/categories', [CmsController::class, 'storeCategory']);
            $router->put('/cms/categories/{id}', [CmsController::class, 'updateCategory']);
            $router->delete('/cms/categories/{id}', [CmsController::class, 'deleteCategory']);

            $router->get('/cms/tags', [CmsController::class, 'tags']);
            $router->post('/cms/tags', [CmsController::class, 'storeTag']);
            $router->delete('/cms/tags/{id}', [CmsController::class, 'deleteTag']);

            $router->get('/cms/news', [CmsController::class, 'news']);
            $router->get('/cms/news/{id}', [CmsController::class, 'showNews']);
            $router->post('/cms/news', [CmsController::class, 'storeNews']);
            $router->put('/cms/news/{id}', [CmsController::class, 'updateNews']);
            $router->delete('/cms/news/{id}', [CmsController::class, 'deleteNews']);
            $router->post('/cms/news/upload-image', [CmsController::class, 'uploadNewsImage']);

            $router->get('/cms/pages', [CmsController::class, 'pages']);
            $router->get('/cms/pages/{id}', [CmsController::class, 'showPage']);
            $router->post('/cms/pages', [CmsController::class, 'storePage']);
            $router->put('/cms/pages/{id}', [CmsController::class, 'updatePage']);
            $router->delete('/cms/pages/{id}', [CmsController::class, 'deletePage']);

            $router->get('/cms/comments', [CmsController::class, 'comments']);
            $router->put('/cms/comments/{id}/status', [CmsController::class, 'updateCommentStatus']);
            $router->post('/cms/comments/{id}/reply', [CmsController::class, 'replyComment']);
            $router->delete('/cms/comments/{id}', [CmsController::class, 'deleteComment']);

            $router->get('/cms/menus', [CmsController::class, 'publicMenus']);
            $router->post('/cms/menus', [CmsController::class, 'storePublicMenu']);
            $router->put('/cms/menus/{id}', [CmsController::class, 'updatePublicMenu']);
            $router->delete('/cms/menus/{id}', [CmsController::class, 'deletePublicMenu']);
            $router->post('/cms/menus/reorder', [CmsController::class, 'reorderPublicMenus']);
        });

        // Routes accessible ONLY by: superadmin (Level 3)
        $router->group(['middleware' => 'role:superadmin'], function (Router $router) {
            $router->get('/database', [DashboardController::class, 'database']);
            $router->get('/settings', [DashboardController::class, 'settings']);
        });
    });
});
