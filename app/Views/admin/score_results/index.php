<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link href="/css/pages/admin-score-results.css" rel="stylesheet">

<div class="container-fluid">
    <?php $statusBulkActions = entry_status_bulk_actions(); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Competition Score Results</h1>
        <a
            class="btn btn-sm btn-primary"
            href="<?= site_url('admin/score-results/export?' . http_build_query($filters)) ?>"
            rel="noopener"
        >Export Score Results</a>
    </div>
    <p>This tool will allow you to tally the scores of each competition during the judging process.</p>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/score-results') ?>" class="form-inline mb-3">
                <label class="mr-2" for="year">Year</label>
                <select id="year" name="year" class="form-control mr-2">
                    <?php foreach ($years as $year): ?>
                        <option value="<?= (int)$year['comp_year'] ?>" <?= (int)$filters['year'] === (int)$year['comp_year'] ? 'selected' : '' ?>><?= (int)$year['comp_year'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Change Year</button>
            </form>

            <form method="get" action="<?= site_url('admin/score-results') ?>" class="form-row">
                <input type="hidden" name="year" value="<?= (int)$filters['year'] ?>">
                <div class="col-md-2 mb-2">
                    <label for="phase">Phase</label>
                    <select id="phase" name="phase" class="form-control">
                        <option value="1" <?= $filters['phase'] === '1' ? 'selected' : '' ?>>Phase 1</option>
                        <option value="2" <?= $filters['phase'] === '2' ? 'selected' : '' ?>>Phase 2</option>
                        <option value="AllSpark" <?= $filters['phase'] === 'AllSpark' ? 'selected' : '' ?>>AllSpark</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="type">Competition Type</label>
                    <select id="type" name="type" class="form-control">
                        <option value="0" <?= (int)$filters['type'] === 0 ? 'selected' : '' ?>>All</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= (int)$type['comp_type_id'] ?>" <?= (int)$filters['type'] === (int)$type['comp_type_id'] ? 'selected' : '' ?>><?= esc($type['comp_type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="userType">User Type</label>
                    <select id="userType" name="userType" class="form-control">
                        <option value="student" <?= strtolower((string)$filters['userType']) === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="pro" <?= strtolower((string)$filters['userType']) === 'pro' ? 'selected' : '' ?>>Pro</option>
                        <option value="all" <?= strtolower((string)$filters['userType']) === 'all' ? 'selected' : '' ?>>All</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status">Entry Status</label>
                    <?php $status = (string)$filters['status']; ?>
                    <select id="status" name="status" class="form-control">
                        <option value="All" <?= strcasecmp($status, 'All') === 0 ? 'selected' : '' ?>>All</option>
                        <option value="Winner" <?= strcasecmp($status, 'Winner') === 0 ? 'selected' : '' ?>>Winner</option>
                        <option value="Finalist" <?= strcasecmp($status, 'Finalist') === 0 ? 'selected' : '' ?>>Finalist</option>
                        <option value="Entrant" <?= strcasecmp($status, 'Entrant') === 0 ? 'selected' : '' ?>>Entrant</option>
                        <option value="Draft" <?= strcasecmp($status, 'Draft') === 0 ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit">Tally Scores</button>
                </div>
            </form>
        </div>
    </div>

    <form id="bulk_score_results_form"
          action="<?= site_url('admin/score-results/bulk') ?>"
          method="post"
          data-bulk-actions="1"
          data-item-selector="input[name='entry_ids[]']"
          data-select-all-selector="#select_all"
          data-action-selector="#action"
          data-delete-action="delete"
          data-delete-confirm="Delete selected score result entries? This cannot be undone.">
        <?= csrf_field() ?>
        <div class="card mb-3">
            <div class="card-body form-inline">
                <label for="action" class="mr-2">Bulk update selected entries:</label>
                <select name="action" id="action" class="form-control mr-2">
                    <option value="">Select an option</option>
                    <option value="">---</option>
                    <?php foreach ($statusBulkActions as $actionValue => $actionLabel): ?>
                        <option value="<?= esc($actionValue) ?>"><?= esc($actionLabel) ?></option>
                    <?php endforeach; ?>
                    <?php if ($canDelete): ?>
                        <option value="">---</option>
                        <option value="delete">DELETE - This CANNOT be undone!</option>
                    <?php endif; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Update</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover datatable">
                    <thead><tr><th><input type="checkbox" id="select_all"></th><th>Entry</th><th>Status</th><th>Competition</th><th>Score</th><th>Judges</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            [$statusLabel, $statusClass, $medalIconClass] = entry_status_pill($row);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="entry_ids[]" value="<?= esc((string)$row['entry_id']) ?>"></td>
                            <td>
                                <div class="score-result-entry">
                                    <?php if (!empty($row['_thumbnail_url'])): ?>
                                        <button
                                            type="button"
                                            class="score-result-thumb-btn js-score-result-thumb"
                                            data-photo-url="<?= esc((string)$row['_thumbnail_url']) ?>"
                                            data-photo-title="<?= esc((string)$row['design_name']) ?>"
                                            aria-label="Open <?= esc((string)$row['design_name']) ?> thumbnail at full size"
                                        >
                                            <img
                                                src="<?= esc((string)$row['_thumbnail_url']) ?>"
                                                class="score-result-thumb"
                                                alt=""
                                                loading="lazy"
                                            >
                                        </button>
                                    <?php else: ?>
                                        <span class="score-result-thumb score-result-thumb-empty" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <a href="https://galleries.sparkawards.com/gallery?entry=<?= rawurlencode((string)$row['entry_id']) ?>" target="_blank" rel="noopener"><?= esc((string)$row['design_name']) ?></a>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill <?= esc($statusClass) ?>">
                                    <?php if ($medalIconClass !== ''): ?>
                                        <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <?= esc($statusLabel) ?>
                                </span>
                            </td>
                            <td><?= esc((string)$row['comp_type_name']) ?></td>
                            <td><?= (int)($row['total_score'] ?? 0) ?></td>
                            <td><?= (int)($row['total_judges'] ?? 0) ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-primary" href="<?= site_url('admin/score-results/edit/' . rawurlencode((string)$row['entry_id']) . '?' . http_build_query($filters)) ?>">Edit Scores</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="scoreResultThumbnailModal" tabindex="-1" role="dialog" aria-labelledby="scoreResultThumbnailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scoreResultThumbnailModalLabel">Entry Thumbnail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="scoreResultThumbnailModalImage" src="" alt="" class="img-fluid rounded border score-result-lightbox-image">
            </div>
        </div>
    </div>
</div>

<script src="/js/utils/bulk-actions.js"></script>
<script src="/js/utils/modal.js"></script>
<script src="/js/utils/photo-preview-modal.js"></script>
<script>
    window.Spark = window.Spark || {};
    if (window.Spark.photoPreviewModal) {
        window.Spark.photoPreviewModal.initSimpleImageModal({
            modalId: 'scoreResultThumbnailModal',
            imageId: 'scoreResultThumbnailModalImage',
            titleId: 'scoreResultThumbnailModalLabel',
            buttonSelector: '.js-score-result-thumb',
            defaultTitle: 'Entry Thumbnail'
        });
    }
</script>

<?= $this->endSection() ?>

