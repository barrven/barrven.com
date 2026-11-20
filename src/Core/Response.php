<?php

declare(strict_types=1);

namespace Core;

/** What a route handler returns. Build one with the static constructors. */
final class Response
{
    /** @var (callable(): void)|null Writes the body directly (used for files). */
    private $streamer = null;

    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-store'],
        );
    }

    public static function text(string $text, int $status = 200): self
    {
        return new self($text, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Redirect to a site-relative path. External URLs are refused. */
    public static function redirect(string $path, int $status = 302): self
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            $path = '/';
        }

        return new self('', $status, ['Location' => $path]);
    }

    /**
     * Stream a file from disk. Supports HTTP Range requests, which browsers
     * need to seek in (and, in Safari, to play) video at all.
     */
    public static function file(string $path, string $mime, array $extraHeaders = []): self
    {
        $size = filesize($path);
        $start = 0;
        $end = $size - 1;
        $status = 200;

        $range = $_SERVER['HTTP_RANGE'] ?? '';
        if ($size > 0 && preg_match('/^bytes=(\d*)-(\d*)$/', $range, $m) && ($m[1] !== '' || $m[2] !== '')) {
            if ($m[1] === '') {
                // "bytes=-500" means the last 500 bytes
                $start = max(0, $size - (int) $m[2]);
            } else {
                $start = (int) $m[1];
                if ($m[2] !== '') {
                    $end = min((int) $m[2], $size - 1);
                }
            }

            if ($start > $end || $start >= $size) {
                return new self('', 416, ['Content-Range' => "bytes */$size"]);
            }
            $status = 206;
        }

        $length = $end - $start + 1;
        $headers = $extraHeaders + [
            'Content-Type' => $mime,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($status === 206) {
            $headers['Content-Range'] = "bytes $start-$end/$size";
        }

        $response = new self('', $status, $headers);
        $response->streamer = static function () use ($path, $start, $length): void {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return;
            }
            fseek($handle, $start);
            $remaining = $length;
            while ($remaining > 0 && !feof($handle)) {
                $chunk = fread($handle, min(8192, $remaining));
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
                $remaining -= strlen($chunk);
            }
            fclose($handle);
        };

        return $response;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        if ($this->streamer !== null) {
            ($this->streamer)();
        } else {
            echo $this->body;
        }
    }

    public static function reasonPhrase(int $status): string
    {
        return match ($status) {
            400 => 'Bad request',
            401 => 'Not signed in',
            403 => 'Forbidden',
            404 => 'Page not found',
            405 => 'Method not allowed',
            413 => 'Upload too large',
            419 => 'Your session expired. Reload the page and try again.',
            default => $status >= 500 ? 'Something went wrong' : 'Error',
        };
    }
}
