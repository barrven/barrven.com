<?php

declare(strict_types=1);

// Shared setup for every request: config, autoloading, error handling,
// timezone. public/index.php requires this and then calls Core\App::run().

define('APP_ROOT', dirname(__DIR__));

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

// Core\Foo           -> src/Core/Foo.php
// Modules\Tierlist\X -> modules/tierlist/X.php
spl_autoload_register(static function (string $class): void {
    $parts = explode('\\', $class);

    if ($parts[0] === 'Core') {
        $file = SRC_ROOT . '/' . implode('/', $parts) . '.php';
    } elseif ($parts[0] === 'Modules' && count($parts) >= 3) {
        $module = strtolower($parts[1]);
        $file = MODULES_ROOT . '/' . $module . '/' . implode('/', array_slice($parts, 2)) . '.php';
    } else {
        return;
    }

    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set(APP_TIMEZONE);

Core\ErrorHandler::register();
