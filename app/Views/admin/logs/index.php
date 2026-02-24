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
        <h1 class="h3 mb-0 text-gray-800">Log Viewer</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Log Directory Overview</h6></div>
        <div class="card-body">
            <p class="mb-2"><strong>Path:</strong> <code><?= esc($logsPath) ?></code></p>

            <?php if (! $logsExists): ?>
                <div class="alert alert-warning mb-0">The logs directory was not found.</div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Log Files</div>
                            <div class="h4 mb-0"><?= number_format((int) $fileCount) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Total Size</div>
                            <div class="h4 mb-0"><?= esc($formatBytes((int) $totalBytes)) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Selected File</div>
                            <div class="h6 mb-0 text-break"><?= esc($selectedFile !== '' ? $selectedFile : 'None') ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($canDelete): ?>
                    <form action="<?= site_url('admin/system-tools/logs/purge') ?>" method="post" onsubmit="return confirm('Purge all log files now? This cannot be undone.');">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger" type="submit">Purge Logs</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Editors can view logs but cannot purge them.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Recent Log Files</h6></div>
                <div class="card-body p-0">
                    <?php if ($files === []): ?>
                        <div class="p-3 text-muted">No log files found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th>File</th>
                                    <th>Size</th>
                                    <th>Updated</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($files as $file): ?>
                                    <?php $isActive = (string) $file['name'] === (string) $selectedFile; ?>
                                    <tr class="<?= $isActive ? 'table-primary' : '' ?>">
                                        <td>
                                            <a href="<?= site_url('admin/system-tools/logs?file=' . rawurlencode((string) $file['name'])) ?>">
                                                <?= esc((string) $file['name']) ?>
                                            </a>
                                        </td>
                                        <td><?= esc($formatBytes((int) ($file['size'] ?? 0))) ?></td>
                                        <td><?= esc(format_datetime_ui(date('Y-m-d H:i:s', (int) ($file['mtime'] ?? 0)), '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Recent Log Output</h6></div>
                <div class="card-body">
                    <?php if ($selectedFile === ''): ?>
                        <div class="text-muted">Select a log file to view recent lines.</div>
                    <?php else: ?>
                        <p class="small text-muted mb-2">Showing the latest 250 lines from <strong><?= esc($selectedFile) ?></strong>.</p>
                        <pre class="border rounded bg-light p-3 mb-0" style="max-height: 70vh; overflow: auto; white-space: pre-wrap; word-break: break-word;"><?= esc($preview) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
