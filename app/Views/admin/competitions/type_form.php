<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Competition Category</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/competition-types') ?>">Back to Competition Categories</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-warning">
                Renaming a competition category also renames its yearly upload folders under <code>/public/uploads/&lt;year&gt;/&lt;category&gt;</code>
                and updates matching <code>comp_entry_photos</code> paths. If the destination folder already exists with files, the update is blocked.
            </div>
            <form method="post" action="<?= site_url('admin/competition-types/update/' . (int)$row->comp_type_id) ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <label for="comp_type_name">Competition Category Name <span class="text-danger">*</span></label>
                        <input
                            id="comp_type_name"
                            type="text"
                            name="comp_type_name"
                            class="form-control"
                            maxlength="100"
                            required
                            value="<?= esc((string)old('comp_type_name', (string)$row->comp_type_name)) ?>"
                        >
                        <div class="invalid-feedback">Competition category name is required.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="is_student_comp">Competition Type <span class="text-danger">*</span></label>
                        <select id="is_student_comp" name="is_student_comp" class="form-control" required>
                            <?php $isStudent = (string)old('is_student_comp', (string)((int)$row->is_student_comp)); ?>
                            <option value="0" <?= $isStudent === '0' ? 'selected' : '' ?>>Professional</option>
                            <option value="1" <?= $isStudent === '1' ? 'selected' : '' ?>>Student</option>
                        </select>
                        <div class="invalid-feedback">Competition type is required.</div>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">Update Category</button>
                <a class="btn btn-secondary" href="<?= site_url('admin/competition-types') ?>">Back to Competition Categories</a>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

