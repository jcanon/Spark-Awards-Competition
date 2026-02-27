(function () {
    var tableEl = document.getElementById('usersTable');
    if (!tableEl) {
        return;
    }
    var canDelete = (tableEl.getAttribute('data-can-delete') || '0') === '1';
    var dataUrl = tableEl.getAttribute('data-ajax-url') || '';
    var editBase = tableEl.getAttribute('data-edit-base') || '';
    var impersonateBase = tableEl.getAttribute('data-impersonate-base') || '';
    var deleteBase = tableEl.getAttribute('data-delete-base') || '';
    var csrfName = tableEl.getAttribute('data-csrf-name') || '';
    var csrfHash = tableEl.getAttribute('data-csrf-hash') || '';
    var defaultOrder = [[0, 'asc']];

    var esc = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[ch];
        });
    };

    var initTable = function () {
        if (!tableEl || typeof window.$ === 'undefined' || !window.$.fn.DataTable) {
            return false;
        }
        if (window.$.fn.DataTable.isDataTable(tableEl)) {
            return true;
        }

        window.$('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 350,
            pageLength: 100,
            lengthMenu: [[25, 50, 100, 500, 1000], [25, 50, 100, 500, 1000]],
            order: defaultOrder,
            responsive: false,
            ajax: {
                url: dataUrl,
                type: 'GET',
                error: function () {
                    var wrapper = document.getElementById('usersTable_wrapper');
                    if (!wrapper) {
                        return;
                    }
                    var existing = document.getElementById('usersLoadError');
                    if (existing) {
                        return;
                    }
                    var div = document.createElement('div');
                    div.id = 'usersLoadError';
                    div.className = 'alert alert-danger mt-3 mb-0';
                    div.textContent = 'Unable to load users right now. Please refresh the page.';
                    wrapper.parentNode.insertBefore(div, wrapper.nextSibling);
                }
            },
            columns: [
                {
                    data: null,
                    render: function (row) {
                        var id = encodeURIComponent(String(row.user_id || ''));
                        var label = esc((row.last_name || '') + ', ' + (row.first_name || ''));
                        return '<a href="' + editBase + id + '">' + label + '</a>';
                    }
                },
                { data: 'company_name', render: function (v) { return esc(v); } },
                {
                    data: 'user_type_name',
                    render: function (v) { return esc(v); }
                },
                {
                    data: null,
                    render: function (row) {
                        if (String(row.is_admin || 'No') === 'Yes') {
                            return 'Admin';
                        }
                        if (String(row.is_editor || 'No') === 'Yes') {
                            return 'Editor';
                        }
                        if (String(row.is_judge || 'No') === 'Yes') {
                            return 'Judge';
                        }
                        return 'User';
                    }
                },
                {
                    data: 'email_address',
                    render: function (v) {
                        var email = String(v || '');
                        if (email === '') {
                            return '';
                        }
                        return '<a href="mailto:' + encodeURIComponent(email) + '">' + esc(email) + '</a>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-nowrap',
                    render: function (row) {
                        var id = encodeURIComponent(String(row.user_id || ''));
                        var html = '<a class="btn btn-sm btn-primary mr-1" href="' + editBase + id + '">Edit</a>';
                        var isAdmin = String(row.is_admin || 'No') === 'Yes';
                        var isEditor = String(row.is_editor || 'No') === 'Yes';
                        var isActive = String(row.account_active || 'No') === 'Yes';
                        var canImpersonate = !isAdmin && !isEditor && isActive;
                        if (canImpersonate) {
                            html += '<form action="' + impersonateBase + id + '" method="post" class="d-inline user-impersonate-form mr-1">';
                            html += '<input type="hidden" name="' + esc(csrfName) + '" value="' + esc(csrfHash) + '">';
                            html += '<button class="btn btn-sm btn-info" type="submit">Impersonate</button></form>';
                        }
                        if (canDelete) {
                            html += '<form action="' + deleteBase + id + '" method="post" class="d-inline user-delete-form">';
                            html += '<input type="hidden" name="' + esc(csrfName) + '" value="' + esc(csrfHash) + '">';
                            html += '<button class="btn btn-sm btn-danger" type="submit">Delete</button></form>';
                        }
                        return html;
                    }
                }
            ]
        });

        return true;
    };

    window.addEventListener('load', function () {
        if (initTable()) {
            return;
        }
        var retries = 0;
        var timer = setInterval(function () {
            retries++;
            if (initTable() || retries >= 20) {
                clearInterval(timer);
            }
        }, 100);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.classList || !form.classList.contains('user-delete-form')) {
            if (!form || !form.classList || !form.classList.contains('user-impersonate-form')) {
                return;
            }
            if (!window.confirm('Impersonate this user now? You will switch into their account.')) {
                event.preventDefault();
            }
            return;
        }
        if (!window.confirm('Delete this user? This action is irreversible.')) {
            event.preventDefault();
        }
    });
})();
