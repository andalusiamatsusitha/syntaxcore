<?php

namespace Core\Security;

use Core\Session\Session;

class Csrf
{
    public const TOKEN_KEY = '_token';

    /**
     * Get current CSRF token from session or generate a new one.
     */
    public static function token(): string
    {
        $token = Session::get(static::TOKEN_KEY);

        if (empty($token) || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(static::TOKEN_KEY, $token);
        }

        return $token;
    }

    /**
     * Generate and store a new CSRF token in session.
     */
    public static function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(static::TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Validate given token against the session token using timing-safe comparison.
     */
    public static function validate(?string $token): bool
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        $knownToken = static::token();
        return hash_equals($knownToken, $token);
    }

    /**
     * Generate HTML hidden input field containing the CSRF token.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . static::TOKEN_KEY . '" value="' . htmlspecialchars(static::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
