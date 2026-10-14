<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container-fluid" id="image-scanner" data-endpoint="<?= esc(site_url('admin/system-tools/broken-image-scanner/step'), 'attr') ?>" data-token-name="<?= esc(csrf_token(), 'attr') ?>" data-token="<?= esc(csrf_hash(), 'attr') ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div class="mb-2"><h1 class="h3 text-gray-800 mb-1">Broken Image Scanner</h1><p class="text-muted mb-0">Find missing images. Restore their home. Keep galleries complete.</p></div>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools') ?>">Back to System Tools</a>
    </div>
    <?php if (!$ready): ?><div class="alert alert-warning">Database setup required. Run <code>php spark migrate</code> to enable the scanner.</div><?php endif; ?>
    <div class="card shadow-sm border-left-primary mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-3"><span class="badge badge-primary mb-2">ALL SUBMISSIONS · ALL YEARS</span><h2 class="h5 text-gray-800">Recover originals, then fill required photos</h2></div>
            <div class="mb-3"><button type="button" id="scan-start" class="btn btn-primary" <?= !$ready ? 'disabled' : '' ?>><i class="fas fa-search mr-2" aria-hidden="true"></i><span>Start scan &amp; repair</span></button> <button type="button" id="scan-pause" class="btn btn-outline-secondary" hidden>Pause</button></div>
        </div>
        <p>First, verify uploaded images and search year folders and trash for the originals. Then fill missing <strong>Photos 1–3</strong> with separate copies of a working design photo from the same submission. Optional photos are not duplicated and do not cause gallery exclusions.</p>
        <p class="small text-muted mb-0">Progress is saved after each batch. Keep this page open to continue, or pause and resume later. Unchanged clean images are skipped on future scans. Conflicting files are left for review; existing gallery exclusions are never automatically cleared.</p>
    </div></div>
    <div id="scan-error" class="alert alert-danger" role="alert" hidden></div>
    <div class="row">
        <?php foreach (['clean' => ['success', 'Verified clean'], 'cached' => ['info', 'Previously verified'], 'repaired' => ['primary', 'Originals restored'], 'copied' => ['info', 'Required photos filled'], 'missing' => ['warning', 'Missing images'], 'review' => ['secondary', 'Need review']] as $key => [$color, $label]): ?>
        <div class="col-6 col-lg mb-4"><div class="card h-100 shadow-sm border-left-<?= $color ?>"><div class="card-body"><div class="small text-muted mb-1"><?= $label ?></div><div class="h3 mb-0 text-gray-800" id="count-<?= $key ?>">0</div></div></div></div>
        <?php endforeach; ?>
    </div>
    <div class="card shadow-sm mb-4"><div class="card-body">
        <h2 class="h5 text-gray-800">Scan progress</h2>
        <p id="scan-activity" role="status" aria-live="polite">Ready to scan.</p>
        <div class="d-flex justify-content-between mb-2"><label for="scan-check" class="mb-0">1. Check uploaded images</label><span id="scan-check-label">0 / 0</span></div>
        <progress id="scan-check" class="w-100 mb-3" max="100" value="0">0%</progress>
        <div class="d-flex justify-content-between mb-2"><label for="scan-repair" class="mb-0">2. Search &amp; repair</label><span id="scan-repair-label">Waiting</span></div>
        <progress id="scan-repair" class="w-100" max="100" value="0">0%</progress>
        <div class="d-flex justify-content-between mt-3 mb-2"><label for="scan-fill" class="mb-0">3. Fill required photos</label><span id="scan-fill-label">Waiting</span></div><progress id="scan-fill" class="w-100" max="100" value="0">0%</progress><p class="small text-muted mt-2 mb-0" id="scan-files">Search begins after the initial scan finishes.</p>
    </div></div>
    <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h2 class="h6 mb-0 font-weight-bold text-gray-800">Repair results <span class="text-muted font-weight-normal">· latest 100 image records</span></h2></div>
        <div class="card-body"><p class="small text-muted">Required photos that cannot be recovered or copied may hide their submission. Optional missing images leave gallery visibility unchanged. Existing gallery exclusions remain in place.</p><p id="scan-empty" class="text-muted mb-0">Results will appear here as queued images are processed.</p><ul id="scan-results" class="list-group list-group-flush"></ul></div>
    </div>
</div>
<script type="application/json" id="scan-initial-state"><?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= base_url('js/broken-image-scanner.js') ?>" defer></script>
<?= $this->endSection() ?>

