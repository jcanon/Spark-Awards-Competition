(function (window, document) {
    if (!window || !document) {
        return;
    }

    var wireForm = function (form) {
        var itemSelector = form.getAttribute('data-item-selector') || 'input[name="entry_ids[]"]';
        var actionSelector = form.getAttribute('data-action-selector') || '';
        var selectAllSelector = form.getAttribute('data-select-all-selector') || '';
        var deleteAction = form.getAttribute('data-delete-action') || 'delete';
        var deleteMessage = form.getAttribute('data-delete-confirm') || 'Are you sure?';
        var refreshUrl = form.getAttribute('data-csrf-refresh-url');
        var refreshing = false;
        var readyToSubmit = false;

        if (selectAllSelector) {
            var selectAll = document.querySelector(selectAllSelector);
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll(itemSelector).forEach(function (el) {
                        el.checked = selectAll.checked;
                    });
                });
            }
        }

        if (actionSelector) {
            var actionEl = document.querySelector(actionSelector);
            if (actionEl) {
                form.addEventListener('submit', function (event) {
                    if (readyToSubmit) {
                        readyToSubmit = false;
                        return;
                    }
                    if (refreshing) {
                        event.preventDefault();
                        return;
                    }
                    if ((actionEl.value || '') === deleteAction) {
                        var ok = window.confirm(deleteMessage);
                        if (!ok) {
                            event.preventDefault();
                            return;
                        }
                    }
                    if (!refreshUrl) {
                        return;
                    }

                    event.preventDefault();
                    var submitter = event.submitter;
                    refreshing = true;
                    form.setAttribute('aria-busy', 'true');
                    window.fetch(refreshUrl, { credentials: 'same-origin', cache: 'no-store' })
                        .then(function (response) {
                            if (!response.ok || response.redirected) {
                                throw new Error('Unable to refresh form token');
                            }
                            return response.json();
                        })
                        .then(function (token) {
                            var field = form.elements.namedItem(token.name);
                            if (!field || typeof token.hash !== 'string' || !token.hash) {
                                throw new Error('Invalid form token');
                            }
                            field.value = token.hash;
                            readyToSubmit = true;
                            // Preserve row-specific formaction and native form validation.
                            form.requestSubmit(submitter || undefined);
                            readyToSubmit = false;
                        })
                        .catch(function () {
                            window.alert('Unable to refresh this form. Please try again. If the problem continues, reload the page and sign in again.');
                        })
                        .finally(function () {
                            refreshing = false;
                            form.removeAttribute('aria-busy');
                        });
                });
            }
        }
    };

    var init = function () {
        document.querySelectorAll('form[data-bulk-actions="1"]').forEach(wireForm);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
