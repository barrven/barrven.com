<?php

function writeToLog($message, $logFile = '/logs/app.log') {
    // Ensure the logs directory exists and is writable
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function jsonResponse(int $code, array $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}