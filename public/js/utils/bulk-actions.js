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
                    if ((actionEl.value || '') === deleteAction) {
                        var ok = window.confirm(deleteMessage);
                        if (!ok) {
                            event.preventDefault();
                        }
                    }
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
