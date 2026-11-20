<?php

declare(strict_types=1);

use Core\View;

return [
    'title' => 'Resume',
    'nav' => 'Resume',
    'url' => '/resume',
    'order' => 40,
    'routes' => [
        ['GET', '/resume', static fn (): Core\Response => View::page('resume', 'index', [], ['title' => 'Resume'])],
    ],
];
