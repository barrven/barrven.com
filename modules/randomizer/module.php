<?php

declare(strict_types=1);

use Modules\Randomizer\Controller;

return [
    'title' => 'Scale Randomizer',
    'description' => 'Provides list of four scales at a time, to play from memory. Includes suggested articulations, and arpeggio to play with the scale. Also shows progress bar for completing all the scales.',
    'nav' => null,  // listed under Projects only
    'url' => '/randomizer',
    'project' => true,
    'order' => 30,
    'routes' => [
        ['GET', '/randomizer', [Controller::class, 'index']],
        ['GET', '/randomizer/mxml/{file:[A-Za-z0-9.-]+\.musicxml}', [Controller::class, 'sheet']],
    ],
    'redirects' => [
        '/randomizer.php' => '/randomizer',
    ],
];
