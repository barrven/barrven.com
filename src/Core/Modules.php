<?php

declare(strict_types=1);

namespace Core;

/**
 * Discovers modules: every modules/<name>/module.php is loaded automatically.
 * A module file returns an array:
 *
 *   return [
 *       'title'       => 'Scale Randomizer',  // shown on the projects page
 *       'description' => '...',               // shown on the projects page
 *       'nav'         => 'Randomizer',         // label in the site nav, or null to hide,
 *                                              // or a list of ['label' => .., 'url' => ..]
 *       'url'         => '/randomizer',        // where the nav/project link points
 *       'project'     => true,                 // list it on /projects
 *       'order'       => 20,                   // sort order in nav and projects
 *       'routes'      => [
 *           ['GET', '/randomizer', [Controller::class, 'index']],
 *       ],
 *       'redirects'   => ['/randomizer.php' => '/randomizer'],  // old URL => new URL
 *   ];
 */
final class Modules
{
    /** @var list<array<string, mixed>>|null */
    private static ?array $modules = null;

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        if (self::$modules !== null) {
            return self::$modules;
        }

        $modules = [];

        foreach (glob(MODULES_ROOT . '/*/module.php') ?: [] as $file) {
            $name = basename(dirname($file));
            if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
                continue;
            }

            $definition = require $file;
            if (!is_array($definition)) {
                throw new \RuntimeException("modules/$name/module.php must return an array");
            }

            $modules[] = $definition + [
                'name' => $name,
                'title' => ucfirst($name),
                'description' => '',
                'nav' => null,
                'url' => '/' . $name,
                'project' => false,
                'order' => 100,
                'routes' => [],
                'redirects' => [],
            ];
        }

        usort($modules, static fn (array $a, array $b): int => [$a['order'], $a['name']] <=> [$b['order'], $b['name']]);

        return self::$modules = $modules;
    }

    /** @return list<array{label: string, url: string, module: string}> */
    public static function navItems(): array
    {
        $items = [];
        foreach (self::all() as $module) {
            $nav = $module['nav'];
            if (is_string($nav)) {
                $items[] = ['label' => $nav, 'url' => $module['url'], 'module' => $module['name']];
            } elseif (is_array($nav)) {
                // Several entries: 'nav' => [['label' => 'Home', 'url' => '/'], ...]
                foreach ($nav as $entry) {
                    $items[] = ['label' => $entry['label'], 'url' => $entry['url'], 'module' => $module['name']];
                }
            }
        }

        return $items;
    }

    /** Modules flagged 'project' => true, for the projects page. */
    public static function projects(): array
    {
        return array_values(array_filter(self::all(), static fn (array $m): bool => $m['project'] === true));
    }
}
