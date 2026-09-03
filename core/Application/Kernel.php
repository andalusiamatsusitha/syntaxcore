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

    /**
     * The application's route middleware aliases.
     */
    protected array $routeMiddleware = [];

    /**
     * The priority-sorted list of middleware.
     */
    protected array $middlewarePriority = [];

    protected mixed $routeLoader = null;
    protected bool $routesLoaded = false;

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        $this->bootstrapMiddleware();
    }

    protected function bootstrapMiddleware(): void
    {
        $config = $this->app->config('middleware', []);

        $this->middleware = $config['global'] ?? [];
        $this->routeMiddleware = $config['aliases'] ?? [];
        $this->middlewarePriority = $config['priority'] ?? [];

        $this->router->setMiddlewareAliases($this->routeMiddleware);
        $this->router->setMiddlewarePriority($this->middlewarePriority);
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function getRouteMiddleware(): array
    {
        return $this->routeMiddleware;
    }

    public function setRouteMiddleware(array $routeMiddleware): static
    {
        $this->routeMiddleware = $routeMiddleware;
        $this->router->setMiddlewareAliases($routeMiddleware);
        return $this;
    }

    public function getMiddlewarePriority(): array
    {
        return $this->middlewarePriority;
    }

    public function setMiddlewarePriority(array $priority): static
    {
        $this->middlewarePriority = $priority;
        $this->router->setMiddlewarePriority($priority);
        return $this;
    }

    public function loadRoutesUsing(callable $loader): static
    {
        $this->routeLoader = $loader;
        $this->routesLoaded = false;
        return $this;
    }

    public function bootRoutes(): void
    {
        if ($this->routesLoaded) {
            return;
        }

        if (is_callable($this->routeLoader)) {
            ($this->routeLoader)($this->router, $this->app);
        }

        $this->routesLoaded = true;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function handle(Request $request): Response
    {
        try {
            $this->bootRoutes();

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
