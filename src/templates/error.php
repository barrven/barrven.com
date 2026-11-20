<?php
declare(strict_types=1);

/**
 * @var int         $status
 * @var string      $message
 * @var string|null $detail   stack trace, only passed when APP_DEBUG is on
 */
?>
<section class="error-page">
    <p class="eyebrow">Error <?= e($status) ?></p>
    <h1><?= e($message) ?></h1>
    <p class="lede">
        <?php if ($status === 404): ?>
            That page doesn't exist, or it moved.
        <?php else: ?>
            Try going back, or start again from the home page.
        <?php endif; ?>
    </p>
    <a class="btn" href="<?= e(url('/')) ?>">Go home</a>

    <?php if ($detail !== null): ?>
        <pre class="debug-trace"><?= e($detail) ?></pre>
    <?php endif; ?>
</section>
