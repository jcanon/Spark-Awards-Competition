<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Coupons</h1>
        <a class="btn btn-sm btn-primary" href="<?= site_url('admin/coupons/create') ?>">Add Coupon</a>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead><tr><th>Code</th><th>Type</th><th>Amount</th><th>Competition</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php $couponCompId = (int)($row->coupon_comp ?? 0); ?>
                        <td><a class="font-weight-bold" href="<?= site_url('admin/coupons/edit/' . (int)$row->coupon_id) ?>"><?= esc((string)$row->coupon_code) ?></a></td>
                        <td><?= esc((string)$row->coupon_type) ?></td>
                        <td><?= esc((string)$row->coupon_amount) ?></td>
                        <td><?= esc((string)($competitionLabels[$couponCompId] ?? 'Unknown Competition')) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row->coupon_start_date)) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row->coupon_end_date)) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary mr-1" href="<?= site_url('admin/coupons/edit/' . (int)$row->coupon_id) ?>">Edit</a>
                            <?php if ($canDelete): ?>
                                <form action="<?= site_url('admin/coupons/delete/' . (int)$row->coupon_id) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
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
