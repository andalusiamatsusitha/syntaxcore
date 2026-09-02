<?php

namespace Core\Routing;

class Route
{
    protected string $method;
    protected string $uri;
    protected mixed $action;
    protected array $middleware = [];
    protected ?string $name = null;
    protected array $parameters = [];

    public function __construct(string $method, string $uri, mixed $action)
    {
        $this->method = strtoupper($method);
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
    }

    public function middleware(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : func_get_args();
        $this->middleware = array_merge($this->middleware, $middlewares);
        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function matches(string $method, string $path): bool
    {
        if ($this->method !== strtoupper($method)) {
            return false;
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $this->uri);
        $pattern = '#^' . $pattern . '$#';

        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        if (preg_match($pattern, $normalizedPath, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            $this->parameters = $params;
            return true;
        }

        return false;
    }
}
