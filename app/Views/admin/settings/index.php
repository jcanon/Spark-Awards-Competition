<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Site Settings</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?= view('partials/flash') ?>

    <form action="<?= site_url('admin/system-tools/settings/update') ?>" method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group"><label>Site URL <span class="text-danger">*</span></label><input class="form-control" name="url" maxlength="100" required value="<?= esc((string)($row?->url ?? '')) ?>"></div>
                    <div class="col-md-6 form-group"><label>Site Title <span class="text-danger">*</span></label><input class="form-control" name="title" maxlength="200" required value="<?= esc((string)($row?->title ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group"><label>Primary Email <span class="text-danger">*</span></label><input type="email" class="form-control" name="email" maxlength="100" required value="<?= esc((string)($row?->email ?? '')) ?>"></div>
                    <div class="col-md-6 form-group"><label>Email Server <span class="text-danger">*</span></label><input class="form-control" name="email_server" maxlength="200" required value="<?= esc((string)($row?->email_server ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group"><label>Email Username <span class="text-danger">*</span></label><input class="form-control" name="email_username" maxlength="200" required value="<?= esc((string)($row?->email_username ?? '')) ?>"></div>
                    <div class="col-md-6 form-group"><label>Email Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="email_password" maxlength="200" required value="<?= esc((string)($row?->email_password ?? '')) ?>"></div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save Settings</button>
    </form>
</div>

<?= $this->endSection() ?>

