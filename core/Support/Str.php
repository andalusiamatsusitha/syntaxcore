<?php

namespace Core\Support;

class Str
{
    /**
     * Convert a string to kebab-case slug.
     */
    public static function slug(string $title, string $separator = '-'): string
    {
        $title = preg_replace('![' . preg_quote($separator === '-' ? '_' : '-') . ']+!u', $separator, $title);
        $title = preg_replace('/[^\pL\pN\s' . preg_quote($separator) . ']+/u', '', mb_strtolower($title, 'UTF-8'));
        $title = preg_replace('/[\s]+/', $separator, $title);
        return trim($title, $separator);
    }

    /**
     * Convert a value to studly caps (PascalCase).
     */
    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    /**
     * Convert a value to camelCase.
     */
    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    /**
     * Generate a random alphanumeric string.
     */
    public static function random(int $length = 16): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * Determine if a given string contains a given substring.
     */
    public static function contains(string $haystack, string|iterable $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     */
    public static function startsWith(string $haystack, string|iterable $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     */
    public static function endsWith(string $haystack, string|iterable $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }
}
