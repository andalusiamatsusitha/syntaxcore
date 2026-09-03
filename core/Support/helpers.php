<?php

use Core\Security\Csrf;

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token string.
     */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate an HTML hidden input field with the current CSRF token.
     */
    function csrf_field(): string
    {
        return Csrf::field();
    }
}
