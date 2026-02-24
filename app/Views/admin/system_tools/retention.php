<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$formatBytes = static function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = (float) $bytes;
    $idx = 0;
    while ($size >= 1024 && $idx < count($units) - 1) {
        $size /= 1024;
        $idx++;
    }
    return number_format($size, $idx === 0 ? 0 : 2) . ' ' . $units[$idx];
};
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Scheduled Retention Rules</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><strong>Rules</strong></div>
        <div class="card-body">
            <form action="<?= site_url('admin/system-tools/retention/save') ?>" method="post">
                <?= csrf_field() ?>
                <?php foreach ($rules as $key => $rule): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="enabled_<?= esc($key) ?>" name="enabled_<?= esc($key) ?>" value="1" <?= !empty($rule['enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enabled_<?= esc($key) ?>"><strong><?= esc((string) ($rule['label'] ?? $key)) ?></strong></label>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Path</label>
                                <input class="form-control" name="path_<?= esc($key) ?>" value="<?= esc((string) ($rule['path'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Retention Days</label>
                                <input type="number" min="1" class="form-control" name="days_<?= esc($key) ?>" value="<?= (int) ($rule['days'] ?? 1) ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Filename Pattern (glob)</label>
                                <input class="form-control" name="glob_<?= esc($key) ?>" value="<?= esc((string) ($rule['glob'] ?? '*')) ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button class="btn btn-primary" type="submit">Save Rules</button>
                <a class="btn btn-outline-secondary" href="<?= site_url('admin/system-tools/retention?preview=1') ?>">Dry-Run Preview</a>
            </form>

            <?php if ($canDelete): ?>
                <form class="mt-3" action="<?= site_url('admin/system-tools/retention/run') ?>" method="post" onsubmit="return confirm('Run retention cleanup now? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger" type="submit">Run Cleanup Now</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_array($preview)): ?>
        <div class="card mb-4">
            <div class="card-header"><strong>Dry-Run Preview</strong></div>
            <div class="card-body">
                <p><strong>Candidates:</strong> <?= number_format((int) ($preview['total_candidates'] ?? 0)) ?> files (<?= esc($formatBytes((int) ($preview['total_bytes'] ?? 0))) ?>)</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Rule</th><th>Path</th><th>Size</th><th>Modified</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice(($preview['items'] ?? []), 0, 300) as $item): ?>
                            <tr>
                                <td><?= esc((string) ($item['rule'] ?? '')) ?></td>
                                <td><code><?= esc((string) ($item['path'] ?? '')) ?></code></td>
                                <td><?= esc($formatBytes((int) ($item['size'] ?? 0))) ?></td>
                                <td><?= esc(format_datetime_ui(date('Y-m-d H:i:s', (int) ($item['mtime'] ?? 0)), '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($preview['items'])): ?><tr><td colspan="4" class="text-muted">No candidates found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

