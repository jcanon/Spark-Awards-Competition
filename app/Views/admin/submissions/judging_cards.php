<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Judging Cards</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions') ?>">Back to Submissions</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/submissions/judging-cards') ?>" class="form-row">
                <div class="col-md-3 mb-2"><label>Year</label><select name="compYear" class="form-control"><?php foreach ($years as $year): ?><option value="<?= (int)$year['comp_year'] ?>" <?= (int)$filters['compYear'] === (int)$year['comp_year'] ? 'selected' : '' ?>><?= (int)$year['comp_year'] ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 mb-2"><label>Type</label><select name="compType" class="form-control"><option value="ALL">All</option><?php foreach ($types as $type): ?><option value="<?= (int)$type['comp_type_id'] ?>" <?= (string)$filters['compType'] === (string)$type['comp_type_id'] ? 'selected' : '' ?>><?= esc($type['comp_type_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2 mb-2"><label>Phase</label><select name="compPhase" class="form-control"><option value="1" <?= (int)$filters['compPhase'] === 1 ? 'selected' : '' ?>>Phase 1</option><option value="2" <?= (int)$filters['compPhase'] === 2 ? 'selected' : '' ?>>Phase 2</option></select></div>
                <div class="col-md-3 mb-2"><label>Exclude Non-Finalists</label><select name="excludeNonFinalists" class="form-control"><option value="No" <?= $filters['excludeNonFinalists'] === 'No' ? 'selected' : '' ?>>No</option><option value="Yes" <?= $filters['excludeNonFinalists'] === 'Yes' ? 'selected' : '' ?>>Yes</option></select></div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit">Go</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead><tr><th>Entry</th><th>Competition</th><th>Designer</th><th>Photo</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc((string)$row['design_name']) ?></td>
                        <td><?= esc((string)$row['comp_type_name'] . ' ' . (string)$row['comp_year']) ?></td>
                        <td><?= esc(trim((string)$row['designer_first_name'] . ' ' . (string)$row['designer_last_name'])) ?></td>
                        <td><?php if (!empty($row['photo'])): ?><img src="<?= esc((string)$row['photo']) ?>" alt="" style="max-width:120px;" class="img-thumbnail"><?php endif; ?></td>
                        <td><?= esc((string)$row['short_description']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
