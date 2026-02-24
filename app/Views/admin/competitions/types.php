<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Competition Categories</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/competitions') ?>">Back to Competitions</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Add Competition Category</h6></div>
        <div class="card-body">
            <form method="post" action="<?= site_url('admin/competition-types/store') ?>" class="form-row">
                <?= csrf_field() ?>
                <div class="col-md-5 mb-2">
                    <label for="comp_type_name">Competition Category Name</label>
                    <input id="comp_type_name" type="text" name="comp_type_name" class="form-control" maxlength="100" required value="<?= esc((string)old('comp_type_name')) ?>">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="is_student_comp">Competition Type</label>
                    <select id="is_student_comp" name="is_student_comp" class="form-control" required>
                        <option value="0" <?= old('is_student_comp', '0') === '0' ? 'selected' : '' ?>>Professional</option>
                        <option value="1" <?= old('is_student_comp') === '1' ? 'selected' : '' ?>>Student</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead><tr><th>Competition Category Name</th><th>Competition Type</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $name = is_object($row) ? (string)$row->comp_type_name : (string)($row['comp_type_name'] ?? '');
                        $student = is_object($row) ? (int)($row->is_student_comp ?? 0) : (int)($row['is_student_comp'] ?? 0);
                        $archived = is_object($row) ? (int)($row->archived ?? 0) : (int)($row['archived'] ?? 0);
                        $id = is_object($row) ? (int)$row->comp_type_id : (int)($row['comp_type_id'] ?? 0);
                    ?>
                    <?php if ($archived === 1) continue; ?>
                    <tr>
                        <td>
                            <a class="font-weight-bold" href="<?= site_url('admin/competition-types/edit/' . $id) ?>">
                                <?= esc($name) ?>
                            </a>
                        </td>
                        <td><?= $student === 1 ? 'Student' : 'Professional' ?></td>
                        <td>
                            <a href="<?= site_url('admin/competition-types/edit/' . $id) ?>" class="btn btn-sm btn-primary">Edit</a>
                            <form method="post" action="<?= site_url('admin/competition-types/archive/' . $id) ?>" class="d-inline" onsubmit="return confirm('Archive this competition category? This action can affect where this category appears.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-secondary">Archive</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
