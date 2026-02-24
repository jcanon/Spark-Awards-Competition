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
    $precision = $idx === 0 ? 0 : 2;
    return number_format($size, $precision) . ' ' . $units[$idx];
};
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Trash Manager</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Trash Folder Overview</h6>
        </div>
        <div class="card-body">
            <p class="mb-2"><strong>Path:</strong> <code><?= esc($trashPath) ?></code></p>

            <?php if (! $trashExists): ?>
                <div class="alert alert-warning mb-0">The trash directory was not found.</div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Photos in Trash</div>
                            <div class="h4 mb-0"><?= number_format((int) $photoCount) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">All Files in Trash</div>
                            <div class="h4 mb-0"><?= number_format((int) $fileCount) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Disk Usage</div>
                            <div class="h4 mb-0"><?= esc($formatBytes((int) $totalBytes)) ?></div>
                        </div>
                    </div>
                </div>

                <p class="text-muted mb-3">Subfolders currently inside trash: <?= number_format((int) $dirCount) ?></p>

                <?php if ($canDelete): ?>
                    <form action="<?= site_url('admin/system-tools/trash/purge') ?>" method="post" onsubmit="return confirm('Purge all files from trash? This cannot be undone.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger">Purge Trash</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Editors can view trash stats but cannot purge files.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
