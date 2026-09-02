<?php

use Core\Application\Application;
use Core\Application\Kernel;
use Core\Routing\Router;

$app = new Application(dirname(__DIR__));

$app->singleton(Kernel::class, function ($app) {
    return new Kernel($app, $app->make(Router::class));
});

return $app;
