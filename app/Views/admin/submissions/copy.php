<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Copy Submission</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions/edit/' . rawurlencode((string)$row['entry_id'])) ?>">Back to Submission</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-3">
                <strong><?= esc((string)$row['design_name']) ?></strong> is currently in
                <strong><?= esc((string)$row['comp_year'] . ' ' . (string)$row['comp_type_name']) ?></strong>.
            </p>

            <form method="get" action="<?= site_url('admin/submissions/copy/' . rawurlencode((string)$row['entry_id'])) ?>" class="form-row mb-3">
                <div class="col-md-4">
                    <label for="compYear">Destination Year</label>
                    <select id="compYear" name="compYear" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= (int)$y['comp_year'] ?>" <?= (int)$year === (int)$y['comp_year'] ? 'selected' : '' ?>>
                                <?= (int)$y['comp_year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <form action="<?= site_url('admin/submissions/copy/' . rawurlencode((string)$row['entry_id'])) ?>" method="post">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="col-md-4 form-group">
                        <label for="comp_id">Destination Competition</label>
                        <select id="comp_id" name="comp_id" class="form-control" required>
                            <option value="">Select Competition</option>
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?= (int)$comp['comp_id'] ?>">
                                    <?= esc((string)$comp['comp_type_name']) ?> (<?= (int)$year ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Copy Submission</button>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

