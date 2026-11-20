<?php

declare(strict_types=1);

// Small global helpers for use in views. Everything a template prints
// should go through e() unless it is known-safe HTML.

/** Escape a value for HTML text or a quoted attribute. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Site-relative URL for a path, e.g. url('/tierlist'). */
function url(string $path = '/'): string
{
    return '/' . ltrim($path, '/');
}

/**
 * URL for a file under public/assets, with a version query string from its
 * modification time so browsers pick up changes without a manual cache bust.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = PUBLIC_ROOT . '/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '0';

    return '/assets/' . $path . '?v=' . $version;
}

function csrf_token(): string
{
    return Core\Security::csrfToken();
}

/** Hidden input carrying the CSRF token, for plain HTML forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
