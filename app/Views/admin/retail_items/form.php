<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Retail Item' : 'Add Retail Item' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/retail-items') ?>">Back to Retail Items</a>
    </div>

    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/retail-items/update/' . (int)$row->retail_item_id) : site_url('admin/retail-items/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label>Item Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="item_name" maxlength="200" required value="<?= esc((string)(old('item_name') ?? ($row?->item_name ?? ''))) ?>">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Phase <span class="text-danger">*</span></label>
                        <?php $phaseVal = (string)(old('phase') ?? ($row?->phase ?? '1')); ?>
                        <select class="form-control" name="phase" required>
                            <option value="1" <?= $phaseVal === '1' ? 'selected' : '' ?>>1</option>
                            <option value="2" <?= $phaseVal === '2' ? 'selected' : '' ?>>2</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Price (USD) <span class="text-danger">*</span></label>
                        <input class="form-control" type="number" name="item_price" required min="0" step="1" inputmode="numeric" pattern="[0-9]+" value="<?= esc((string)(old('item_price') ?? ($row?->item_price ?? '0'))) ?>">
                        <small class="text-muted">Whole dollars only (e.g. 100).</small>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Active <span class="text-danger">*</span></label>
                        <?php $activeVal = (string)(old('active') ?? ($row?->active ?? 'Y')); ?>
                        <select class="form-control" name="active" required>
                            <option value="Y" <?= $activeVal === 'Y' ? 'selected' : '' ?>>Yes</option>
                            <option value="N" <?= $activeVal === 'N' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Item Description</label>
                        <textarea class="form-control" name="item_description" rows="4" maxlength="65535"><?= esc((string)(old('item_description') ?? ($row?->item_description ?? ''))) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Save Retail Item' ?></button>
        <a class="btn btn-secondary" href="<?= site_url('admin/retail-items') ?>">Back to Retail Items</a>
    </form>
</div>

<script>
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        var priceField = document.querySelector('input[name="item_price"]');
        if (priceField) {
            priceField.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
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

