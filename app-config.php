<?php

declare(strict_types=1);

// Load key=value pairs from .env into the environment, then expose everything
// the app needs as constants in one place.

$envPath = __DIR__ . '/.env';

if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('APP_URL', getenv('APP_URL') ?: '');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('APP_ROOT', __DIR__);
define('PUBLIC_ROOT', __DIR__ . '/public');
define('LOG_ROOT', __DIR__ . '/logs');
