<?php

namespace Core\Application;

use Core\Exceptions\ExceptionHandler;
use Core\Http\Request;
use Core\Routing\Router;
use Core\Support\Arr;

class Application extends Container
{
    protected string $basePath;
    protected array $configs = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(static::class, $this);

        $this->loadEnvironment();
        $this->loadConfigurations();
        $this->registerCoreBindings();
        $this->registerExceptionHandler();
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? '/' . ltrim($path, '\/') : '');
    }

    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path !== '' ? '/' . ltrim($path, '\/') : ''));
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? '/' . ltrim($path, '\/') : ''));
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? '/' . ltrim($path, '\/') : ''));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? '/' . ltrim($path, '\/') : ''));
    }

    public function routesPath(string $path = ''): string
    {
        return $this->basePath('routes' . ($path !== '' ? '/' . ltrim($path, '\/') : ''));
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path !== '' ? '/' . ltrim($path, '\/') : ''));
    }

    protected function loadEnvironment(): void
    {
        $envFile = $this->basePath('.env');
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip enclosing quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    protected function loadConfigurations(): void
    {
        $configDir = $this->configPath();
        if (!is_dir($configDir)) {
            return;
        }

        foreach (scandir($configDir) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $key = pathinfo($file, PATHINFO_FILENAME);
                $this->configs[$key] = require "{$configDir}/{$file}";
            }
        }
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->configs, $key, $default);
    }

    protected function registerCoreBindings(): void
    {
        $this->singleton(Router::class, function () {
            return new Router($this);
        });

        $this->singleton(Request::class, function () {
            return Request::capture();
        });
    }

    protected function registerExceptionHandler(): void
    {
        $debug = (bool) $this->config('app.debug', true);
        $handler = new ExceptionHandler($debug);
        $handler->register();

        $this->singleton(ExceptionHandler::class, function () use ($handler) {
            return $handler;
        });
    }
}
