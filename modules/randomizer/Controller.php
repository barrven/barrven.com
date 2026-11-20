<?php

declare(strict_types=1);

namespace Modules\Randomizer;

use Core\HttpException;
use Core\Response;
use Core\View;

final class Controller
{
    private const MXML_DIR = __DIR__ . '/data/mxml';

    public function index(): Response
    {
        return View::page('randomizer', 'index', [], [
            'title' => 'Scale Randomizer',
            'styles' => ['randomizer/randomizer.css'],
            'scripts' => [
                'randomizer/vendor/opensheetmusicdisplay-1.9.0.min.js',
                'randomizer/randomizer.js',
            ],
        ]);
    }

    /** One MusicXML file, by name. The route pattern already restricts the characters. */
    public function sheet(array $params): Response
    {
        $dir = realpath(self::MXML_DIR);
        $path = $dir === false ? false : realpath($dir . '/' . basename($params['file']));

        if ($path === false || dirname($path) !== $dir || !is_file($path)) {
            throw new HttpException(404, 'Sheet music not found');
        }

        return Response::file($path, 'application/vnd.recordare.musicxml+xml; charset=UTF-8', [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
