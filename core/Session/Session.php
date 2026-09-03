<?php

namespace Core\Session;

use Core\Support\Arr;

class Session
{
    /**
     * Ensure PHP native session is active.
     */
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Check if session is currently active.
     */
    public static function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Get an item from the session (supports dot notation).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        static::start();
        return Arr::get($_SESSION ?? [], $key, $default);
    }

    /**
     * Store an item in the session (supports dot notation).
     */
    public static function set(string $key, mixed $value): void
    {
        static::start();

        $keys = explode('.', $key);
        $array = &$_SESSION;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }
            $array = &$array[$k];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Determine if an item exists in the session (supports dot notation).
     */
    public static function has(string $key): bool
    {
        static::start();
        return Arr::has($_SESSION ?? [], $key);
    }

    /**
     * Remove an item from the session (supports dot notation).
     */
    public static function remove(string $key): void
    {
        static::start();

        $keys = explode('.', $key);
        $array = &$_SESSION;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($array[$k]) || !is_array($array[$k])) {
                return;
            }
            $array = &$array[$k];
        }

        unset($array[array_shift($keys)]);
    }

    /**
     * Retrieve all session items.
     */
    public static function all(): array
    {
        static::start();
        return $_SESSION ?? [];
    }

    /**
     * Regenerate the session ID.
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        static::start();
        if (static::isStarted() && !headers_sent()) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }

    /**
     * Clear all session data.
     */
    public static function clear(): void
    {
        static::start();
        $_SESSION = [];
    }

    /**
     * Invalidate and destroy the current session.
     */
    public static function destroy(): void
    {
        static::start();
        $_SESSION = [];
        if (static::isStarted() && !headers_sent()) {
            session_destroy();
        }
    }
}
