<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Country' : 'Add Country' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools/countries') ?>">Back to Countries</a>
    </div>

    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/system-tools/countries/update/' . rawurlencode((string)$row->ccode)) : site_url('admin/system-tools/countries/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Country Code <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="ccode" maxlength="2" minlength="2" required value="<?= esc(strtoupper((string)(old('ccode') ?? ($row?->ccode ?? '')))) ?>">
                    </div>
                    <div class="col-md-9 form-group">
                        <label>Country Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="country" maxlength="200" required value="<?= esc((string)(old('country') ?? ($row?->country ?? ''))) ?>">
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Save Country' ?></button>
        <a class="btn btn-secondary" href="<?= site_url('admin/system-tools/countries') ?>">Back to Countries</a>
    </form>
</div>

<script>
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        var code = document.querySelector('input[name="ccode"]');
        if (code) {
            code.addEventListener('input', function () {
                this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 2);
            });
        }
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
