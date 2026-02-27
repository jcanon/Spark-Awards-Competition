<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit State / Province' : 'Add State / Province' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools/states') ?>">Back to States / Provinces</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/system-tools/states/update/' . rawurlencode((string)$row->scode)) : site_url('admin/system-tools/states/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <?= view('partials/ui/card-start') ?>
                <div class="row">
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-3 form-group',
                        'label' => 'State / Province Code',
                        'name' => 'scode',
                        'value' => strtoupper((string)(old('scode') ?? ($row?->scode ?? ''))),
                        'required' => true,
                        'attrs' => ['data-filter' => 'alpha2', 'maxlength' => 2, 'minlength' => 2],
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-9 form-group',
                        'label' => 'State / Province Name',
                        'name' => 'state',
                        'value' => (string)(old('state') ?? ($row?->state ?? '')),
                        'required' => true,
                        'attrs' => ['maxlength' => 32],
                    ]) ?>
                </div>
        <?= view('partials/ui/card-end') ?>

        <?= view('partials/forms/actions', [
            'submitLabel' => $isEdit ? 'Save Changes' : 'Save State / Province',
            'backLabel' => 'Back to States / Provinces',
            'backUrl' => site_url('admin/system-tools/states'),
        ]) ?>
    </form>
</div>

<?= $this->endSection() ?>
