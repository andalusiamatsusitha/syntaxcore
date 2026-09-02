<?php

namespace Core\View;

use Core\Http\Response;
use Exception;

class View
{
    protected static ?string $basePath = null;
    protected static array $sharedData = [];

    public static function setBasePath(string $path): void
    {
        static::$basePath = rtrim($path, '/\\');
    }

    public static function getBasePath(): string
    {
        if (is_null(static::$basePath)) {
            static::$basePath = dirname(__DIR__, 2) . '/resources/views';
        }
        return static::$basePath;
    }

    public static function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            static::$sharedData = array_merge(static::$sharedData, $key);
        } else {
            static::$sharedData[$key] = $value;
        }
    }

    public static function render(string $view, array $data = []): string
    {
        $normalizedPath = str_replace('.', '/', $view);
        $file = static::getBasePath() . '/' . ltrim($normalizedPath, '/') . '.php';

        if (!file_exists($file)) {
            throw new Exception("View [{$view}] not found at [{$file}].");
        }

        $allData = array_merge(static::$sharedData, $data);

        extract($allData, EXTR_SKIP);

        ob_start();

        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    public static function make(string $view, array $data = [], int $status = 200, array $headers = []): Response
    {
        $html = static::render($view, $data);
        return Response::html($html, $status, $headers);
    }
}
