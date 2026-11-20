<?php

declare(strict_types=1);

// Loads key=value pairs from the git-ignored .env into the environment, then
// exposes everything the app needs as constants in one place.

$envPath = APP_ROOT . '/.env';

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
define('APP_URL', rtrim(getenv('APP_URL') ?: '', '/'));
define('APP_NAME', 'barrven');
define('APP_TIMEZONE', 'America/New_York');

define('SRC_ROOT', APP_ROOT . '/src');
define('MODULES_ROOT', APP_ROOT . '/modules');
define('PUBLIC_ROOT', APP_ROOT . '/public');
define('STORAGE_ROOT', APP_ROOT . '/storage');
define('LOG_ROOT', STORAGE_ROOT . '/logs');
