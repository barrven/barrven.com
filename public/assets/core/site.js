// Shared behaviour for every page: the mobile nav toggle, and site.fetch(),
// a fetch() wrapper that sends the CSRF token so POST requests are accepted.
(function () {
    'use strict';

    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

    /**
     * Same as fetch(), plus: sends the CSRF token header, asks for JSON, and
     * rejects with an Error (message from the server's "error" field when
     * there is one) on any non-2xx response.
     */
    async function siteFetch(url, options) {
        options = options || {};
        var headers = new Headers(options.headers || {});
        headers.set('X-CSRF-Token', csrfToken);
        if (!headers.has('Accept')) headers.set('Accept', 'application/json');

        var response = await fetch(url, Object.assign({}, options, {
            headers: headers,
            credentials: 'same-origin'
        }));

        if (!response.ok) {
            var message = 'Request failed (HTTP ' + response.status + ')';
            try {
                var body = await response.json();
                if (body && body.error) message = body.error;
            } catch (e) { /* not JSON */ }
            throw new Error(message);
        }

        return response;
    }

    /** POST a JS object as JSON and return the parsed JSON reply. */
    async function postJson(url, data) {
        var response = await siteFetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data || {})
        });
        return response.json();
    }

    // ---- Dialogs ----
    // Markup: <div class="dialog-overlay" id="x" hidden><div class="dialog">...
    // Any [data-close-dialog] inside closes it, as do Escape and a click on
    // the dimmed backdrop.
    var dialog = {
        open: function (overlay) {
            overlay.hidden = false;
            var first = overlay.querySelector('input, select, textarea, button:not(.dialog-close)');
            if (first) first.focus();
        },
        close: function (overlay) {
            overlay.hidden = true;
        }
    };

    document.addEventListener('click', function (event) {
        var closer = event.target.closest('[data-close-dialog]');
        if (closer) {
            dialog.close(closer.closest('.dialog-overlay'));
        } else if (event.target.classList && event.target.classList.contains('dialog-overlay')) {
            dialog.close(event.target);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.dialog-overlay:not([hidden])').forEach(dialog.close);
    });

    window.site = { fetch: siteFetch, postJson: postJson, csrfToken: csrfToken, dialog: dialog };

    // ---- Mobile nav ----
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('navToggle');
        var list = document.getElementById('navLinks');
        if (!toggle || !list) return;

        toggle.addEventListener('click', function () {
            var isOpen = list.classList.toggle('open');
            toggle.classList.toggle('open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    });
})();
