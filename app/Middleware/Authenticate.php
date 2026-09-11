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
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->path(), '/admin/api')) {
                return Response::json([
                    'error' => true,
                    'status' => 401,
                    'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
                ], 401);
            }

            return Response::redirect('/admin/login');
        }

        return $next($request);
    }
}
