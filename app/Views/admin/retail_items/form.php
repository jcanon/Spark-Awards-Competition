<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Retail Item' : 'Add Retail Item' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/retail-items') ?>">Back to Retail Items</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/retail-items/update/' . (int)$row->retail_item_id) : site_url('admin/retail-items/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <?= view('partials/ui/card-start') ?>
                <div class="row">
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-5 form-group',
                        'label' => 'Item Name',
                        'name' => 'item_name',
                        'value' => (string)(old('item_name') ?? ($row?->item_name ?? '')),
                        'required' => true,
                        'attrs' => ['maxlength' => 200],
                    ]) ?>
                    <?php $phaseVal = (string)(old('phase') ?? ($row?->phase ?? '1')); ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-2 form-group',
                        'label' => 'Phase',
                        'name' => 'phase',
                        'required' => true,
                        'options' => [
                            ['value' => '1', 'label' => '1', 'selected' => $phaseVal === '1'],
                            ['value' => '2', 'label' => '2', 'selected' => $phaseVal === '2'],
                        ],
                    ]) ?>
                    <div class="col-md-3 form-group">
                        <label for="item_price">Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input
                                id="item_price"
                                class="form-control"
                                type="number"
                                name="item_price"
                                value="<?= esc((string)(old('item_price') ?? ($row?->item_price ?? '0'))) ?>"
                                required
                                data-filter="digits"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                pattern="[0-9]+"
                            >
                        </div>
                        <small class="text-muted">Whole dollars only (e.g. 100).</small>
                    </div>
                    <?php $activeVal = (string)(old('active') ?? ($row?->active ?? 'Y')); ?>
                    <div class="col-md-2 form-group">
                        <label for="active">Active <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-toggle-on" aria-hidden="true"></i></span>
                            </div>
                            <select id="active" name="active" class="form-control" required>
                                <option value="Y" <?= $activeVal === 'Y' ? 'selected' : '' ?>>Yes</option>
                                <option value="N" <?= $activeVal === 'N' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?= view('partials/forms/textarea', [
                        'colClass' => 'col-md-12 form-group',
                        'label' => 'Item Description',
                        'name' => 'item_description',
                        'rows' => 4,
                        'value' => (string)(old('item_description') ?? ($row?->item_description ?? '')),
                        'attrs' => ['maxlength' => 65535],
                    ]) ?>
                </div>
        <?= view('partials/ui/card-end') ?>

        <?= view('partials/forms/actions', [
            'submitLabel' => $isEdit ? 'Save Changes' : 'Save Retail Item',
            'backLabel' => 'Back to Retail Items',
            'backUrl' => site_url('admin/retail-items'),
        ]) ?>
    </form>
</div>

<?= $this->endSection() ?>
