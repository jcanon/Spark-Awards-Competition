<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link href="/css/pages/judging-list.css" rel="stylesheet">

<div class="container-fluid">
    <?php
    $queryBase = [
        'phase' => (string)$phase,
    ];
    if (!empty($fullList)) {
        $queryBase['all'] = '1';
    }
    $applicationsUrl = site_url('judging/entries/competition/' . (int)$compId . '/status/' . rawurlencode((string)$status)) . '?' . http_build_query(array_merge($queryBase, ['view' => 'applications']));
    $scoreUrl = site_url('judging/entries/competition/' . (int)$compId . '/status/' . rawurlencode((string)$status)) . '?' . http_build_query(array_merge($queryBase, ['view' => 'score']));
    $currentView = (string)($viewMode ?? 'applications');
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">Judging Entries</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('judging') ?>">Back to Competitions</a>
    </div>
    <?php if (!empty($judgingContextLabel ?? null)): ?>
        <p class="mb-1 text-muted"><?= esc((string)$judgingContextLabel) ?></p>
    <?php endif; ?>
    <?php if (!empty($competitionLabel ?? null)): ?>
        <p class="mb-3 font-weight-bold text-primary"><?= esc((string)$competitionLabel) ?></p>
    <?php endif; ?>

    <?php if (!empty($fallbackNotice ?? null)): ?>
        <div class="alert alert-warning"><?= esc((string)($fallbackNotice ?? '')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link<?= $currentView === 'applications' ? ' active' : '' ?>" href="<?= esc($applicationsUrl) ?>">Entries List</a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $currentView === 'score' ? ' active' : '' ?>" href="<?= esc($scoreUrl) ?>">Score Review</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Entry Photo</th>
                    <th>Entry Name</th>
                    <th>Status</th>
                    <th>Score</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $i => $row): ?>
                    <?php
                        $myScore = $row['_my_score'] ?? null;
                        $isScored = $myScore !== null && $myScore !== '';
                        $rowClass = '';
                        if ($currentView === 'applications') {
                            $rowClass = $isScored ? 'judging-row-scored' : 'judging-row-unscored';
                        }

                        [$statusLabel, $statusClass, $medalIconClass] = entry_status_pill($row);
                    ?>
                    <tr class="<?= esc($rowClass) ?>">
                        <td><?= $i + 1 ?></td>
                        <td class="judging-preview-cell">
                            <?php if (!empty($row['_preview_photo_id'])): ?>
                                <button
                                    type="button"
                                    class="btn p-0 border-0 bg-transparent js-judging-photo-link judging-preview-btn"
                                    data-photo-url="<?= esc(site_url('media/photo/' . (int)$row['_preview_photo_id'])) ?>"
                                    data-photo-title="<?= esc((string)$row['design_name']) ?> - Preview"
                                    aria-label="Open entry preview image"
                                >
                                    <img
                                        src="<?= esc(site_url('media/photo/' . (int)$row['_preview_photo_id'])) ?>"
                                        class="judging-preview-thumb"
                                        alt="Entry preview image"
                                        loading="lazy"
                                    >
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">No image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= site_url('judging/entry/' . rawurlencode((string)$row['entry_id']) . '/status/' . rawurlencode($status) . '?phase=' . rawurlencode((string)$phase) . '&comp=' . (int)($row['comp_id'] ?? $compId) . ($fullList ? '&all=1' : '') . '&view=' . rawurlencode($currentView)) ?>">
                                <?= esc((string)$row['design_name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="status-pill <?= esc($statusClass) ?>">
                                <?php if ($medalIconClass !== ''): ?>
                                    <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= esc($statusLabel) ?>
                            </span>
                        </td>
                        <td><?= esc((string)$myScore) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="judgingPhotoModal" tabindex="-1" role="dialog" aria-labelledby="judgingPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judgingPhotoModalLabel">Entry Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="judgingPhotoModalImage" src="" alt="" class="img-fluid rounded border spark-max-h-75vh">
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="judgingPhotoPrevBtn">Previous</button>
                <div class="small text-muted" id="judgingPhotoCounter"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="judgingPhotoNextBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/utils/modal.js"></script>
<script src="/js/utils/photo-preview-modal.js"></script>
<script src="/js/pages/judging-entry.js"></script>

<?= $this->endSection() ?>
