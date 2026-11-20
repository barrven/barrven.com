<?php

declare(strict_types=1);

namespace Core;

/**
 * Maps METHOD + path to a handler. Patterns use {name} for one path segment
 * or {name:regex} for a constrained one, e.g. "/tierlist/lists/{id:\d+}".
 * Handlers are [ClassName::class, 'method'] pairs or closures, called with
 * the captured parameters as an array, and must return a Response.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable|array, module: string}> */
    private array $routes = [];

    /** @var array<string, string> */
    private array $redirects = [];

    public function add(string $method, string $pattern, callable|array $handler, string $module): void
    {
        $regex = preg_replace_callback(
            '/\{(\w+)(?::((?:[^{}]|\{[^{}]*\})+))?\}/',  // allows quantifiers like {32} inside
            static fn (array $m): string => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $pattern,
        );

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'module' => $module,
        ];
    }

    /** Permanent redirect for an old URL, e.g. "/tierlist.php" => "/tierlist". */
    public function redirect(string $from, string $to): void
    {
        $this->redirects[$from] = $to;
    }

    public function dispatch(string $method, string $path): Response
    {
        if (isset($this->redirects[$path])) {
            return Response::redirect($this->redirects[$path], 301);
        }

        // One canonical URL per page: "/projects/" -> "/projects"
        if ($path !== '/' && str_ends_with($path, '/')) {
            return Response::redirect(rtrim($path, '/'), 301);
        }

        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $routeMethod = $route['method'];
            if ($routeMethod !== $method && !($routeMethod === 'GET' && $method === 'HEAD')) {
                $allowed[] = $routeMethod;
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];

            if (is_array($handler) && is_string($handler[0])) {
                $handler = [new $handler[0](), $handler[1]];
            }

            return $handler($params);
        }

        if ($allowed !== []) {
            throw new HttpException(405);
        }

        throw new HttpException(404);
    }
}
