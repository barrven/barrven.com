<?php

declare(strict_types=1);

namespace Core;

/** Read-only helpers for the incoming request. */
final class Request
{
    /** Decoded URL path without query string, e.g. "/tierlist/lists/3". */
    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';

        return $path === '' ? '/' : $path;
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /** True for fetch()/API requests that expect a JSON reply. */
    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || isset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    /** Decoded JSON body as an array; throws a 400 if it isn't valid JSON. */
    public static function json(): array
    {
        $data = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($data)) {
            throw new HttpException(400, 'Invalid JSON body');
        }

        return $data;
    }

    public static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }
}
