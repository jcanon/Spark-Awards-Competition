<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Competition Readiness Checklist</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/system-tools/readiness') ?>" class="form-inline">
                <label for="year" class="mr-2 font-weight-bold">Year</label>
                <select id="year" name="year" class="form-control mr-2">
                    <option value="">All Years</option>
                    <?php foreach (($years ?? []) as $year): ?>
                        <option value="<?= (int) $year ?>" <?= ((int) ($selectedYear ?? 0) === (int) $year) ? 'selected' : '' ?>><?= (int) $year ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary mr-2" type="submit">Apply</button>
                <a class="btn btn-outline-secondary" href="<?= site_url('admin/system-tools/readiness') ?>">Reset</a>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-0"><strong>Submission Questions Configured:</strong> <?= number_format((int) ($report['question_count'] ?? 0)) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Competition</th><th>Ready</th><th>Blockers</th><th>Warnings</th><th>Issues</th></tr></thead>
                <tbody>
                <?php foreach (($report['competitions'] ?? []) as $row): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['comp_id'])): ?>
                                <a class="font-weight-bold" href="<?= site_url('admin/competitions/edit/' . (int) $row['comp_id']) ?>">
                                    <?= esc((string) ($row['competition'] ?? '')) ?>
                                </a>
                            <?php else: ?>
                                <?= esc((string) ($row['competition'] ?? '')) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($row['is_ready']) ? '<span class="text-success font-weight-bold">Yes</span>' : '<span class="text-danger font-weight-bold">No</span>' ?></td>
                        <td><?= (int) ($row['blockers'] ?? 0) ?></td>
                        <td><?= (int) ($row['warnings'] ?? 0) ?></td>
                        <td>
                            <?php foreach (($row['issues'] ?? []) as $issue): ?>
                                <div><span class="badge badge-<?= (($issue['severity'] ?? '') === 'blocker') ? 'danger' : 'warning' ?>"><?= esc((string) ($issue['severity'] ?? '')) ?></span> <?= esc((string) ($issue['message'] ?? '')) ?></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($report['competitions'])): ?><tr><td colspan="5" class="text-muted">No competitions found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
