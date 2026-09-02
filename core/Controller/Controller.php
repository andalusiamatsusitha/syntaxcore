<?php

namespace Core\Controller;

use Core\Http\Response;
use Core\View\View;

abstract class Controller
{
    /**
     * Render a view and return a Response.
     */
    protected function view(string $view, array $data = [], int $status = 200, array $headers = []): Response
    {
        return View::make($view, $data, $status, $headers);
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
