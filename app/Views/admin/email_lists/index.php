<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Email Lists</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/email-lists') ?>" class="form-inline">
                <label for="list" class="mr-2">Category</label>
                <select id="list" name="list" class="form-control mr-2">
                    <option value="">Select category</option>
                    <?php foreach ($options as $option): ?>
                        <option value="<?= esc($option) ?>" <?= $label === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Load</button>
            </form>
        </div>
    </div>

    <?php if ($label !== ''): ?>
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    Emails in "<?= esc($label) ?>" (<?= count($emails) ?>)
                </h6>
            </div>
            <div class="card-body">
                <?php if ($emails === []): ?>
                    <div class="alert alert-warning mb-0">No email addresses found for this category.</div>
                <?php else: ?>
                    <p class="text-muted mb-2">
                        Copy and paste this comma-separated list into the <strong>BCC</strong> field in your email client.
                    </p>
                    <div class="mb-2">
                        <button class="btn btn-sm btn-primary" type="button" id="copyEmailListBtn">Copy Email List</button>
                    </div>
                    <textarea class="form-control" id="emailListTextarea" rows="18" readonly><?= esc($emailText) ?></textarea>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="/js/pages/admin-email-lists.js"></script>

<?= $this->endSection() ?>
