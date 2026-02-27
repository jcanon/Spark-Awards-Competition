<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Design Types</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/competitions') ?>">Back to Competitions</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Select Competition Category</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/design-types') ?>" class="form-row">
                <div class="col-md-9 mb-2">
                    <label for="comp_type_id">Competition Category</label>
                    <select id="comp_type_id" name="comp_type_id" class="form-control" required>
                        <option value="">Select a category...</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= (int)$type->comp_type_id ?>" <?= (int)$selectedCompTypeId === (int)$type->comp_type_id ? 'selected' : '' ?>>
                                <?= esc((string)$type->comp_type_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">Load Design Types</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedCompTypeId > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Add Design Type</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= site_url('admin/design-types/store') ?>" class="form-row">
                    <?= csrf_field() ?>
                    <input type="hidden" name="comp_type_id" value="<?= (int)$selectedCompTypeId ?>">
                    <div class="col-md-9 mb-2">
                        <label for="design_type_name">Design Type Name</label>
                        <input id="design_type_name" type="text" name="design_type_name" class="form-control" maxlength="200" required value="<?= esc((string)old('design_type_name')) ?>">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-block" type="submit">Add Design Type</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th class="spark-w-75">Design Type</th>
                        <th class="spark-w-25">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="2" class="text-muted">No design types found for this category.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('admin/design-types/edit/' . (int)$row->design_type_id) ?>">
                                        <?= esc((string)$row->design_type_name) ?>
                                    </a>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/design-types/edit/' . (int)$row->design_type_id) ?>">Edit</a>
                                    <?php if ($canDelete): ?>
                                        <form action="<?= site_url('admin/design-types/delete/' . (int)$row->design_type_id) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this design type?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

