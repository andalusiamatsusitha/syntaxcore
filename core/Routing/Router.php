<?php

namespace Core\Routing;

use Core\Application\Container;
use Core\Exceptions\HttpException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\Pipeline;
use Closure;
use ReflectionMethod;
use ReflectionFunction;

class Router
{
    protected ?Container $container;
    /** @var Route[] */
    protected array $routes = [];
    protected array $groupStack = [];

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function any(string $uri, mixed $action): array
    {
        $routes = [];
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $routes[] = $this->addRoute($method, $uri, $action);
        }
        return $routes;
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    protected function addRoute(string $method, string $uri, mixed $action): Route
    {
        $uri = $this->prefixUri($uri);
        $route = new Route($method, $uri, $action);

        $middlewares = $this->getCurrentGroupMiddlewares();
        if (!empty($middlewares)) {
            $route->middleware($middlewares);
        }

        $this->routes[] = $route;
        return $route;
    }

    protected function prefixUri(string $uri): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        $fullUri = $prefix . '/' . trim($uri, '/');
        return '/' . trim($fullUri, '/');
    }

    protected function getCurrentGroupMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $middlewares = array_merge($middlewares, $m);
            }
        }
        return $middlewares;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $matchedRoute = null;
        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                $matchedRoute = $route;
                break;
            }
        }

        if (!$matchedRoute) {
            throw new HttpException(404, "Route [{$method}] '{$path}' not found.");
        }

        $request->setParams($matchedRoute->getParameters());

        $pipeline = new Pipeline($this->container);

        return $pipeline
            ->send($request)
            ->through($matchedRoute->getMiddleware())
            ->then(function ($request) use ($matchedRoute) {
                return $this->runAction($matchedRoute->getAction(), $request);
            });
    }

    protected function runAction(mixed $action, Request $request): Response
    {
        $result = null;

        if ($action instanceof Closure) {
            $reflector = new ReflectionFunction($action);
            $parameters = $this->resolveActionParameters($reflector, $request);
            $result = $reflector->invokeArgs($parameters);
        } elseif (is_array($action)) {
            [$controller, $method] = $action;
            $controllerInstance = is_string($controller) ? $this->container->make($controller) : $controller;
            $result = $this->callAction($controllerInstance, $method, $request);
        } elseif (is_string($action)) {
            if (str_contains($action, '@')) {
                [$controller, $method] = explode('@', $action, 2);
            } else {
                $controller = $action;
                $method = '__invoke';
            }

            $controllerInstance = $this->container->make($controller);
            $result = $this->callAction($controllerInstance, $method, $request);
        }

        return $this->toResponse($result);
    }

    protected function callAction(object $instance, string $method, Request $request): mixed
    {
        $reflector = new ReflectionMethod($instance, $method);
        $parameters = $this->resolveActionParameters($reflector, $request);
        return $reflector->invokeArgs($instance, $parameters);
    }

    protected function resolveActionParameters(ReflectionMethod|ReflectionFunction $reflector, Request $request): array
    {
        $dependencies = [];
        $routeParams = $request->params();

        foreach ($reflector->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                if ($className === Request::class || is_subclass_of($className, Request::class)) {
                    $dependencies[] = $request;
                    continue;
                }
                $dependencies[] = $this->container->make($className);
                continue;
            }

            if (array_key_exists($name, $routeParams)) {
                $dependencies[] = $routeParams[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                $dependencies[] = null;
            }
        }

        return $dependencies;
    }

    protected function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }
}
