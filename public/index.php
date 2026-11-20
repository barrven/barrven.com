<?php

declare(strict_types=1);

// Front controller: Apache sends every request that isn't a real file
// under public/ here (FallbackResource). Do not add markup or logic here;
// routes live in modules/<name>/module.php.

require __DIR__ . '/../src/bootstrap.php';

Core\App::run();
