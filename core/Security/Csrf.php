<?php

namespace Core\Security;

class Csrf
{
    public const TOKEN_KEY = '_token';

    /**
     * Ensure session is active.
     */
    public static function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Get current CSRF token from session or generate a new one.
     */
    public static function token(): string
    {
        static::ensureSessionStarted();

        if (empty($_SESSION[static::TOKEN_KEY]) || !is_string($_SESSION[static::TOKEN_KEY])) {
            $_SESSION[static::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[static::TOKEN_KEY];
    }

    /**
     * Generate and store a new CSRF token in session.
     */
    public static function regenerateToken(): string
    {
        static::ensureSessionStarted();
        return $_SESSION[static::TOKEN_KEY] = bin2hex(random_bytes(32));
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
}
