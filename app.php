<?php

declare(strict_types=1);

require __DIR__ . '/app-config.php';

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_ROOT . '/php-error.log');
}

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barrven</title>
</head>
<body>
    <h1>Coooool</h1>
    <p>The site is up and runningdddddd.</p>
    <p><?php echo 'host'. DB_NAME ?></p>
</body>
</html>
