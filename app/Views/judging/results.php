<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Score Results</h1>
    <a href="<?= site_url('admin/score-results/export?year=' . (int)$year . '&type=' . (int)$type . '&phase=' . rawurlencode((string)$phase) . '&userType=' . rawurlencode($userType) . '&status=' . rawurlencode($status)) ?>" class="btn btn-sm btn-primary">Export Excel</a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                <tr>
                    <th>Entry</th>
                    <th>Design</th>
                    <th>Status</th>
                    <th>Entrant</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc((string)$row['entry_id']) ?></td>
                        <td><?= esc((string)$row['design_name']) ?></td>
                        <td><?= esc((string)$row['entry_status']) ?></td>
                        <td><?= esc(trim((string)$row['first_name'] . ' ' . (string)$row['last_name'])) ?></td>
                        <td><?= esc((string)$row['company_name']) ?></td>
                        <td><?= esc((string)$row['email_address']) ?></td>
                        <td><?= esc((string)$row['phone']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
