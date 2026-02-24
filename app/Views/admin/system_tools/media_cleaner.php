<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$totals = $report['totals'] ?? [];
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
        <h1 class="h3 mb-0 text-gray-800">Media Dedup + Orphan Cleaner</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-2"><strong>Uploads Root:</strong> <code><?= esc((string) ($report['uploads_root'] ?? '')) ?></code></p>
            <div class="alert alert-info py-2">
                <strong>Note:</strong> "Unreferenced" means not referenced by <code>comp_entry_photos</code> in this codebase.
                It may still be used by legacy systems.
            </div>
            <div class="row">
                <div class="col-md-3"><strong>Total Files:</strong> <?= number_format((int) ($totals['files_total'] ?? 0)) ?></div>
                <div class="col-md-3"><strong>Referenced:</strong> <?= number_format((int) ($totals['files_referenced'] ?? 0)) ?></div>
                <div class="col-md-3"><strong>Unreferenced (Potential Orphans):</strong> <?= number_format((int) ($totals['files_unreferenced'] ?? ($totals['files_orphan'] ?? 0))) ?></div>
                <div class="col-md-3"><strong>Unreferenced Bytes:</strong> <?= esc($formatBytes((int) ($totals['unreferenced_bytes'] ?? ($totals['orphan_bytes'] ?? 0)))) ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><strong>Duplicate Groups:</strong> <?= number_format((int) ($totals['duplicate_group_count'] ?? 0)) ?></div>
                <div class="col-md-6"><strong>Safe Purge Candidates (Unreferenced Duplicates):</strong> <?= number_format((int) ($totals['purge_candidate_count'] ?? 0)) ?> (<?= esc($formatBytes((int) ($totals['purge_candidate_bytes'] ?? 0))) ?>)</div>
            </div>
            <?php if ($canDelete): ?>
                <form class="mt-3" action="<?= site_url('admin/system-tools/media-cleaner/purge') ?>" method="post" onsubmit="return confirm('Delete all safe duplicate candidates now? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger" type="submit">Purge Safe Duplicate Candidates</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Purge Preview (Top 200)</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Reason</th><th>File</th><th>Size</th></tr></thead>
                <tbody>
                <?php foreach (array_slice(($report['purge_plan']['candidates'] ?? []), 0, 200) as $row): ?>
                    <tr>
                        <td><?= esc((string) ($row['reason'] ?? '')) ?></td>
                        <td><code><?= esc((string) ($row['rel'] ?? '')) ?></code></td>
                        <td><?= esc($formatBytes((int) ($row['size'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($report['purge_plan']['candidates'])): ?><tr><td colspan="3" class="text-muted">No candidates found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
