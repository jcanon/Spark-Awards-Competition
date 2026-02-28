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
                    <div class="col-md-3 form-group">
                        <label for="audience">Audience <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-users" aria-hidden="true"></i></span>
                            </div>
                            <select id="audience" name="audience" class="form-control" required>
                                <option value="all" <?= $audience === 'all' ? 'selected' : '' ?>>All Users</option>
                                <option value="admin" <?= $audience === 'admin' ? 'selected' : '' ?>>Admins</option>
                                <option value="editor" <?= $audience === 'editor' ? 'selected' : '' ?>>Editors</option>
                                <option value="judge" <?= $audience === 'judge' ? 'selected' : '' ?>>Judges</option>
                                <option value="user" <?= $audience === 'user' ? 'selected' : '' ?>>Entrants</option>
                            </select>
                        </div>
                    </div>
                    <?php $level = (string)old('level', (string)($row['level'] ?? 'info')); ?>
                    <div class="col-md-3 form-group">
                        <label for="level">Level <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-layer-group" aria-hidden="true"></i></span>
                            </div>
                            <select id="level" name="level" class="form-control" required>
                                <option value="info" <?= $level === 'info' ? 'selected' : '' ?>>Info</option>
                                <option value="success" <?= $level === 'success' ? 'selected' : '' ?>>Success</option>
                                <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>Warning</option>
                                <option value="danger" <?= $level === 'danger' ? 'selected' : '' ?>>Danger</option>
                            </select>
                        </div>
                    </div>
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
                    <div class="col-md-4 form-group">
                        <label for="link_url">Link URL (optional)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-link" aria-hidden="true"></i></span>
                            </div>
                            <input
                                id="link_url"
                                name="link_url"
                                class="form-control"
                                maxlength="255"
                                value="<?= esc((string)old('link_url', (string)($row['link_url'] ?? ''))) ?>"
                            >
                        </div>
                    </div>
                    <?= view('partials/forms/input', [
                        'colClass' => 'col-md-4 form-group',
                        'label' => 'Link Text (optional)',
                        'name' => 'link_text',
                        'value' => (string)old('link_text', (string)($row['link_text'] ?? '')),
                        'attrs' => ['maxlength' => 60],
                    ]) ?>
                    <div class="col-md-2 form-group">
                        <label for="sort_order">Sort Order</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sort-numeric-down" aria-hidden="true"></i></span>
                            </div>
                            <input
                                id="sort_order"
                                name="sort_order"
                                type="number"
                                class="form-control"
                                value="<?= esc((string)old('sort_order', (string)($row['sort_order'] ?? '0'))) ?>"
                            >
                        </div>
                    </div>
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
