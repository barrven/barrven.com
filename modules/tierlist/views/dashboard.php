<?php
declare(strict_types=1);

/**
 * @var array{id: int, username: string} $user
 * @var list<array{list_id: int, list_name: string}> $lists
 */
?>
<div class="tierlist-dashboard">
    <div class="tierlist-toolbar">
        <div>
            <p class="eyebrow">Tier List Maker</p>
            <h1 class="page-title">Your lists</h1>
        </div>
        <form method="post" action="<?= e(url('/tierlist/logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm">Log out <?= e($user['username']) ?></button>
        </form>
    </div>

    <div class="btn-row">
        <button type="button" class="btn" id="newListBtn">New list</button>
    </div>

    <div class="lists-panel card">
        <?php if ($lists === []): ?>
            <p>You have no tier lists yet. Create one to get started!</p>
        <?php endif; ?>

        <?php foreach ($lists as $list): ?>
            <div class="list-row">
                <span class="list-name"><?= e($list['list_name']) ?></span>
                <div class="btn-row">
                    <a class="btn btn-secondary btn-sm" href="<?= e(url('/tierlist/lists/' . (int) $list['list_id'])) ?>">Edit</a>
                    <button type="button" class="btn btn-danger btn-sm" data-delete-list
                            data-list-id="<?= (int) $list['list_id'] ?>"
                            data-list-name="<?= e($list['list_name']) ?>">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="dialog-overlay" id="newListDialog" hidden>
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="newListTitle">
        <div class="dialog-header">
            <h2 id="newListTitle">Create new tier list</h2>
            <button type="button" class="dialog-close" data-close-dialog aria-label="Close">&times;</button>
        </div>
        <form id="createListForm" enctype="multipart/form-data">
            <div class="field">
                <label for="listName">List name</label>
                <input class="input" type="text" id="listName" name="listName" maxlength="100" required>
            </div>
            <div class="field">
                <label for="filesInput">Images or videos (optional)</label>
                <input class="input" type="file" id="filesInput" name="filesInput[]" accept="image/*,video/*" multiple>
                <span class="hint">JPEG, PNG, GIF, WebP, AVIF, BMP, MP4, WebM, MOV or Ogg.</span>
            </div>
            <p class="alert alert-error" data-form-error hidden></p>
            <button type="submit" class="btn btn-block">Save</button>
        </form>
    </div>
</div>
