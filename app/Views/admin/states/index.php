<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">States / Provinces</h1>
        <a class="btn btn-sm btn-primary" href="<?= site_url('admin/system-tools/states/create') ?>">Add State / Province</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead><tr><th>State / Province Code</th><th>State / Province Name</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= site_url('admin/system-tools/states/edit/' . rawurlencode((string)$row->scode)) ?>"><?= esc((string)$row->scode) ?></a></td>
                        <td><?= esc((string)$row->state) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/system-tools/states/edit/' . rawurlencode((string)$row->scode)) ?>">Edit</a>
                            <?php if ($canDelete): ?>
                                <form action="<?= site_url('admin/system-tools/states/delete/' . rawurlencode((string)$row->scode)) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this State / Province?');">
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

