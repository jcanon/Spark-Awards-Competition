<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = is_array($row); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Notification' : 'Add Notification' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools/notifications') ?>">Back to Notifications</a>
    </div>

    <?= view('partials/flash') ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/system-tools/notifications/update/' . (int)$row['notification_id']) : site_url('admin/system-tools/notifications/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <?= view('partials/ui/card-start') ?>
                <div class="row">
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-6 form-group',
                        'label' => 'Title',
                        'name' => 'title',
                        'value' => (string)old('title', (string)($row['title'] ?? '')),
                        'required' => true,
                        'attrs' => ['maxlength' => 150],
                    ]) ?>
                    <?php $audience = (string)old('audience', (string)($row['audience'] ?? 'all')); ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-3 form-group',
                        'label' => 'Audience',
                        'name' => 'audience',
                        'required' => true,
                        'options' => [
                            ['value' => 'all', 'label' => 'All Users', 'selected' => $audience === 'all'],
                            ['value' => 'admin', 'label' => 'Admins', 'selected' => $audience === 'admin'],
                            ['value' => 'editor', 'label' => 'Editors', 'selected' => $audience === 'editor'],
                            ['value' => 'judge', 'label' => 'Judges', 'selected' => $audience === 'judge'],
                            ['value' => 'user', 'label' => 'Entrants', 'selected' => $audience === 'user'],
                        ],
                    ]) ?>
                    <?php $level = (string)old('level', (string)($row['level'] ?? 'info')); ?>
                    <?= view('partials/forms/select', [
                        'colClass' => 'col-md-3 form-group',
                        'label' => 'Level',
                        'name' => 'level',
                        'required' => true,
                        'options' => [
                            ['value' => 'info', 'label' => 'Info', 'selected' => $level === 'info'],
                            ['value' => 'success', 'label' => 'Success', 'selected' => $level === 'success'],
                            ['value' => 'warning', 'label' => 'Warning', 'selected' => $level === 'warning'],
                            ['value' => 'danger', 'label' => 'Danger', 'selected' => $level === 'danger'],
                        ],
                    ]) ?>
                </div>

                <div class="row">
                    <?= view('partials/forms/textarea', [
                        'colClass' => 'col-md-12 form-group',
                        'label' => 'Message',
                        'name' => 'message',
                        'rows' => 4,
                        'required' => true,
                        'value' => (string)old('message', (string)($row['message'] ?? '')),
                    ]) ?>
                </div>

                <div class="row">
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Link URL (optional)',
                        'name' => 'link_url',
                        'value' => (string)old('link_url', (string)($row['link_url'] ?? '')),
                        'attrs' => ['maxlength' => 255],
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Link Text (optional)',
                        'name' => 'link_text',
                        'value' => (string)old('link_text', (string)($row['link_text'] ?? '')),
                        'attrs' => ['maxlength' => 60],
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-2 form-group',
                        'label' => 'Sort Order',
                        'name' => 'sort_order',
                        'type' => 'number',
                        'value' => (string)old('sort_order', (string)($row['sort_order'] ?? '0')),
                    ]) ?>
                    <div class="col-md-2 form-group d-flex align-items-center">
                        <?php $active = (string)old('is_active', isset($row['is_active']) ? ((int)$row['is_active'] === 1 ? '1' : '0') : '1'); ?>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $active === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php $startsAt = (string)old('starts_at', (string)($row['starts_at'] ?? '')); ?>
                    <?php $endsAt = (string)old('ends_at', (string)($row['ends_at'] ?? '')); ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-6 form-group',
                        'label' => 'Starts At (optional)',
                        'name' => 'starts_at',
                        'type' => 'datetime-local',
                        'value' => $startsAt !== '' ? date('Y-m-d\\TH:i', strtotime($startsAt)) : '',
                    ]) ?>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-6 form-group',
                        'label' => 'Ends At (optional)',
                        'name' => 'ends_at',
                        'type' => 'datetime-local',
                        'value' => $endsAt !== '' ? date('Y-m-d\\TH:i', strtotime($endsAt)) : '',
                    ]) ?>
                </div>
        <?= view('partials/ui/card-end') ?>

        <?= view('partials/forms/actions', [
            'submitLabel' => $isEdit ? 'Update Notification' : 'Create Notification',
            'backLabel' => 'Back to Notifications',
            'backUrl' => site_url('admin/system-tools/notifications'),
        ]) ?>
    </form>
</div>

<?= $this->endSection() ?>
