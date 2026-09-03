<?php

use Core\Application\Application;
use Core\Application\Kernel;
use Core\Routing\Router;

$app = new Application(dirname(__DIR__));

$app->singleton(Kernel::class, function (Application $app) {
    $kernel = new Kernel($app, $app->make(Router::class));

    $kernel->loadRoutesUsing(function (Router $router, Application $app) {
        $routesPath = $app->routesPath();

        // Web routes
        if (file_exists("{$routesPath}/web.php")) {
            require "{$routesPath}/web.php";
        }

        // Admin routes with '/admin' prefix
        if (file_exists("{$routesPath}/admin.php")) {
            $router->group(['prefix' => 'admin'], function ($router) use ($routesPath) {
                require "{$routesPath}/admin.php";
            });
        }

        // API routes with '/api' prefix
        if (file_exists("{$routesPath}/api.php")) {
            $router->group(['prefix' => 'api'], function ($router) use ($routesPath) {
                require "{$routesPath}/api.php";
            });
        }
    });

    return $kernel;
});

return $app;
