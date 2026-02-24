<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Coupon' : 'Add Coupon' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/coupons') ?>">Back to Coupons</a>
    </div>

    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/coupons/update/' . (int)$row->coupon_id) : site_url('admin/coupons/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Coupon Code <span class="text-danger">*</span></label><input class="form-control" type="text" name="coupon_code" maxlength="25" required value="<?= esc((string)($row?->coupon_code ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>Type <span class="text-danger">*</span></label><select class="form-control" name="coupon_type" required><option value="Dollar" <?= (($row?->coupon_type ?? '') === 'Dollar') ? 'selected' : '' ?>>$ Dollars Off</option><option value="Percentage" <?= (($row?->coupon_type ?? '') === 'Percentage') ? 'selected' : '' ?>>% Percentage Off</option></select></div>
                    <div class="col-md-4 form-group"><label>Amount <span class="text-danger">*</span></label><input class="form-control" type="number" name="coupon_amount" required min="1" step="1" inputmode="numeric" pattern="[0-9]+" value="<?= esc((string)($row?->coupon_amount ?? '')) ?>"> (whole numbers / no symbols)</div>

                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>Competition</label><select class="form-control" name="coupon_comp"><option value="0">Global Coupon</option><?php foreach ($competitions as $comp): ?><option value="<?= (int)$comp->comp_id ?>" <?= ((int)($row?->coupon_comp ?? 0) === (int)$comp->comp_id) ? 'selected' : '' ?>><?= esc((string)$comp->comp_type_name . ' ' . (string)$comp->comp_year) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 form-group"><label>Start Date <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="coupon_start_date" required value="<?= !empty($row?->coupon_start_date) ? date('Y-m-d\\TH:i', strtotime((string)$row?->coupon_start_date)) : '' ?>"></div>
                    <div class="col-md-4 form-group"><label>End Date <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="coupon_end_date" required value="<?= !empty($row?->coupon_end_date) ? date('Y-m-d\\TH:i', strtotime((string)$row?->coupon_end_date)) : '' ?>"></div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save Coupon</button>
        <a class="btn btn-secondary" href="<?= site_url('admin/coupons') ?>">Back to Coupons</a>
    </form>
</div>

<script>
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        var amountField = document.querySelector('input[name="coupon_amount"]');
        if (amountField) {
            amountField.addEventListener('input', function () {
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
