<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Competition' : 'Add Competition' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/competitions') ?>">Back to Competitions</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/competitions/update/' . (int)$row->comp_id) : site_url('admin/competitions/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Competition Category <span class="text-danger">*</span></label>
                        <select class="form-control" name="comp_type_id" required>
                            <option value="">Select category</option>
                            <?php foreach ($types as $t): ?>
                                <?php $selected = $isEdit && (int)$row->comp_type_id === (int)$t->comp_type_id; ?>
                                <option value="<?= (int)$t->comp_type_id ?>" <?= $selected ? 'selected' : '' ?>><?= esc((string)$t->comp_type_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group"><label>Competition Year <span class="text-danger">*</span></label><input class="form-control" type="number" name="comp_year" min="2000" max="2100" required value="<?= esc((string)($row?->comp_year ?? date('Y'))) ?>"></div>
                    <div class="col-md-3 form-group"><label>Shortlist Enabled</label><select class="form-control" name="shortlist_enabled"><option value="0" <?= ((int)($row?->shortlist_enabled ?? 0) === 0) ? 'selected' : '' ?>>No</option><option value="1" <?= ((int)($row?->shortlist_enabled ?? 0) === 1) ? 'selected' : '' ?>>Yes</option></select></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Schedule</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group"><label>Phase 1 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_1_open" required value="<?= !empty($row?->comp_phase_1_open) ? date('Y-m-d\\TH:i', strtotime((string)$row?->comp_phase_1_open)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Regular Reg Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_regular_reg_open" required value="<?= !empty($row?->comp_regular_reg_open) ? date('Y-m-d\\TH:i', strtotime((string)$row?->comp_regular_reg_open)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Late Reg Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_late_reg_open" required value="<?= !empty($row?->comp_late_reg_open) ? date('Y-m-d\\TH:i', strtotime((string)$row?->comp_late_reg_open)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Phase 1 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_1_close" required value="<?= !empty($row?->comp_phase_1_close) ? date('Y-m-d\\TH:i', strtotime((string)$row?->comp_phase_1_close)) : '' ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Jury Phase 1 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_1_open" required value="<?= !empty($row?->jury_phase_1_open) ? date('Y-m-d\\TH:i', strtotime((string)$row?->jury_phase_1_open)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Jury Phase 1 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_1_close" required value="<?= !empty($row?->jury_phase_1_close) ? date('Y-m-d\\TH:i', strtotime((string)$row?->jury_phase_1_close)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Phase 2 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_2_open" required value="<?= !empty($row?->comp_phase_2_open) ? date('Y-m-d\\TH:i', strtotime((string)$row?->comp_phase_2_open)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Phase 2 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_2_close" required value="<?= !empty($row?->comp_phase_2_close) ? date('Y-m-d\\TH:i', strtotime((string)$row?->comp_phase_2_close)) : '' ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Jury Phase 2 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_2_open" required value="<?= !empty($row?->jury_phase_2_open) ? date('Y-m-d\\TH:i', strtotime((string)$row?->jury_phase_2_open)) : '' ?>"></div>
                    <div class="col-md-3 form-group"><label>Jury Phase 2 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_2_close" required value="<?= !empty($row?->jury_phase_2_close) ? date('Y-m-d\\TH:i', strtotime((string)$row?->jury_phase_2_close)) : '' ?>"></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Pricing</h6></div>
            <div class="card-body">
                <?php
                $fields = [
                    'pro_early_reg_price','pro_regular_reg_price','pro_late_reg_price','pro_finalist_price','pro_winner_price','pro_series_price',
                    'student_early_reg_price','student_regular_reg_price','student_late_reg_price','student_finalist_price','student_winner_price','student_series_price',
                    'trophy_price','additional_trophy_price',
                ];
                ?>
                <div class="row">
                    <?php foreach ($fields as $field): ?>
                        <?php
                        $oldValue = old($field);
                        if ($oldValue !== null) {
                            $displayValue = (string)$oldValue;
                        } else {
                            $rawValue = $row?->{$field} ?? null;
                            $displayValue = is_numeric($rawValue) ? number_format((float)$rawValue, 2, '.', '') : (string)($rawValue ?? '');
                        }
                        ?>
                        <div class="col-md-3 form-group">
                            <label><?= esc(ucwords(str_replace('_', ' ', $field))) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="<?= esc($field) ?>" value="<?= esc($displayValue) ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Save Competition</button>
        <a class="btn btn-secondary" href="<?= site_url('admin/competitions') ?>">Back to Competitions</a>
    </form>
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
