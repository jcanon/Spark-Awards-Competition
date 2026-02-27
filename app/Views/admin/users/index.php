<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link href="/css/pages/admin-users-index.css" rel="stylesheet">

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

    <?= view('partials/flash') ?>

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
            <table
                id="usersTable"
                class="table table-bordered table-hover"
                data-can-delete="<?= $canDelete ? '1' : '0' ?>"
                data-ajax-url="<?= esc(site_url('admin/users/data') . '?' . http_build_query([
                    'userType' => $opts['userType'],
                    'profileCompleted' => $opts['profileCompleted'] ?? 'Yes',
                ])) ?>"
                data-edit-base="<?= esc(site_url('admin/users/edit/')) ?>"
                data-impersonate-base="<?= esc(site_url('admin/users/impersonate/')) ?>"
                data-delete-base="<?= esc(site_url('admin/users/delete/')) ?>"
                data-csrf-name="<?= esc(csrf_token()) ?>"
                data-csrf-hash="<?= esc(csrf_hash()) ?>"
            >
                <thead><tr><th>Name</th><th>Company</th><th>Entrant Type</th><th>User Type</th><th>Email Address</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/js/pages/admin-users-index.js"></script>

<?= $this->endSection() ?>

