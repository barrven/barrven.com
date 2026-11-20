<?php

declare(strict_types=1);

namespace Core;

/** Appends timestamped lines to storage/logs/<channel>.log. */
final class Log
{
    public static function error(string $message, string $channel = 'app'): void
    {
        self::write('ERROR', $message, $channel);
    }

    public static function info(string $message, string $channel = 'app'): void
    {
        self::write('INFO', $message, $channel);
    }

    private static function write(string $level, string $message, string $channel): void
    {
        $channel = preg_replace('/[^a-z0-9_-]/i', '', $channel) ?: 'app';
        $line = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $level, $message);

        @file_put_contents(LOG_ROOT . '/' . $channel . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
