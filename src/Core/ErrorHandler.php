<?php

declare(strict_types=1);

namespace Core;

/**
 * Turns every PHP warning into an exception, logs anything unexpected, and
 * shows visitors a generic error page (or JSON for API calls). Details
 * (message, stack trace) are only shown when APP_DEBUG is on.
 */
final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', APP_DEBUG ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', LOG_ROOT . '/php-error.log');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false; // silenced with @
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    public static function handle(\Throwable $e): void
    {
        if ($e instanceof HttpException) {
            $status = $e->status;
            $message = $e->getMessage();
        } else {
            $status = 500;
            $message = Response::reasonPhrase(500);
            Log::error((string) $e);
        }

        // Discard any half-rendered page before sending the error
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $detail = (APP_DEBUG && !($e instanceof HttpException)) ? (string) $e : null;

        try {
            if (Request::wantsJson()) {
                $payload = ['success' => false, 'error' => $message];
                if ($detail !== null) {
                    $payload['debug'] = $detail;
                }
                $response = Response::json($payload, $status);
            } else {
                $response = View::page('core', 'error', [
                    'status' => $status,
                    'message' => $message,
                    'detail' => $detail,
                ], ['title' => $message]);
                $response->status = $status;
            }
            $response->send();
        } catch (\Throwable $inner) {
            // The error page itself failed; fall back to plain text
            Log::error('Error page failed: ' . $inner);
            if (!headers_sent()) {
                http_response_code($status);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo $message;
        }
    }
}
