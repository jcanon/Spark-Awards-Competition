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
                    <div class="col-md-6 form-group">
                        <label>Site URL <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-link" aria-hidden="true"></i></span>
                            </div>
                            <input class="form-control" name="url" maxlength="100" required value="<?= esc((string)($row?->url ?? '')) ?>">
                        </div>
                    </div>
                    <div class="col-md-6 form-group"><label>Site Title <span class="text-danger">*</span></label><input class="form-control" name="title" maxlength="200" required value="<?= esc((string)($row?->title ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Primary Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                            </div>
                            <input type="email" class="form-control" name="email" maxlength="100" required value="<?= esc((string)($row?->email ?? '')) ?>">
                        </div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Email Server <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-server" aria-hidden="true"></i></span>
                            </div>
                            <input class="form-control" name="email_server" maxlength="200" required value="<?= esc((string)($row?->email_server ?? '')) ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Email Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user" aria-hidden="true"></i></span>
                            </div>
                            <input class="form-control" name="email_username" maxlength="200" required value="<?= esc((string)($row?->email_username ?? '')) ?>">
                        </div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Email Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            </div>
                            <input type="password" class="form-control" name="email_password" maxlength="200" required value="<?= esc((string)($row?->email_password ?? '')) ?>">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Site Maintenance</label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="hidden" name="site_maintenance" value="0">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="site_maintenance"
                                name="site_maintenance"
                                value="1"
                                <?= ((int)($row?->site_maintenance ?? 0) === 1) ? 'checked' : '' ?>
                            >
                            <label class="custom-control-label" for="site_maintenance">Enable maintenance mode</label>
                        </div>
                        <small class="form-text text-muted">When enabled, only administrators can log in.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="site_maintenance_message">Site Maintenance Message</label>
                        <textarea
                            class="form-control"
                            id="site_maintenance_message"
                            name="site_maintenance_message"
                            rows="4"
                            maxlength="2000"
                            placeholder="We are currently performing scheduled maintenance. Please try again shortly."
                        ><?= esc((string)($row?->site_maintenance_message ?? '')) ?></textarea>
                        <small class="form-text text-muted">Shown on the login page when maintenance mode is enabled.</small>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save Settings</button>
    </form>
</div>

<?= $this->endSection() ?>

