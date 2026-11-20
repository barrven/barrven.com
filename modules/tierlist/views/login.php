<?php
declare(strict_types=1);

/**
 * @var string|null $error
 * @var string      $username
 */
?>
<section class="tierlist-login card">
    <p class="eyebrow">Tier List Maker</p>
    <h1 class="page-title">Choose a username</h1>
    <p class="muted">
        Enter a new username to start, or an existing one to get back to your lists.
        Letters, numbers, underscores and hyphens only.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/tierlist/login')) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="username">Username</label>
            <input class="input" type="text" id="username" name="username" value="<?= e($username) ?>"
                   minlength="3" maxlength="30" pattern="[a-zA-Z0-9][a-zA-Z0-9_\-]*"
                   autocomplete="username" required autofocus>
        </div>
        <button type="submit" class="btn btn-block">Continue</button>
    </form>
</section>
