// Tier List Maker: dashboard (create and delete lists).
(function () {
    'use strict';

    var dialogEl = document.getElementById('newListDialog');
    var form = document.getElementById('createListForm');
    var formError = form.querySelector('[data-form-error]');

    function showError(message) {
        formError.textContent = message;
        formError.hidden = false;
    }

    document.getElementById('newListBtn').addEventListener('click', function () {
        formError.hidden = true;
        site.dialog.open(dialogEl);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        var submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        formError.hidden = true;

        try {
            var formData = new FormData(form);
            var response = await site.fetch('/tierlist/api/lists', { method: 'POST', body: formData });
            var result = await response.json();

            if (result.skipped.length > 0) {
                alert('Created "' + formData.get('listName') + '".\n' +
                    'Uploaded ' + result.uploaded.length + ' file(s); skipped ' + result.skipped.length +
                    ' that were not a supported image or video type:\n' + result.skipped.join('\n'));
            }
            window.location.reload();
        } catch (err) {
            showError(err.message);
            submit.disabled = false;
        }
    });

    document.querySelectorAll('[data-delete-list]').forEach(function (button) {
        button.addEventListener('click', async function () {
            var listName = button.dataset.listName;
            if (!confirm('Are you sure you want to delete "' + listName + '"?')) return;

            try {
                await site.postJson('/tierlist/api/lists/' + button.dataset.listId + '/delete');
                button.closest('.list-row').remove();
            } catch (err) {
                alert('Failed to delete: ' + err.message);
            }
        });
    });
})();
