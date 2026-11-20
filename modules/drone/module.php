<?php

declare(strict_types=1);

use Core\View;

return [
    'title' => 'Drone + Metronome App',
    'description' => 'Useful for practicing brass instruments, specifically long tones, scales and arpeggios. Features a piano keyboard layout or circle of fifths layout for note selection. As well as a metronome and keyboard shorcuts for changing octave, volume or, notes.',
    'nav' => null,  // listed under Projects only
    'url' => '/drone',
    'project' => true,
    'order' => 20,
    'routes' => [
        ['GET', '/drone', static fn (): Core\Response => View::page('drone', 'index', [], [
            'title' => 'Drone + Metronome',
            'styles' => ['drone/drone.css'],
            'scripts' => ['drone/drone.js'],
            'layout' => 'app',
        ])],
    ],
    'redirects' => [
        '/drone-app' => '/drone',
    ],
];
