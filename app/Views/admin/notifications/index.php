<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
        <a class="btn btn-sm btn-primary" href="<?= site_url('admin/system-tools/notifications/create') ?>">Add Notification</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <?php if (!$schemaReady): ?>
        <div class="alert alert-warning">
            Notifications table not found yet. Run the SQL script provided for `comp_notifications` before using this page.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Audience</th>
                    <th>Level</th>
                    <th>Active</th>
                    <th>Starts</th>
                    <th>Ends</th>
                    <th>Sort</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <a class="font-weight-bold" href="<?= site_url('admin/system-tools/notifications/edit/' . (int)$row['notification_id']) ?>">
                                <?= esc((string)$row['title']) ?>
                            </a>
                        </td>
                        <td><?= esc(ucfirst((string)$row['audience'])) ?></td>
                        <td>
                            <span class="badge badge-<?= esc((string)$row['level']) ?>">
                                <?= esc(ucfirst((string)$row['level'])) ?>
                            </span>
                        </td>
                        <td><?= ((int)$row['is_active'] === 1) ? 'Yes' : 'No' ?></td>
                        <td><?= esc(format_datetime_ui((string)($row['starts_at'] ?? ''), '-')) ?></td>
                        <td><?= esc(format_datetime_ui((string)($row['ends_at'] ?? ''), '-')) ?></td>
                        <td><?= (int)($row['sort_order'] ?? 0) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/system-tools/notifications/edit/' . (int)$row['notification_id']) ?>">Edit</a>
                            <?php if ($canDelete): ?>
                                <form method="post" action="<?= site_url('admin/system-tools/notifications/delete/' . (int)$row['notification_id']) ?>" class="d-inline" onsubmit="return confirm('Delete this notification? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
