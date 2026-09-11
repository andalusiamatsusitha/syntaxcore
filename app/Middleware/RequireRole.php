<?php

namespace App\Middleware;

use App\Services\AuthService;
use Core\Exceptions\HttpException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;
use Closure;

class RequireRole implements MiddlewareInterface
{
    public function __construct(protected AuthService $auth)
    {
    }

    /**
     * Handle incoming request and verify user has one of the required roles.
     *
     * Usage in route: ->middleware('role:superadmin')
     *                 ->middleware('role:admin,superadmin')
     */
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        // 1. Pastikan user sudah terautentikasi
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

        // 2. Jika tidak ada filter role yang diminta, loloskan
        if (empty($roles)) {
            return $next($request);
        }

        // 3. Superadmin selalu memiliki bypass akses untuk semua role
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        // 4. Periksa apakah peran user cocok dengan salah satu peran yang diizinkan
        if (!$user->hasRole($roles)) {
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->path(), '/admin/api')) {
                return Response::json([
                    'error' => true,
                    'status' => 403,
                    'message' => 'Akses ditolak. Peran (' . $user->roleSlug() . ') tidak memiliki izin untuk resource ini.',
                ], 403);
            }

            throw new HttpException(403, 'Akses ditolak. Peran Anda tidak memiliki izin untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
