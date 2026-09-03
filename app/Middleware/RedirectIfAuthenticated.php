<?php

namespace App\Middleware;

use App\Services\AuthService;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;
use Closure;

class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function __construct(protected AuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->auth->check()) {
            return Response::redirect('/admin');
        }

        return $next($request);
    }
}
