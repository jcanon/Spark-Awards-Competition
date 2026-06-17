<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Certificate Requests</h1>
    </div>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/certificate-requests') ?>" class="form-row">
                <div class="col-md-3 mb-2">
                    <label for="year">Year</label>
                    <select id="year" name="year" class="form-control">
                        <option value="0" <?= (int)$filters['year'] === 0 ? 'selected' : '' ?>>All Years</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= (int)$y['comp_year'] ?>" <?= (int)$filters['year'] === (int)$y['comp_year'] ? 'selected' : '' ?>>
                                <?= (int)$y['comp_year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <label for="competition_id">Competition</label>
                    <select id="competition_id" name="competition_id" class="form-control">
                        <option value="0" <?= (int)$filters['competition_id'] === 0 ? 'selected' : '' ?>>All Competitions</option>
                        <?php foreach ($competitionOptions as $comp): ?>
                            <option value="<?= (int)$comp['comp_id'] ?>" <?= (int)$filters['competition_id'] === (int)$comp['comp_id'] ? 'selected' : '' ?>>
                                <?= esc((string)$comp['comp_type_name']) ?> <?= (int)$comp['comp_year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status">Status</label>
                    <?php $statusFilter = trim((string)($filters['status'] ?? '')); ?>
                    <select id="status" name="status" class="form-control">
                        <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
                        <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= $statusFilter === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                    <tr>
                        <th>Submission</th>
                        <th>Competition</th>
                        <th>Entry Status</th>
                        <th>Contact</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                        [$entryStatus, $statusClass, $medalIconClass] = entry_status_pill($row);
                        [$status, $requestPillClass] = certificate_request_status_pill((string)($row['request_status'] ?? ''), true);
                        $entryId = trim((string)($row['entry_id'] ?? ''));
                        $designName = (string)($row['design_name'] ?? '');
                        $contactName = trim((string)($row['contact_person'] ?? ''));
                        $contactEmail = trim((string)($row['contact_email'] ?? ''));
                    ?>
                    <tr>
                        <td>
                            <?php if ($entryId !== ''): ?>
                                <a href="<?= site_url('admin/submissions/edit/' . rawurlencode($entryId)) ?>"><?= esc($designName) ?></a>
                            <?php else: ?>
                                <?= esc($designName) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string)($row['comp_type_name'] ?? '')) ?> <?= (int)($row['comp_year'] ?? 0) ?></td>
                        <td>
                            <span class="status-pill <?= esc($statusClass) ?>">
                                <?php if ($medalIconClass !== ''): ?>
                                    <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= esc($entryStatus) ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <?= esc($contactName) ?>
                            </div>
                            <div class="small text-muted">
                                <?php if ($contactEmail !== ''): ?>
                                    <a href="mailto:<?= esc($contactEmail, 'attr') ?>"><?= esc($contactEmail) ?></a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= esc(format_datetime_ui((string)($row['requested_at'] ?? ''), '-')) ?></td>
                        <td class="spark-min-w-220">
                            <div class="mb-2">
                                <span class="request-pill <?= esc($requestPillClass) ?>"><?= esc($status) ?></span>
                            </div>
                            <form method="post" action="<?= site_url('admin/certificate-requests/status/' . (int)$row['certificate_request_id']) ?>" class="form-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="year" value="<?= (int)$filters['year'] ?>">
                                <input type="hidden" name="competition_id" value="<?= (int)$filters['competition_id'] ?>">
                                <input type="hidden" name="status" value="<?= esc((string)($filters['status'] ?? '')) ?>">
                                <select name="request_status" class="form-control form-control-sm mr-2">
                                    <?php foreach ($statusOptions as $opt): ?>
                                        <option value="<?= esc($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                            </form>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-primary mb-1" href="<?= site_url('admin/certificate-requests/edit/' . (int)$row['certificate_request_id'] . '?' . http_build_query(array_filter([
                                'year' => (int)$filters['year'] ?: null,
                                'competition_id' => (int)$filters['competition_id'] ?: null,
                                'status' => trim((string)($filters['status'] ?? '')) ?: null,
                            ]))) ?>">Edit Request</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

