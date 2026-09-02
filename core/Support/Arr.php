<?php

namespace Core\Support;

use ArrayAccess;

class Arr
{
    /**
     * Get an item from an array using "dot" notation.
     */
    public static function get(mixed $array, ?string $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default instanceof \Closure ? $default() : $default;
            }
        }

        return $array;
    }

    /**
     * Determine if an item or items exist in an array using "dot" notation.
     */
    public static function has(array $array, string $key): bool
    {
        if (empty($array)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        $subArray = $array;
        foreach (explode('.', $key) as $segment) {
            if (is_array($subArray) && array_key_exists($segment, $subArray)) {
                $subArray = $subArray[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter array to only specified keys.
     */
    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Filter array excluding specified keys.
     */
    public static function except(array $array, array|string $keys): array
    {
        return array_diff_key($array, array_flip((array) $keys));
    }
}
