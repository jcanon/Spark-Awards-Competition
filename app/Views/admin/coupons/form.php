<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Coupon' : 'Add Coupon' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/coupons') ?>">Back to Coupons</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/coupons/update/' . (int)$row->coupon_id) : site_url('admin/coupons/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <?= view('partials/ui/card-start') ?>
                <div class="row">
                    <?php $couponCode = (string)(old('coupon_code') ?? ($row?->coupon_code ?? '')); ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Coupon Code',
                        'name' => 'coupon_code',
                        'value' => $couponCode,
                        'required' => true,
                        'attrs' => ['maxlength' => 25],
                    ]) ?>
                    <?php $couponType = (string)(old('coupon_type') ?? ($row?->coupon_type ?? 'Dollar')); ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Type',
                        'name' => 'coupon_type',
                        'required' => true,
                        'options' => [
                            ['value' => 'Dollar', 'label' => '$ Dollars Off', 'selected' => $couponType === 'Dollar'],
                            ['value' => 'Percentage', 'label' => '% Percentage Off', 'selected' => $couponType === 'Percentage'],
                        ],
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Amount',
                        'name' => 'coupon_amount',
                        'type' => 'number',
                        'value' => (string)(old('coupon_amount') ?? ($row?->coupon_amount ?? '')),
                        'required' => true,
                        'help' => 'Whole numbers / no symbols.',
                        'attrs' => ['data-filter' => 'digits', 'min' => 1, 'step' => 1, 'inputmode' => 'numeric', 'pattern' => '[0-9]+'],
                    ]) ?>
                </div>
                <div class="row">
                    <?php
                    $couponComp = (int)(old('coupon_comp') ?? ($row?->coupon_comp ?? 0));
                    $competitionOptions = [['value' => '0', 'label' => 'Global Coupon', 'selected' => ($couponComp === 0)]];
                    foreach ($competitions as $comp) {
                        $competitionOptions[] = [
                            'value' => (string)(int)$comp->comp_id,
                            'label' => (string)$comp->comp_type_name . ' ' . (string)$comp->comp_year,
                            'selected' => ($couponComp === (int)$comp->comp_id),
                        ];
                    }
                    $startDateValue = (string)(old('coupon_start_date') ?? (!empty($row?->coupon_start_date) ? date('Y-m-d\\TH:i', strtotime((string)$row?->coupon_start_date)) : ''));
                    $endDateValue = (string)(old('coupon_end_date') ?? (!empty($row?->coupon_end_date) ? date('Y-m-d\\TH:i', strtotime((string)$row?->coupon_end_date)) : ''));
                    ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Competition',
                        'name' => 'coupon_comp',
                        'options' => $competitionOptions,
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Start Date',
                        'name' => 'coupon_start_date',
                        'type' => 'datetime-local',
                        'value' => $startDateValue,
                        'required' => true,
                        'help' => '',
                        'attrs' => [],
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'End Date',
                        'name' => 'coupon_end_date',
                        'type' => 'datetime-local',
                        'value' => $endDateValue,
                        'required' => true,
                        'help' => '',
                        'attrs' => [],
                    ]) ?>
                </div>
                <div class="row">
                    <?php $singleUse = (string)(old('coupon_single_use') ?? ($row?->coupon_single_use ?? '0')); ?>
                    <?php $couponPhase = (string)(old('coupon_phase') ?? ($row?->coupon_phase ?? '0')); ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Payment Phase',
                        'name' => 'coupon_phase',
                        'required' => true,
                        'options' => [
                            ['value' => '0', 'label' => 'All', 'selected' => $couponPhase === '0'],
                            ['value' => '1', 'label' => 'Phase 1', 'selected' => $couponPhase === '1'],
                            ['value' => '2', 'label' => 'Phase 2', 'selected' => $couponPhase === '2'],
                        ],
                    ]) ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Usage',
                        'name' => 'coupon_single_use',
                        'required' => true,
                        'attrs' => ['aria-describedby' => 'coupon-usage-help'],
                        'options' => [
                            ['value' => '0', 'label' => 'Multi-use', 'selected' => $singleUse === '0'],
                            ['value' => '1', 'label' => 'One-time use', 'selected' => $singleUse === '1'],
                        ],
                    ]) ?>
                </div>
                <p id="coupon-usage-help" class="small text-muted">One-time codes are limited to one entry payment across all entrants. Starting checkout reserves the code for that payment, including payment retries.</p>
        <?= view('partials/ui/card-end') ?>

        <?= view('partials/forms/actions', [
            'submitLabel' => 'Save Coupon',
            'backLabel' => 'Back to Coupons',
            'backUrl' => site_url('admin/coupons'),
        ]) ?>
    </form>
</div>

<?= $this->endSection() ?>
