<?php

namespace App\Middleware;

use App\Services\AuthService;
use Core\Exceptions\HttpException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;
use Closure;

class RequireLevel implements MiddlewareInterface
{
    public function __construct(protected AuthService $auth)
    {
    }

    /**
     * Handle incoming request and verify user meets minimum numeric level.
     *
     * Usage in route: ->middleware('level:2') // Admin level (2) & Superadmin (3)
     *                 ->middleware('level:3') // Superadmin only (3)
     */
    public function handle(Request $request, Closure $next, string|int $minLevel = 1): mixed
    {
        if ($this->auth->guest()) {
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->path(), '/admin/api')) {
                return Response::json([
                    'error' => true,
                    'status' => 401,
                    'message' => 'Silakan login terlebih dahulu.',
                ], 401);
            }

            return Response::redirect('/admin/login');
        }

        $user = $this->auth->user();
        $minLevel = (int) $minLevel;

        if (!$user->hasMinLevel($minLevel)) {
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->path(), '/admin/api')) {
                return Response::json([
                    'error' => true,
                    'status' => 403,
                    'message' => 'Akses ditolak. Level akun (' . $user->roleLevel() . ') tidak mencukupi batas minimal level (' . $minLevel . ').',
                ], 403);
            }

            throw new HttpException(403, 'Akses ditolak. Level akun Anda tidak mencukupi untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
