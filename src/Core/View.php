<?php

declare(strict_types=1);

namespace Core;

/**
 * Renders PHP templates. Module views live in modules/<module>/views/, and
 * core ones (layout, error page) in src/templates/. View::page() wraps a
 * view in the shared site layout so every module looks the same.
 */
final class View
{
    /** Render one template to a string. $vars become local variables in it. */
    public static function render(string $module, string $view, array $vars = []): string
    {
        $file = self::path($module, $view);

        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                require $__file;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string) ob_get_clean();
        })($file, $vars);
    }

    /**
     * Render a view inside the site layout.
     *
     * Options:
     *   title    page title (shown as "<title> · barrven")
     *   styles   extra stylesheets under public/assets, e.g. ['tierlist/tierlist.css']
     *   scripts  extra scripts under public/assets (loaded deferred, after site.js)
     *   layout   'default' (centered reading width) or 'wide' (full-width app area)
     */
    public static function page(string $module, string $view, array $vars = [], array $options = []): Response
    {
        $content = self::render($module, $view, $vars);

        $html = self::render('core', 'layout', [
            'content' => $content,
            'title' => $options['title'] ?? null,
            'styles' => $options['styles'] ?? [],
            'scripts' => $options['scripts'] ?? [],
            'layout' => $options['layout'] ?? 'default',
            'activeModule' => $module,
            'navItems' => Modules::navItems(),
        ]);

        return Response::html($html);
    }

    private static function path(string $module, string $view): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $module) || !preg_match('/^[a-zA-Z0-9_\/-]+$/', $view) || str_contains($view, '..')) {
            throw new \InvalidArgumentException("Invalid view name: $module/$view");
        }

        $file = $module === 'core'
            ? SRC_ROOT . '/templates/' . $view . '.php'
            : MODULES_ROOT . '/' . $module . '/views/' . $view . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $module/$view");
        }

        return $file;
    }
}
