# barrven.com

Personal PHP website. Early stage — currently a bare skeleton, not a working page.

## Stack

- PHP, no framework, no dependency manager (no composer.json).
- SQLite for data storage (`data/app.sqlite`), though `app-config.php` currently
  defines MySQL-style connection constants (`DB_HOST`/`DB_USER`/`DB_PASS`) that
  are unused — this is a leftover inconsistency, not an active MySQL setup.
- Config loaded from a git-ignored `.env` (`APP_ENV`, `APP_DEBUG`, `APP_URL`,
  `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`), parsed by hand in `app-config.php`
  into constants — no vlucas/phpdotenv or similar.

## Layout

- `public/` — web root; `public/index.php` is the sole entry point, just
  requires `app.php`. `public/uploads/` is git-ignored (runtime upload storage).
- `app.php` — bootstraps config, error display/logging, timezone
  (`America/New_York`), session start, and currently inlines the homepage HTML
  directly (placeholder content, not yet templated).
- `app-config.php` — loads `.env`, defines constants (`APP_ROOT`, `PUBLIC_ROOT`,
  `LOG_ROOT`, `DB_*`, etc). Included by `app.php`.
- `data/` — git-ignored, holds `app.sqlite`.
- `logs/` — git-ignored, `php-error.log` written when `APP_DEBUG` is false.

## State

- Repo pushed to `git@github.com:barrven/barrven.com.git` on `master`
  (2026-09-02); remote was empty before this.
- `app.php` still has placeholder/test content in the HTML body ("Coooool",
  "site is up and runningdddddd") — needs real homepage content.
- No templating, routing, or DB access layer built yet — everything is one
  script.

## Conventions

- `declare(strict_types=1);` at the top of every PHP file.
- Config-as-constants pattern (`app-config.php`) rather than a config object/array.
- All site code — PHP, JavaScript, CSS, HTML — goes in `app.php`.
  `public/index.php` is just an entrypoint that requires it; do not add markup
  or logic there.
