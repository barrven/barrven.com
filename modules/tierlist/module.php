<?php

declare(strict_types=1);

use Modules\Tierlist\Controller;

$list = '/tierlist/api/lists/{id:\d+}';

return [
    'title' => 'Tier List Maker',
    'description' => 'Upload images such as memes or cool cars and organize them into a tier list.',
    'nav' => null,  // listed under Projects only
    'url' => '/tierlist',
    'project' => true,
    'order' => 10,
    'routes' => [
        // Pages
        ['GET', '/tierlist', [Controller::class, 'dashboard']],
        ['GET', '/tierlist/login', [Controller::class, 'loginForm']],
        ['POST', '/tierlist/login', [Controller::class, 'login']],
        ['POST', '/tierlist/logout', [Controller::class, 'logout']],
        ['GET', '/tierlist/lists/{id:\d+}', [Controller::class, 'editor']],

        // JSON API (all POSTs need the CSRF token; site.fetch() sends it)
        ['POST', '/tierlist/api/lists', [Controller::class, 'createList']],
        ['POST', "$list/delete", [Controller::class, 'deleteList']],
        ['POST', "$list/files", [Controller::class, 'addFiles']],
        ['POST', "$list/files/delete", [Controller::class, 'deleteFiles']],
        ['GET', "$list/tiers", [Controller::class, 'loadTiers']],
        ['POST', "$list/tiers", [Controller::class, 'saveTiers']],
        ['POST', "$list/tiers/reset", [Controller::class, 'resetTiers']],

        // Uploaded media, served only to its owner
        ['GET', '/tierlist/files/{name:[a-f0-9]{32}\.[a-z0-9]{2,5}}', [Controller::class, 'file']],
    ],
    'redirects' => [
        '/tierlist.php' => '/tierlist',
    ],
];
