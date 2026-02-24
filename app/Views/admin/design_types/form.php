<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Design Type</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/design-types?comp_type_id=' . (int)$row->comp_type_id) ?>">Back to Design Types</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= site_url('admin/design-types/update/' . (int)$row->design_type_id) ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="col-md-6 mb-3">
                        <label for="comp_type_id">Competition Category <span class="text-danger">*</span></label>
                        <select id="comp_type_id" name="comp_type_id" class="form-control" required>
                            <?php $selectedCompType = (string)old('comp_type_id', (string)$row->comp_type_id); ?>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= (int)$type->comp_type_id ?>" <?= $selectedCompType === (string)$type->comp_type_id ? 'selected' : '' ?>>
                                    <?= esc((string)$type->comp_type_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Competition category is required.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="design_type_name">Design Type Name <span class="text-danger">*</span></label>
                        <input
                            id="design_type_name"
                            type="text"
                            name="design_type_name"
                            class="form-control"
                            maxlength="200"
                            required
                            value="<?= esc((string)old('design_type_name', (string)$row->design_type_name)) ?>"
                        >
                        <div class="invalid-feedback">Design type name is required.</div>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">Save Changes</button>
                <a class="btn btn-secondary" href="<?= site_url('admin/design-types?comp_type_id=' . (int)$row->comp_type_id) ?>">Back to Design Types</a>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?= $this->endSection() ?>

