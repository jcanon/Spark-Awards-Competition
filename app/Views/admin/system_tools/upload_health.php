<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $totals = $report['totals'] ?? []; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Upload Queue Health</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Rows Scanned:</strong> <?= number_format((int) ($totals['rows_scanned'] ?? 0)) ?></div>
                <div class="col-md-3"><strong>Failed Upload Rows:</strong> <?= number_format((int) ($totals['failed_count'] ?? 0)) ?></div>
                <div class="col-md-3"><strong>Orphan Rows:</strong> <?= number_format((int) ($totals['orphan_count'] ?? 0)) ?></div>
                <div class="col-md-3"><strong>Missing Files:</strong> <?= number_format((int) ($totals['missing_file_count'] ?? 0)) ?></div>
            </div>
            <?php if ($canDelete): ?>
                <form class="mt-3" action="<?= site_url('admin/system-tools/upload-health/repair') ?>" method="post" onsubmit="return confirm('Run one-click repair now?');">
                    <?= csrf_field() ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="delete_empty_rows" id="delete_empty_rows">
                        <label class="form-check-label" for="delete_empty_rows">Also delete fully empty photo rows</label>
                    </div>
                    <button class="btn btn-danger" type="submit">Run Repair</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Failed Upload Rows</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Year</th><th>Category</th><th>Entry Photo ID</th><th>Entry ID</th><th>Reason</th></tr></thead>
                <tbody>
                <?php foreach (($report['failed'] ?? []) as $row): ?>
                    <tr>
                        <td><?= (int) ($row['comp_year'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['comp_type_name'] ?? '')) ?></td>
                        <td><?= (int) ($row['entry_photo_id'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['entry_id'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['reason'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($report['failed'])): ?><tr><td colspan="5" class="text-muted">No rows found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Orphan Photo Rows</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Year</th><th>Category</th><th>Entry Photo ID</th><th>Entry ID</th><th>Res</th><th>Order</th></tr></thead>
                <tbody>
                <?php foreach (($report['orphans'] ?? []) as $row): ?>
                    <tr>
                        <td><?= (int) ($row['comp_year'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['comp_type_name'] ?? '')) ?></td>
                        <td><?= (int) ($row['entry_photo_id'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['entry_id'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['entry_photo_res'] ?? '')) ?></td>
                        <td><?= (int) ($row['entry_photo_order'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($report['orphans'])): ?><tr><td colspan="6" class="text-muted">No rows found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Missing Files</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Year</th><th>Category</th><th>Entry Photo ID</th><th>Entry ID</th><th>Field</th><th>Current</th><th>Expected</th></tr></thead>
                <tbody>
                <?php foreach (($report['missing_files'] ?? []) as $row): ?>
                    <tr>
                        <td><?= (int) ($row['comp_year'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['comp_type_name'] ?? '')) ?></td>
                        <td><?= (int) ($row['entry_photo_id'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['entry_id'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['field'] ?? '')) ?></td>
                        <td><code><?= esc((string) ($row['current'] ?? '')) ?></code></td>
                        <td><code><?= esc((string) ($row['expected'] ?? '')) ?></code></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($report['missing_files'])): ?><tr><td colspan="7" class="text-muted">No rows found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
