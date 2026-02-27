<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Design Type</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/design-types?comp_type_id=' . (int)$row->comp_type_id) ?>">Back to Design Types</a>
    </div>

    <?= view('partials/flash') ?>

    <?= view('partials/ui/card-start') ?>
            <form method="post" action="<?= site_url('admin/design-types/update/' . (int)$row->design_type_id) ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="form-row">
                    <?php
                    $selectedCompType = (string)old('comp_type_id', (string)$row->comp_type_id);
                    $competitionTypeOptions = [];
                    foreach ($types as $type) {
                        $competitionTypeOptions[] = [
                            'value' => (string)(int)$type->comp_type_id,
                            'label' => (string)$type->comp_type_name,
                            'selected' => $selectedCompType === (string)$type->comp_type_id,
                        ];
                    }
                    ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-6 mb-3',
                        'label' => 'Competition Category',
                        'name' => 'comp_type_id',
                        'required' => true,
                        'options' => $competitionTypeOptions,
                        'invalid' => 'Competition category is required.',
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-6 mb-3',
                        'label' => 'Design Type Name',
                        'name' => 'design_type_name',
                        'value' => (string)old('design_type_name', (string)$row->design_type_name),
                        'required' => true,
                        'attrs' => ['maxlength' => 200],
                        'invalid' => 'Design type name is required.',
                    ]) ?>
                </div>

                <?= view('partials/forms/actions', [
                    'submitLabel' => 'Save Changes',
                    'backLabel' => 'Back to Design Types',
                    'backUrl' => site_url('admin/design-types?comp_type_id=' . (int)$row->comp_type_id),
                ]) ?>
            </form>
    <?= view('partials/ui/card-end') ?>
</div>

<?= $this->endSection() ?>
