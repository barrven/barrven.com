# barrven.com

Personal PHP website hosting small apps ("modules"): a homepage, a scale
randomizer, a drone/metronome, and a tier list maker.

## Stack

- PHP 8.3, no framework, no Composer. Apache (mod_php) on Ubuntu.
- SQLite, one database file per module (`storage/db/<module>.sqlite`).
- Config from a git-ignored `.env` (`APP_ENV`, `APP_DEBUG`, `APP_URL`), parsed
  by hand in `src/config.php` into constants.
- The server's working tree is the live site: edits to `/var/www/html` go live
  immediately.

## Layout

- `public/`: web root. Only `index.php` (the front controller) and `assets/`.
  - `assets/core/`: shared `site.css` (design tokens + components) and
    `site.js` (nav toggle, `site.fetch`/`site.postJson` with CSRF, dialogs).
  - `assets/<module>/`: that module's CSS/JS (and vendored libraries).
- `src/`: the core.
  - `bootstrap.php`: constants, autoloader, timezone, error handler.
  - `Core/App.php`: per request, starts the session, sends security headers,
    checks CSRF on POST, loads modules, dispatches.
  - `Core/Router.php`, `Request.php`, `Response.php` (html/json/redirect/file
    with Range support), `View.php` (templates + shared layout),
    `Modules.php` (discovers `modules/*/module.php`), `Security.php` (session,
    headers/CSP, CSRF), `Db.php` (per-module SQLite + migrations),
    `Upload.php` (content-sniffed uploads), `ErrorHandler.php`, `Log.php`,
    `HttpException.php`.
  - `templates/layout.php` (the one page shell), `templates/error.php`.
  - `helpers.php`: `e()`, `url()`, `asset()`, `csrf_token()`, `csrf_field()`.
- `modules/<name>/`: one folder per module.
  - `module.php` returns its definition: title, description, nav, url,
    project flag, order, routes, redirects (format documented in
    `src/Core/Modules.php`).
  - Controller classes (namespace `Modules\<Name>\`, autoloaded from this
    folder), `views/`, `migrations/NNN_*.sql`, `data/`, `docs/`.
- `storage/`: git-ignored, not web-accessible. `db/`, `uploads/<module>/`,
  `logs/` (`app.log`, `php-error.log`, per-module logs), `backups/`.
- `deploy/apache-vhost.conf`: copy of the live SSL vhost
  (`/etc/apache2/sites-available/000-default-le-ssl.conf`). Apache rewrites
  every request that isn't a real file under `public/` to `/index.php`.
  Keep the copy in sync if the vhost changes.
- `projects/drone-app/`: a separate git repo (`barrven/drone-app`) embedded as
  a gitlink. It's the drone's original standalone version. The live drone is
  `modules/drone` + `public/assets/drone`, split out of it.

## Adding a module

1. Create `modules/<name>/module.php` with routes. It shows up in the nav and
   on /projects automatically (`nav`, `project`).
2. Render pages with `View::page('<name>', '<view>', $vars, ['title' => ...,
   'styles' => ['<name>/x.css'], 'scripts' => ['<name>/x.js'], 'layout' =>
   'default'|'wide'|'app'])` so they get the shared header and styles.
3. Put CSS/JS in `public/assets/<name>/`. Build on the tokens (`var(--surface)`,
   `var(--accent)`, ...) and components (`.btn`, `.card`, `.dialog`, `.field`,
   `.alert`) in `core/site.css` rather than hard-coding colours.
4. Data: `Db::module('<name>')` plus `migrations/001_*.sql`. Uploads:
   `Upload::store()` into `STORAGE_ROOT . '/uploads/<name>/...'`, served
   through a route that checks ownership via `Response::file()`.

## Conventions and security rules

- `declare(strict_types=1);` at the top of every PHP file, views included.
- Config-as-constants (`src/config.php`), not a config object.
- No inline `<script>` or `on*=""` attributes: the CSP is `script-src 'self'`.
  Pass data to JS with `data-*` attributes; wire events in the module's JS.
- No third-party script CDNs; vendor libraries into `public/assets/<module>/vendor/`.
- Escape all output with `e()`. Use prepared statements only.
- Every POST needs the CSRF token: `csrf_field()` in forms, `site.fetch()` /
  `site.postJson()` for fetch calls. Logout and other state changes are POST.
- Handlers throw `HttpException($status, $safeMessage)` for expected errors.
  Anything else is logged and shown as a generic error, with details only when
  `APP_DEBUG` is on.
- Scope data access to the current user (see `Modules\Tierlist\Store::ownedList`).
- Tier list sign-in is username-only by design (no passwords).
