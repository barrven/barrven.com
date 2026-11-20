<?php
declare(strict_types=1);

/**
 * @var array{id: int, username: string} $user
 * @var array{list_id: int, list_name: string} $list
 */
$listId = (int) $list['list_id'];
?>
<div class="tierlist-editor" id="tierlistEditor"
     data-api-base="<?= e(url('/tierlist/api/lists/' . $listId)) ?>"
     data-file-base="<?= e(url('/tierlist/files')) ?>">

    <div class="tierlist-toolbar">
        <div>
            <p class="eyebrow">Tier List Maker</p>
            <h1 class="page-title"><?= e($list['list_name']) ?></h1>
        </div>
        <div class="btn-row">
            <button type="button" id="resetBtn" class="btn btn-secondary btn-sm">Reset tiers</button>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('/tierlist')) ?>">Back to <?= e($user['username']) ?>'s lists</a>
        </div>
    </div>

    <div class="tier-list">
        <?php foreach (['S', 'A', 'B', 'C', 'D'] as $tier): ?>
            <div class="tier-row tier-<?= strtolower($tier) ?>">
                <div class="tier-label"><?= $tier ?></div>
                <div class="tier-content"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Content</h2>
    <div class="btn-row file-controls">
        <button type="button" id="addFilesBtn" class="btn">Add files</button>
        <button type="button" id="deleteModeBtn" class="btn btn-secondary">Delete files</button>
        <p id="editModeTxt" class="muted" hidden>Select files below to delete…</p>
        <div class="btn-row delete-mode-actions">
            <button type="button" id="exitDeleteModeBtn" class="btn btn-secondary" hidden>Cancel</button>
            <button type="button" id="deleteSelectedFilesBtn" class="btn btn-danger" hidden>Delete</button>
        </div>
    </div>

    <!-- Unranked media -->
    <div class="image-grid-section card">
        <div class="image-grid"></div>
    </div>
</div>

<!-- Image/video viewer -->
<div id="imageModal" class="media-viewer" hidden>
    <div class="media-viewer-content">
        <div id="modalMediaContainer"></div>

        <button type="button" class="nav-btn prev-btn" id="prevBtn" aria-label="Previous">&larr;</button>
        <button type="button" class="nav-btn next-btn" id="nextBtn" aria-label="Next">&rarr;</button>

        <div class="tier-buttons">
            <?php foreach (['S', 'A', 'B', 'C', 'D'] as $tier): ?>
                <button type="button" class="tier-btn tier-<?= strtolower($tier) ?>" data-tier="<?= $tier ?>"><?= $tier ?></button>
            <?php endforeach; ?>
            <button type="button" class="tier-btn tier-l" data-tier="L">None</button>
        </div>
    </div>
</div>

<!-- Add files dialog -->
<div class="dialog-overlay" id="addFilesDialog" hidden>
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="addFilesTitle">
        <div class="dialog-header">
            <h2 id="addFilesTitle">Add files to <?= e($list['list_name']) ?></h2>
            <button type="button" class="dialog-close" data-close-dialog aria-label="Close">&times;</button>
        </div>
        <form id="addFilesForm" enctype="multipart/form-data">
            <div class="field">
                <label for="filesInput">Images or videos</label>
                <input class="input" type="file" id="filesInput" name="filesInput[]" accept="image/*,video/*" multiple required>
                <span class="hint">JPEG, PNG, GIF, WebP, AVIF, BMP, MP4, WebM, MOV or Ogg.</span>
            </div>
            <p class="alert alert-error" data-form-error hidden></p>
            <button type="submit" class="btn btn-block">Save</button>
        </form>
    </div>
</div>
