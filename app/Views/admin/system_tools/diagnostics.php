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
        <h1 class="h3 mb-0 text-gray-800">Diagnostics Bundle</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <p>Create a sanitized diagnostics snapshot for troubleshooting.</p>
            <form action="<?= site_url('admin/system-tools/diagnostics/generate') ?>" method="post">
                <?= csrf_field() ?>
                <button class="btn btn-primary" type="submit">Generate + Download Bundle</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Recent Bundles</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>File</th><th>Size</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach (($bundles ?? []) as $bundle): ?>
                    <tr>
                        <td><a href="<?= site_url('admin/system-tools/diagnostics/download/' . rawurlencode((string) ($bundle['name'] ?? ''))) ?>"><?= esc((string) ($bundle['name'] ?? '')) ?></a></td>
                        <td><?= esc($formatBytes((int) ($bundle['size'] ?? 0))) ?></td>
                        <td><?= esc(format_datetime_ui(date('Y-m-d H:i:s', (int) ($bundle['mtime'] ?? 0)), '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($bundles)): ?><tr><td colspan="3" class="text-muted">No bundles found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

