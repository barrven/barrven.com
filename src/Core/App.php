<?php

declare(strict_types=1);

namespace Core;

/**
 * Handles one request: secures the session and headers, checks CSRF on
 * state-changing requests, then dispatches to the module that owns the path.
 */
final class App
{
    public static function run(): void
    {
        Security::startSession();
        Security::sendHeaders();

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = Request::path();

        if (Security::isUnsafeMethod($method)) {
            Security::verifyCsrf();
        }

        $router = new Router();

        foreach (Modules::all() as $module) {
            foreach ($module['redirects'] as $from => $to) {
                $router->redirect($from, $to);
            }
            foreach ($module['routes'] as [$routeMethod, $pattern, $handler]) {
                $router->add($routeMethod, $pattern, $handler, $module['name']);
            }
        }

        $router->dispatch($method, $path)->send();
    }
}
