<?php

namespace App\Core;

/**
 * Request helper (mínimo).
 */
final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $baseUrl = defined('BASE_URL') ? (string)BASE_URL : '';

        if ($baseUrl !== '' && str_starts_with($uriPath, $baseUrl)) {
            $uriPath = substr($uriPath, strlen($baseUrl));
        }

        $uriPath = '/' . ltrim($uriPath, '/');
        return $uriPath === '//' ? '/' : $uriPath;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '');
    }

    public static function userAgent(): string
    {
        return (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    }
}

