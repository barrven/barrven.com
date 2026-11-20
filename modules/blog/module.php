<?php

declare(strict_types=1);

use Core\View;

return [
    'title' => 'Blog',
    'nav' => 'Blog',
    'url' => '/blog',
    'order' => 50,
    'routes' => [
        ['GET', '/blog', static fn (): Core\Response => View::page('blog', 'index', [], ['title' => 'Blog'])],
    ],
];
