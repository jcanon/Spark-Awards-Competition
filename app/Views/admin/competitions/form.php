<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<?php
$existingTypeIdsByYear = isset($existingTypeIdsByYear) && is_array($existingTypeIdsByYear) ? $existingTypeIdsByYear : [];
$fieldValue = static function (string $field, string $default = '') use ($row): string {
    $oldValue = old($field);
    if ($oldValue !== null) {
        return (string)$oldValue;
    }

    $raw = $row?->{$field} ?? null;
    if ($raw === null || $raw === '') {
        return $default;
    }

    return (string)$raw;
};

$datetimeValue = static function (string $field) use ($row): string {
    $oldValue = old($field);
    if ($oldValue !== null) {
        return (string)$oldValue;
    }

    $raw = $row?->{$field} ?? null;
    if ($raw === null || $raw === '') {
        return '';
    }

    $ts = strtotime((string)$raw);
    if ($ts === false) {
        return '';
    }

    return date('Y-m-d\\TH:i', $ts);
};
?>
<div class="container-fluid">
    <link href="/css/pages/admin-competitions-form.css" rel="stylesheet">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Competition' : 'Add Competition' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/competitions') ?>">Back to Competitions</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/competitions/update/' . (int)$row->comp_id) : site_url('admin/competitions/store') ?>" class="needs-validation" novalidate data-existing-type-ids-by-year="<?= esc(json_encode($existingTypeIdsByYear, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>">
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Competition Category <span class="text-danger">*</span></label>
                        <?php if ($isEdit): ?>
                            <select class="form-control" name="comp_type_id" required>
                                <option value="">Select category</option>
                                <?php foreach ($types as $t): ?>
                                    <?php
                                    $selected = false;
                                    $oldTypeId = old('comp_type_id');
                                    if ($oldTypeId !== null && $oldTypeId !== '') {
                                        $selected = (int)$oldTypeId === (int)$t->comp_type_id;
                                    } elseif ($isEdit && (int)$row->comp_type_id === (int)$t->comp_type_id) {
                                        $selected = true;
                                    }
                                    ?>
                                    <option value="<?= (int)$t->comp_type_id ?>" <?= $selected ? 'selected' : '' ?>><?= esc((string)$t->comp_type_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?php $selectedTypeIds = array_map('intval', (array)old('comp_type_ids', [])); ?>
                            <?php $selectedYear = (int)$fieldValue('comp_year', date('Y')); ?>
                            <?php $existingTypeIdsForYear = array_map('intval', (array)($existingTypeIdsByYear[$selectedYear] ?? [])); ?>
                            <select class="form-control" name="comp_type_ids[]" required multiple size="10">
                                <?php foreach ($types as $t): ?>
                                    <?php $selected = in_array((int)$t->comp_type_id, $selectedTypeIds, true); ?>
                                    <?php $disabled = in_array((int)$t->comp_type_id, $existingTypeIdsForYear, true); ?>
                                    <?php $baseLabel = (string)$t->comp_type_name; ?>
                                    <option data-base-label="<?= esc($baseLabel) ?>" value="<?= (int)$t->comp_type_id ?>" <?= $selected ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>><?= esc($baseLabel . ($disabled ? ' (Already exists for selected year)' : '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple categories.</small>
                            <small class="form-text text-muted">Categories that already exist for the selected year are disabled.</small>
                            <div class="invalid-feedback">Please select at least one competition category.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 form-group"><label>Competition Year <span class="text-danger">*</span></label><input class="form-control" type="number" name="comp_year" min="2000" max="2100" required value="<?= esc($fieldValue('comp_year', date('Y'))) ?>"></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Schedule</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group"><label>Phase 1 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_1_open" required value="<?= esc($datetimeValue('comp_phase_1_open')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Regular Registration Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_regular_reg_open" required value="<?= esc($datetimeValue('comp_regular_reg_open')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Late Registration Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_late_reg_open" required value="<?= esc($datetimeValue('comp_late_reg_open')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Phase 1 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_1_close" required value="<?= esc($datetimeValue('comp_phase_1_close')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Jury Phase 1 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_1_open" required value="<?= esc($datetimeValue('jury_phase_1_open')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Jury Phase 1 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_1_close" required value="<?= esc($datetimeValue('jury_phase_1_close')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Phase 2 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_2_open" required value="<?= esc($datetimeValue('comp_phase_2_open')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Phase 2 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="comp_phase_2_close" required value="<?= esc($datetimeValue('comp_phase_2_close')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Jury Phase 2 Open <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_2_open" required value="<?= esc($datetimeValue('jury_phase_2_open')) ?>"></div>
                    <div class="col-md-3 form-group"><label>Jury Phase 2 Close <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" name="jury_phase_2_close" required value="<?= esc($datetimeValue('jury_phase_2_close')) ?>"></div>
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
                            <?php
                            $label = ucwords(str_replace('_', ' ', $field));
                            $label = preg_replace('/\bPro\b/', 'Professional', $label) ?? $label;
                            $label = preg_replace('/\bReg\b/', 'Registration', $label) ?? $label;
                            ?>
                            <label><?= esc($label) ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="text" class="form-control" name="<?= esc($field) ?>" value="<?= esc($displayValue) ?>" required>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Competition' : 'Save Competitions' ?></button>
        <a class="btn btn-secondary" href="<?= site_url('admin/competitions') ?>">Back to Competitions</a>
    </form>
</div>

<script src="/js/pages/admin-competitions-form.js"></script>

<?= $this->endSection() ?>

