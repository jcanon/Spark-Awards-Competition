<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Countries</h1>
        <a class="btn btn-sm btn-primary" href="<?= site_url('admin/system-tools/countries/create') ?>">Add Country</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead><tr><th>Code</th><th>Country</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= site_url('admin/system-tools/countries/edit/' . rawurlencode((string)$row->ccode)) ?>"><?= esc((string)$row->ccode) ?></a></td>
                        <td><?= esc((string)$row->country) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/system-tools/countries/edit/' . rawurlencode((string)$row->ccode)) ?>">Edit</a>
                            <?php if ($canDelete): ?>
                                <form action="<?= site_url('admin/system-tools/countries/delete/' . rawurlencode((string)$row->ccode)) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this country?');">
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
</div>

<?= $this->endSection() ?>

