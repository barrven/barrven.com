<?php

declare(strict_types=1);

namespace Core;

/**
 * Throw from a handler to stop with an HTTP error. The message is shown to
 * the visitor, so keep it free of internal details.
 */
final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : Response::reasonPhrase($status), $status);
    }
}
