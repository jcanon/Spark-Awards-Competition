<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    #usersTable thead th {
        white-space: nowrap;
    }

    #usersTable .btn {
        white-space: nowrap;
    }
</style>

<div class="container-fluid">
    <?php
    $exportBaseParams = [
        'userType' => $opts['userType'] ?? 'ALL',
        'profileCompleted' => $opts['profileCompleted'] ?? 'Yes',
    ];
    $exportAllUrl = site_url('admin/users/export') . '?' . http_build_query(array_merge($exportBaseParams, [
        'group' => 'all',
    ]));
    $exportProsUrl = site_url('admin/users/export') . '?' . http_build_query(array_merge($exportBaseParams, [
        'group' => 'professionals',
    ]));
    $exportStudentsUrl = site_url('admin/users/export') . '?' . http_build_query(array_merge($exportBaseParams, [
        'group' => 'students',
    ]));
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Users</h1>
        <div class="d-flex">
            <a class="btn btn-sm btn-primary mr-2" href="<?= esc($exportAllUrl) ?>">Export All</a>
            <a class="btn btn-sm btn-outline-primary mr-2" href="<?= esc($exportProsUrl) ?>">Export Professionals</a>
            <a class="btn btn-sm btn-outline-primary" href="<?= esc($exportStudentsUrl) ?>">Export Students</a>
        </div>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/users') ?>" class="form-row">
                <div class="col-md-3 mb-2">
                    <label>User Type</label>
                    <select name="userType" class="form-control">
                        <option value="ALL" <?= $opts['userType'] === 'ALL' ? 'selected' : '' ?>>All</option>
                        <option value="Admin" <?= $opts['userType'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Editor" <?= $opts['userType'] === 'Editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="Judge" <?= $opts['userType'] === 'Judge' ? 'selected' : '' ?>>Judge</option>
                        <option value="User" <?= $opts['userType'] === 'User' ? 'selected' : '' ?>>Entrant</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Profile Completed</label>
                    <select name="profileCompleted" class="form-control">
                        <option value="Yes" <?= ($opts['profileCompleted'] ?? 'Yes') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                        <option value="No" <?= ($opts['profileCompleted'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                        <option value="ALL" <?= ($opts['profileCompleted'] ?? '') === 'ALL' ? 'selected' : '' ?>>All</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table id="usersTable" class="table table-bordered table-hover">
                <thead><tr><th>Name</th><th>Company</th><th>Entrant Type</th><th>User Type</th><th>Email Address</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        var canDelete = <?= $canDelete ? 'true' : 'false' ?>;
        var dataUrl = <?= json_encode(site_url('admin/users/data') . '?' . http_build_query([
            'userType' => $opts['userType'],
            'profileCompleted' => $opts['profileCompleted'] ?? 'Yes',
        ])) ?>;
        var editBase = <?= json_encode(site_url('admin/users/edit/')) ?>;
        var impersonateBase = <?= json_encode(site_url('admin/users/impersonate/')) ?>;
        var deleteBase = <?= json_encode(site_url('admin/users/delete/')) ?>;
        var csrfName = <?= json_encode(csrf_token()) ?>;
        var csrfHash = <?= json_encode(csrf_hash()) ?>;
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
            var tableEl = document.getElementById('usersTable');
            if (!tableEl || typeof $ === 'undefined' || !$.fn.DataTable) {
                return false;
            }
            if ($.fn.DataTable.isDataTable(tableEl)) {
                return true;
            }

            $('#usersTable').DataTable({
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
                            return '<a class="font-weight-bold" href="' + editBase + id + '">' + label + '</a>';
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
</script>

<?= $this->endSection() ?>
