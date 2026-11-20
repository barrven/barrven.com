<?php
declare(strict_types=1);

/**
 * The one page shell every module renders inside.
 *
 * @var string      $content       the module view's HTML
 * @var string|null $title
 * @var string[]    $styles        extra stylesheets under public/assets
 * @var string[]    $scripts       extra scripts under public/assets
 * @var string      $layout        'default' | 'wide'
 * @var string      $activeModule
 * @var array       $navItems
 */
$currentPath = Core\Request::path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= $title ? e($title) . ' · ' : '' ?><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('core/site.css')) ?>">
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= e(asset($style)) ?>">
<?php endforeach; ?>
    <script src="<?= e(asset('core/site.js')) ?>" defer></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach; ?>
</head>
<body class="module-<?= e($activeModule) ?>">
    <header class="site-header">
        <nav class="navbar">
            <a href="<?= e(url('/')) ?>" class="brand"><?= e(APP_NAME) ?></a>
            <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-links" id="navLinks">
<?php foreach ($navItems as $item):
    $isActive = $item['url'] === '/'
        ? $currentPath === '/'
        : ($currentPath === $item['url'] || str_starts_with($currentPath, $item['url'] . '/'));
?>
                <li><a href="<?= e($item['url']) ?>"<?= $isActive ? ' class="active" aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>
    </header>

    <main class="site-main site-main--<?= e($layout) ?>">
<?= $content ?>
    </main>
</body>
</html>
