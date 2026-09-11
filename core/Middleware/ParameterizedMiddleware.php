<?php

namespace Core\Middleware;

use Closure;
use Core\Application\Container;
use Core\Http\Request;

class ParameterizedMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected mixed $target,
        protected array $parameters = [],
        protected ?Container $container = null
    ) {
    }

    public function getTargetClass(): string
    {
        return is_string($this->target)
            ? $this->target
            : (is_object($this->target) ? get_class($this->target) : '');
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $instance = is_string($this->target)
            ? ($this->container ? $this->container->make($this->target) : new $this->target())
            : $this->target;

        return $instance->handle($request, $next, ...$this->parameters);
    }
}
