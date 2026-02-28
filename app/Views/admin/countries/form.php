<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Country' : 'Add Country' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools/countries') ?>">Back to Countries</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/system-tools/countries/update/' . rawurlencode((string)$row->ccode)) : site_url('admin/system-tools/countries/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <?= view('partials/ui/card-start') ?>
                <div class="row">
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-3 form-group',
                        'label' => 'Country Code',
                        'name' => 'ccode',
                        'value' => strtoupper((string)(old('ccode') ?? ($row?->ccode ?? ''))),
                        'required' => true,
                        'attrs' => ['data-filter' => 'alpha2', 'maxlength' => 2, 'minlength' => 2],
                    ]) ?>
                    <div class="col-md-9 form-group">
                        <label for="country">Country Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-flag" aria-hidden="true"></i></span>
                            </div>
                            <input
                                id="country"
                                name="country"
                                class="form-control"
                                maxlength="200"
                                required
                                value="<?= esc((string)(old('country') ?? ($row?->country ?? ''))) ?>"
                            >
                        </div>
                    </div>
                </div>
        <?= view('partials/ui/card-end') ?>

        <?= view('partials/forms/actions', [
            'submitLabel' => $isEdit ? 'Save Changes' : 'Save Country',
            'backLabel' => 'Back to Countries',
            'backUrl' => site_url('admin/system-tools/countries'),
        ]) ?>
    </form>
</div>

<?= $this->endSection() ?>
