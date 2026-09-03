<?php

namespace Core\Controller;

use Core\Http\Response;
use Core\View\View;

abstract class Controller
{
    /**
     * The action method currently being executed.
     */
    protected ?string $actionMethod = null;

    /**
     * Set the current action method.
     */
    public function setActionMethod(string $method): static
    {
        $this->actionMethod = $method;
        return $this;
    }

    /**
     * Get the current action method.
     */
    public function getActionMethod(): ?string
    {
        return $this->actionMethod;
    }

    /**
     * Render a view and return a Response.
     * Supports automatic convention: $this->view(), $this->view($data), or explicit: $this->view('name', $data).
     */
    protected function view(string|array|null $view = null, array $data = [], int $status = 200, array $headers = []): Response
    {
        if (is_array($view)) {
            $headers = $status !== 200 && is_array($status) ? $status : $headers;
            $status = is_int($data) ? $data : 200;
            $data = $view;
            $view = null;
        }

        if (is_null($view) || $view === '') {
            $view = $this->resolveConventionalView();
        }

        return View::make($view, $data, $status, $headers);
    }

    /**
     * Resolve the conventional view path based on controller namespace, name, and action method.
     * Example: App\Controllers\Web\HomeController::index -> web/home/index
     */
    protected function resolveConventionalView(): string
    {
        $class = static::class;

        // Remove base namespace App\Controllers\ if present
        $baseNamespace = 'App\\Controllers\\';
        if (str_starts_with($class, $baseNamespace)) {
            $relativeClass = substr($class, strlen($baseNamespace));
        } else {
            $parts = explode('\\', $class);
            $relativeClass = end($parts);
        }

        $segments = explode('\\', $relativeClass);
        $controllerName = array_pop($segments);

        // Strip 'Controller' suffix if present
        if (str_ends_with($controllerName, 'Controller')) {
            $controllerName = substr($controllerName, 0, -strlen('Controller'));
        }

        $pathSegments = [];

        // Add normalized directory segments (e.g. Web -> web, Admin -> admin)
        foreach ($segments as $segment) {
            $pathSegments[] = $this->normalizeSegment($segment);
        }

        // Add normalized controller segment (e.g. HomeController -> home)
        $pathSegments[] = $this->normalizeSegment($controllerName);

        // Add normalized action method segment (e.g. index -> index)
        $method = $this->actionMethod ?? $this->detectCallingActionMethod();
        $pathSegments[] = $this->normalizeSegment($method);

        return implode('/', array_filter($pathSegments, fn($s) => $s !== ''));
    }

    /**
     * Normalize a name segment into kebab-case / lowercase.
     */
    protected function normalizeSegment(string $value): string
    {
        $kebab = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
        $kebab = str_replace('_', '-', $kebab);
        return trim($kebab, '-');
    }

    /**
     * Detect the calling action method via backtrace if not explicitly set.
     */
    protected function detectCallingActionMethod(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $frame) {
            if (isset($frame['class']) && $frame['class'] === static::class) {
                if (!in_array($frame['function'], ['view', 'resolveConventionalView', 'detectCallingActionMethod'], true)) {
                    return $frame['function'];
                }
            }
        }

        return 'index';
    }

    /**
     * Return a JSON response.
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return Response::json($data, $status, $headers);
    }

    /**
     * Return a redirect response.
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}
