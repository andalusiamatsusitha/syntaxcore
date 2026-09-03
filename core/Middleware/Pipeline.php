<?php

namespace Core\Middleware;

use Core\Application\Container;
use Core\Http\Request;
use Closure;

class Pipeline
{
    protected ?Container $container;
    protected mixed $passable;
    protected array $pipes = [];

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                if (is_object($pipe)) {
                    if ($pipe instanceof MiddlewareInterface) {
                        return $pipe->handle($passable, $stack);
                    }
                    if (method_exists($pipe, 'handle')) {
                        return $pipe->handle($passable, $stack);
                    }
                }

                if (is_string($pipe)) {
                    $instance = $this->container ? $this->container->make($pipe) : new $pipe();
                    if ($instance instanceof MiddlewareInterface) {
                        return $instance->handle($passable, $stack);
                    }
                    if (method_exists($instance, 'handle')) {
                        return $instance->handle($passable, $stack);
                    }
                }

                return $stack($passable);
            };
        };
    }
}
