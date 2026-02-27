<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Competition Retail Items</h1>
        <a class="btn btn-sm btn-primary" href="<?= site_url('admin/retail-items/create') ?>">Add Retail Item</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Phase</th>
                    <th>Price</th>
                    <th>Active</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= site_url('admin/retail-items/edit/' . (int)$row->retail_item_id) ?>"><?= esc((string)$row->item_name) ?></a></td>
                        <td><?= esc((string)$row->phase) ?></td>
                        <td>$<?= number_format((float)$row->item_price, 2) ?></td>
                        <td><?= esc(((string)$row->active === 'N') ? 'No' : 'Yes') ?></td>
                        <td><?= esc((string)$row->item_description) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/retail-items/edit/' . (int)$row->retail_item_id) ?>">Edit</a>
                            <?php if ($canDelete): ?>
                                <form action="<?= site_url('admin/retail-items/delete/' . (int)$row->retail_item_id) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this retail item?');">
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

