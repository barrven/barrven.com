// Tier List Maker: list editor. Ranks the list's media into S-D tiers;
// unranked media ("L") shows in the grid below the tiers.
(function () {
    'use strict';

    var root = document.getElementById('tierlistEditor');
    var API = root.dataset.apiBase;        // e.g. /tierlist/api/lists/3
    var FILE_BASE = root.dataset.fileBase; // e.g. /tierlist/files

    var VIDEO_EXTS = ['mp4', 'webm', 'ogv', 'ogg', 'mov'];

    // Media URLs per tier
    var tierArrays = { S: [], A: [], B: [], C: [], D: [], L: [] };

    // .tier-content containers, by tier letter
    var tierContents = {};

    // Viewer state
    var currentImageIndex = 0;
    var activeListKey = 'L';

    // Delete mode state
    var selectedFiles = [];
    var deleteMode = false;

    var viewer = document.getElementById('imageModal');
    var grid = document.querySelector('.image-grid');

    function fileName(url) {
        return url.substring(url.lastIndexOf('/') + 1);
    }

    // ---------------------------------------------------------------------
    // Server sync
    // ---------------------------------------------------------------------

    async function saveState() {
        try {
            await site.postJson(API + '/tiers', { tierData: tierArrays });
        } catch (error) {
            console.error('Error saving tier data:', error);
            alert('Could not save your changes: ' + error.message);
        }
    }

    async function loadState() {
        try {
            var response = await site.fetch(API + '/tiers');
            var data = await response.json();

            Object.keys(tierArrays).forEach(function (k) {
                tierArrays[k].length = 0;
                if (Array.isArray(data[k])) {
                    data[k].forEach(function (name) { tierArrays[k].push(FILE_BASE + '/' + name); });
                }
            });
        } catch (error) {
            console.error('Error loading tier data:', error);
            alert('Could not load this list: ' + error.message);
        }

        populateImageGrid();
        populateTierRows();
    }

    function moveImageToTier(url, targetTierKey) {
        Object.keys(tierArrays).forEach(function (key) {
            var idx = tierArrays[key].indexOf(url);
            if (idx !== -1) tierArrays[key].splice(idx, 1);
        });

        tierArrays[targetTierKey].push(url);

        saveState();
        populateImageGrid();
        populateTierRows();
    }

    async function resetTierData() {
        if (!confirm('Are you sure you want to reset all tier data? This action cannot be undone.')) return;

        try {
            await site.postJson(API + '/tiers/reset');
        } catch (error) {
            alert('Could not reset tiers: ' + error.message);
        }
        loadState();
    }

    // ---------------------------------------------------------------------
    // Rendering
    // ---------------------------------------------------------------------

    function createMediaElement(url, isThumbnail) {
        var ext = url.split('.').pop().toLowerCase();
        var el;

        if (VIDEO_EXTS.indexOf(ext) !== -1) {
            el = document.createElement('video');
            el.src = url;
            el.muted = true;
            el.loop = true;
            el.playsInline = true;
            el.preload = 'metadata';
        } else {
            el = document.createElement('img');
            el.src = url;
            el.alt = '';
            el.loading = 'lazy';
        }

        el.classList.add(isThumbnail ? 'thumbnail' : 'grid-image');
        return el;
    }

    function populateTierRows() {
        ['S', 'A', 'B', 'C', 'D'].forEach(function (letter) {
            var container = tierContents[letter];
            container.innerHTML = '';

            tierArrays[letter].forEach(function (url, index) {
                var media = createMediaElement(url, true);
                media.addEventListener('click', function () {
                    activeListKey = letter;
                    currentImageIndex = index;
                    showViewerWithMedia(url);
                });
                container.appendChild(media);
            });
        });
    }

    function populateImageGrid() {
        grid.innerHTML = '';

        tierArrays.L.forEach(function (url, index) {
            var media = createMediaElement(url, false);

            if (deleteMode) {
                if (selectedFiles.indexOf(url) !== -1) media.classList.add('gi-delete-selected');
                media.addEventListener('click', function () { toggleDeleteSelection(url, media); });
            } else {
                media.addEventListener('click', function () {
                    activeListKey = 'L';
                    currentImageIndex = index;
                    showViewerWithMedia(url);
                });
            }

            grid.appendChild(media);
        });
    }

    // ---------------------------------------------------------------------
    // Media viewer
    // ---------------------------------------------------------------------

    function showViewerWithMedia(url) {
        var container = document.getElementById('modalMediaContainer');
        container.innerHTML = '';

        var media = createMediaElement(url, false);
        if (media.tagName === 'VIDEO') {
            media.controls = true;
            media.autoplay = true;
            media.muted = false;
        }

        container.appendChild(media);
        viewer.hidden = false;
    }

    function closeViewer() {
        var video = viewer.querySelector('video');
        if (video) video.pause();
        viewer.hidden = true;
    }

    function updateViewer() {
        var list = tierArrays[activeListKey];
        if (!list.length) {
            closeViewer();
            return;
        }
        // The index can run past the end after moving an image to another tier
        if (currentImageIndex >= list.length) currentImageIndex = list.length - 1;
        showViewerWithMedia(list[currentImageIndex]);
    }

    function step(delta) {
        var list = tierArrays[activeListKey];
        if (!list.length) return;
        currentImageIndex = (currentImageIndex + delta + list.length) % list.length;
        updateViewer();
    }

    // ---------------------------------------------------------------------
    // Add / delete files
    // ---------------------------------------------------------------------

    var addDialog = document.getElementById('addFilesDialog');
    var addForm = document.getElementById('addFilesForm');
    var addError = addForm.querySelector('[data-form-error]');
    var editModeTxt = document.getElementById('editModeTxt');
    var exitDeleteBtn = document.getElementById('exitDeleteModeBtn');
    var deleteSelectedBtn = document.getElementById('deleteSelectedFilesBtn');

    addForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        var submit = addForm.querySelector('button[type="submit"]');
        submit.disabled = true;
        addError.hidden = true;

        try {
            var response = await site.fetch(API + '/files', { method: 'POST', body: new FormData(addForm) });
            var result = await response.json();
            if (result.skipped.length > 0) {
                alert('Added ' + result.uploaded.length + ' file(s). Skipped ' + result.skipped.length +
                    ' that were not a supported image or video type:\n' + result.skipped.join('\n'));
            }
            window.location.reload();
        } catch (err) {
            addError.textContent = err.message;
            addError.hidden = false;
            submit.disabled = false;
        }
    });

    function updateDeleteButtonLabel() {
        deleteSelectedBtn.textContent = selectedFiles.length ? 'Delete (' + selectedFiles.length + ')' : 'Delete';
    }

    function toggleDeleteSelection(url, media) {
        var idx = selectedFiles.indexOf(url);
        if (idx === -1) {
            selectedFiles.push(url);
            media.classList.add('gi-delete-selected');
        } else {
            selectedFiles.splice(idx, 1);
            media.classList.remove('gi-delete-selected');
        }
        updateDeleteButtonLabel();
    }

    function setDeleteMode(on) {
        deleteMode = on;
        selectedFiles.length = 0;
        updateDeleteButtonLabel();
        editModeTxt.hidden = !on;
        exitDeleteBtn.hidden = !on;
        deleteSelectedBtn.hidden = !on;
        grid.classList.toggle('delete-mode', on);
        populateImageGrid();
    }

    async function deleteSelectedFiles() {
        if (selectedFiles.length === 0) return;
        if (!confirm('Are you sure you want to delete ' + selectedFiles.length + ' file(s)?')) return;

        try {
            await site.postJson(API + '/files/delete', { filenames: selectedFiles.map(fileName) });
        } catch (error) {
            alert('Failed to delete files: ' + error.message);
            return;
        }

        setDeleteMode(false);
        loadState();
    }

    // ---------------------------------------------------------------------
    // Wiring
    // ---------------------------------------------------------------------

    ['S', 'A', 'B', 'C', 'D'].forEach(function (letter) {
        tierContents[letter] = document.querySelector('.tier-row.tier-' + letter.toLowerCase() + ' .tier-content');
    });

    document.getElementById('resetBtn').addEventListener('click', resetTierData);
    document.getElementById('addFilesBtn').addEventListener('click', function () {
        addError.hidden = true;
        site.dialog.open(addDialog);
    });
    document.getElementById('deleteModeBtn').addEventListener('click', function () { setDeleteMode(true); });
    exitDeleteBtn.addEventListener('click', function () { setDeleteMode(false); });
    deleteSelectedBtn.addEventListener('click', deleteSelectedFiles);

    document.getElementById('prevBtn').addEventListener('click', function () { step(-1); });
    document.getElementById('nextBtn').addEventListener('click', function () { step(1); });

    document.querySelectorAll('.tier-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tier = btn.dataset.tier;
            var url = tierArrays[activeListKey][currentImageIndex];
            if (!url || tierArrays[tier].indexOf(url) !== -1) return;

            moveImageToTier(url, tier);
            updateViewer();
        });
    });

    viewer.addEventListener('click', function (event) {
        if (event.target === viewer) closeViewer();
    });

    document.addEventListener('keydown', function (event) {
        if (viewer.hidden) return;
        if (event.key === 'ArrowLeft') step(-1);
        if (event.key === 'ArrowRight') step(1);
        if (event.key === 'Escape') closeViewer();
    });

    loadState();
})();
