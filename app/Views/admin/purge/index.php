<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Purge Old Entries</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-2">This tool permanently removes old entries and associated files/payment rows.</p>
            <ul>
                <li>Draft/Entrant entries with competition year <= <?= (int)$entrantCutoff ?></li>
                <li>Finalist entries with competition year <= <?= (int)$finalistCutoff ?></li>
            </ul>
            <p><strong>Current candidates:</strong> <?= (int)$entrantCount ?> draft/entrant, <?= (int)$finalistCount ?> finalist.</p>

            <?php if ($canDelete): ?>
                <form action="<?= site_url('admin/purge-entries/run') ?>" method="post" onsubmit="return confirm('Run purge now? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <button class="btn btn-danger" type="submit">Run Purge</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mb-0">Editors can view purge stats but cannot run purges.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

