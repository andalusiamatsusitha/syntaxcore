<?php

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\MiddlewareInterface;
use Core\Security\Csrf;
use Closure;

class VerifyCsrfToken implements MiddlewareInterface
{
    /**
     * HTTP methods that require CSRF verification.
     */
    protected array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array(strtoupper($request->getMethod()), $this->protectedMethods, true)) {
            $token = $request->input(Csrf::TOKEN_KEY) ?? $request->header('X-CSRF-TOKEN');

            if (!Csrf::validate($token)) {
                return new Response('419 Page Expired (CSRF token mismatch)', 419, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ]);
            }
        }

        return $next($request);
    }
}
