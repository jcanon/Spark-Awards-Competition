<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = is_array($row); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $isEdit ? 'Edit Notification' : 'Add Notification' ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools/notifications') ?>">Back to Notifications</a>
    </div>

    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <form method="post" action="<?= $isEdit ? site_url('admin/system-tools/notifications/update/' . (int)$row['notification_id']) : site_url('admin/system-tools/notifications/store') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" maxlength="150" required value="<?= esc((string)old('title', (string)($row['title'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Audience <span class="text-danger">*</span></label>
                        <?php $audience = (string)old('audience', (string)($row['audience'] ?? 'all')); ?>
                        <select name="audience" class="form-control" required>
                            <option value="all" <?= $audience === 'all' ? 'selected' : '' ?>>All Users</option>
                            <option value="admin" <?= $audience === 'admin' ? 'selected' : '' ?>>Admins</option>
                            <option value="editor" <?= $audience === 'editor' ? 'selected' : '' ?>>Editors</option>
                            <option value="judge" <?= $audience === 'judge' ? 'selected' : '' ?>>Judges</option>
                            <option value="user" <?= $audience === 'user' ? 'selected' : '' ?>>Entrants</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Level <span class="text-danger">*</span></label>
                        <?php $level = (string)old('level', (string)($row['level'] ?? 'info')); ?>
                        <select name="level" class="form-control" required>
                            <option value="info" <?= $level === 'info' ? 'selected' : '' ?>>Info</option>
                            <option value="success" <?= $level === 'success' ? 'selected' : '' ?>>Success</option>
                            <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>Warning</option>
                            <option value="danger" <?= $level === 'danger' ? 'selected' : '' ?>>Danger</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4" required><?= esc((string)old('message', (string)($row['message'] ?? ''))) ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Link URL (optional)</label>
                        <input type="text" name="link_url" class="form-control" maxlength="255" value="<?= esc((string)old('link_url', (string)($row['link_url'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Link Text (optional)</label>
                        <input type="text" name="link_text" class="form-control" maxlength="60" value="<?= esc((string)old('link_text', (string)($row['link_text'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= esc((string)old('sort_order', (string)($row['sort_order'] ?? '0'))) ?>">
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
                    <div class="col-md-6 form-group">
                        <label>Starts At (optional)</label>
                        <input type="datetime-local" name="starts_at" class="form-control" value="<?= !empty(old('starts_at', (string)($row['starts_at'] ?? ''))) ? esc(date('Y-m-d\\TH:i', strtotime((string)old('starts_at', (string)($row['starts_at'] ?? ''))))) : '' ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Ends At (optional)</label>
                        <input type="datetime-local" name="ends_at" class="form-control" value="<?= !empty(old('ends_at', (string)($row['ends_at'] ?? ''))) ? esc(date('Y-m-d\\TH:i', strtotime((string)old('ends_at', (string)($row['ends_at'] ?? ''))))) : '' ?>">
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Update Notification' : 'Create Notification' ?></button>
        <a class="btn btn-secondary" href="<?= site_url('admin/system-tools/notifications') ?>">Back to Notifications</a>
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
