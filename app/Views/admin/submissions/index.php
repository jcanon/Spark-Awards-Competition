<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <?php $statusBulkActions = entry_status_bulk_actions(); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Submissions</h1>
        <div>
            <a class="btn btn-sm btn-primary" href="<?= site_url('admin/submissions/create?compYear=' . (int)$filters['compYear']) ?>">Add Submission</a>
            <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions/export?' . http_build_query($filters)) ?>">Export Submissions</a>
        </div>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/submissions') ?>" class="form-row">
                <div class="col-md-2 mb-2"><label>Year</label><select name="compYear" class="form-control"><?php foreach ($years as $year): ?><option value="<?= (int)$year['comp_year'] ?>" <?= (int)$filters['compYear'] === (int)$year['comp_year'] ? 'selected' : '' ?>><?= (int)$year['comp_year'] ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 mb-2"><label>Competition</label><select name="compType" class="form-control"><option value="ALL">ALL</option><?php foreach ($types as $type): ?><option value="<?= (int)$type['comp_type_id'] ?>" <?= (string)$filters['compType'] === (string)$type['comp_type_id'] ? 'selected' : '' ?>><?= esc($type['comp_type_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 mb-2"><label>Exclude Non-Finalists</label><select name="excludeNonFinalists" class="form-control"><option value="No" <?= $filters['excludeNonFinalists'] === 'No' ? 'selected' : '' ?>>No</option><option value="Yes" <?= $filters['excludeNonFinalists'] === 'Yes' ? 'selected' : '' ?>>Yes</option></select></div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><button type="submit" class="btn btn-primary btn-block">Go</button></div>
            </form>
        </div>
    </div>

    <form id="bulk_submissions_form"
          action="<?= site_url('admin/submissions/bulk') ?>"
          method="post"
          data-bulk-actions="1"
          data-item-selector="input[name='entry_ids[]']"
          data-select-all-selector="#select_all"
          data-action-selector="#bulk_action"
          data-delete-action="delete"
          data-delete-confirm="Delete selected submissions? This cannot be undone.">
        <?= csrf_field() ?>
        <div class="card mb-3">
            <div class="card-body form-inline">
                <label class="mr-2" for="bulk_action">Bulk Update Submissions:</label>
                <select id="bulk_action" name="action" class="form-control mr-2">
                    <option value="">Select Submission Checkboxes First...</option>
                    <option value="">---</option>
                    <option value="gallery_show">Show in Gallery</option>
                    <option value="gallery_hide">Hide from Gallery</option>
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
                    <thead><tr><th><input type="checkbox" id="select_all"></th><th>Design Name</th><th>Designer</th><th>Competition</th><th>Entry Status</th><th>In Gallery</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $designer = trim((string)($row['designer_first_name'] ?? '') . ' ' . (string)($row['designer_last_name'] ?? ''));
                            if ($designer === '') {
                                $designer = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
                            }
                            [$statusLabel, $statusClass, $medalIconClass] = entry_status_pill($row);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="entry_ids[]" value="<?= esc((string)$row['entry_id']) ?>"></td>
                            <td><a href="<?= site_url('admin/submissions/edit/' . rawurlencode((string)$row['entry_id'])) ?>"><?= esc((string)$row['design_name']) ?></a></td>
                            <td><?= esc($designer) ?></td>
                            <td><?= esc((string)$row['comp_type_name']) ?></td>
                            <td>
                                <span class="status-pill <?= esc($statusClass) ?>">
                                    <?php if ($medalIconClass !== ''): ?>
                                        <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <?= esc($statusLabel) ?>
                                </span>
                            </td>
                            <td><?= ((string)($row['gallery_hide'] ?? 'No') === 'Yes') ? 'No' : 'Yes' ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/submissions/edit/' . rawurlencode((string)$row['entry_id'])) ?>">Edit</a>
                                <a class="btn btn-sm btn-info mr-1" href="<?= site_url('admin/submissions/copy/' . rawurlencode((string)$row['entry_id'])) ?>">Copy</a>
                                <?php if ($canDelete): ?>
                                    <form action="<?= site_url('admin/submissions/delete/' . rawurlencode((string)$row['entry_id'])) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this submission?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script src="/js/utils/bulk-actions.js"></script>

<?= $this->endSection() ?>
