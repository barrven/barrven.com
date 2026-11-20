<?php

declare(strict_types=1);

namespace Core;

/** Session hardening, security headers and CSRF protection for every request. */
final class Security
{
    private const SESSION_NAME = 'barrven_sid';

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');    // reject session IDs the server didn't issue
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** New session ID after a login, so a planted ID can't be reused (session fixation). */
    public static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }

    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // Scripts only from this site: no inline <script>, no inline event
        // handlers, no third-party CDNs. Inline styles stay allowed because
        // the sheet-music renderer and several views set element styles.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "media-src 'self' blob:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        if (Request::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function isUnsafeMethod(string $method): bool
    {
        return !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * Every POST/PUT/PATCH/DELETE must carry the session's CSRF token, either
     * as a "_csrf" form field or an "X-CSRF-Token" header (site.js adds the
     * header to fetch calls), and must not come from another origin.
     */
    public static function verifyCsrf(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '') {
            $originHost = (string) parse_url($origin, PHP_URL_HOST);
            $originPort = parse_url($origin, PHP_URL_PORT);
            if ($originPort !== null) {
                $originHost .= ':' . $originPort;
            }
        }
        if ($origin !== '' && $originHost !== ($_SERVER['HTTP_HOST'] ?? '')) {
            throw new HttpException(403, 'Cross-origin request refused');
        }

        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
        $expected = $_SESSION['_csrf'] ?? '';

        if (!is_string($sent) || !is_string($expected) || $expected === '' || !hash_equals($expected, $sent)) {
            throw new HttpException(419);
        }
    }
}
