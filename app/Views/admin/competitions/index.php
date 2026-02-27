<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Competitions</h1>
        <div>
            <a class="btn btn-sm btn-primary" href="<?= site_url('admin/competitions/create') ?>">Add Competition</a>
            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/competition-types') ?>">Competition Categories</a>
        </div>
    </div>

    <?= view('partials/flash') ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable" data-order='[[1,"desc"],[0,"asc"]]'>
                <thead><tr><th>Competition</th><th>Year</th><th>Submissions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= site_url('admin/competitions/edit/' . (int)$row->comp_id) ?>"><?= esc((string)($row->comp_type_name ?? '')) ?></a></td>
                        <td><?= esc((string)($row->comp_year ?? '')) ?></td>
                        <td><?= (int)($row->submissions_count ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

