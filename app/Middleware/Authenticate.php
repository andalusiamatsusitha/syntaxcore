<?php

namespace App\Middleware;

use App\Services\AuthService;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;
use Closure;

class Authenticate implements MiddlewareInterface
{
    public function __construct(protected AuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->auth->guest()) {
            return Response::redirect('/admin/login');
        }

        return $next($request);
    }
}
