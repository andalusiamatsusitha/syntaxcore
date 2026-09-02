<?php

namespace Core\Application;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\Pipeline;
use Core\Routing\Router;

class Kernel
{
    protected Application $app;
    protected Router $router;

    /**
     * The application's global HTTP middleware stack.
     */
    protected array $middleware = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        $routesPath = $this->app->routesPath();
        $router = $this->router;

        // Load web routes
        if (file_exists("{$routesPath}/web.php")) {
            require "{$routesPath}/web.php";
        }

        // Load admin routes with '/admin' prefix
        if (file_exists("{$routesPath}/admin.php")) {
            $router->group(['prefix' => 'admin'], function ($router) use ($routesPath) {
                require "{$routesPath}/admin.php";
            });
        }

        // Load api routes with '/api' prefix
        if (file_exists("{$routesPath}/api.php")) {
            $router->group(['prefix' => 'api'], function ($router) use ($routesPath) {
                require "{$routesPath}/api.php";
            });
        }
    }

    public function handle(Request $request): Response
    {
        try {
            $pipeline = new Pipeline($this->app);

            return $pipeline
                ->send($request)
                ->through($this->middleware)
                ->then(function ($request) {
                    return $this->router->dispatch($request);
                });
        } catch (\Throwable $e) {
            /** @var \Core\Exceptions\ExceptionHandler $handler */
            $handler = $this->app->make(\Core\Exceptions\ExceptionHandler::class);
            return $handler->render($request, $e);
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        // Perform post-response cleanup if needed
    }
}
