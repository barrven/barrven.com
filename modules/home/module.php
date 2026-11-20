<?php

declare(strict_types=1);

use Core\Modules;
use Core\View;

return [
    'title' => 'Home',
    'nav' => [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Projects', 'url' => '/projects'],
    ],
    'order' => 0,
    'routes' => [
        ['GET', '/', static fn (): Core\Response => View::page('home', 'home')],
        ['GET', '/projects', static fn (): Core\Response => View::page('home', 'projects', [
            'projects' => Modules::projects(),
        ], ['title' => 'Projects'])],
    ],
];
