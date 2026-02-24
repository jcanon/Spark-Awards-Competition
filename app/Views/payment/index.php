<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc(lang('Entrant.review_order')) ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('submissions') ?>"><?= esc(lang('Entrant.back_to_submissions')) ?></a>
    </div>

    <?php if ($msg = session('success')): ?>
        <div class="alert alert-success"><?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = session('error')): ?>
        <div class="alert alert-danger"><?= esc($msg) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.order_summary')) ?></h6></div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th><?= esc(lang('Entrant.base_fee')) ?></th><td class="text-right">$<?= number_format((float)$cart['base'], 2) ?></td></tr>
                        <?php if ((float)$cart['series'] > 0): ?>
                            <tr><th><?= esc(lang('Entrant.series_fee')) ?></th><td class="text-right">$<?= number_format((float)$cart['series'], 2) ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($cart['addons'] as $addon): ?>
                            <?php if ($addon['selected']): ?>
                                <tr><th><?= esc($addon['name']) ?></th><td class="text-right">$<?= number_format((float)$addon['line_total'], 2) ?></td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <tr><th><?= esc(lang('Entrant.subtotal')) ?></th><td class="text-right">$<?= number_format((float)$cart['subtotal'], 2) ?></td></tr>
                        <tr><th><?= esc(lang('Entrant.discount')) ?></th><td class="text-right">- $<?= number_format((float)$cart['discount'], 2) ?></td></tr>
                        <tr class="table-primary"><th><?= esc(lang('Entrant.total')) ?></th><td class="text-right font-weight-bold">$<?= number_format((float)$cart['total'], 2) ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.coupon_code')) ?></h6></div>
                <div class="card-body">
                    <form action="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/coupon') ?>" method="post" class="form-inline">
                        <?= csrf_field() ?>
                        <input type="text" name="coupon_code" maxlength="25" class="form-control mr-2 mb-2" value="<?= esc((string)$cart['coupon_code']) ?>" placeholder="<?= esc(lang('Entrant.enter_coupon')) ?>">
                        <button class="btn btn-primary mb-2" type="submit"><?= esc(lang('Entrant.apply')) ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.additional_items')) ?></h6></div>
                <div class="card-body">
                    <form action="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/addons') ?>" method="post">
                        <?= csrf_field() ?>
                        <?php if ($cart['addons'] === []): ?>
                            <p class="text-muted mb-0"><?= esc(lang('Entrant.no_addons_available')) ?></p>
                        <?php else: ?>
                            <?php foreach ($cart['addons'] as $addon): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="addon_ids[]" id="addon_<?= (int)$addon['id'] ?>" value="<?= (int)$addon['id'] ?>" <?= $addon['selected'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="addon_<?= (int)$addon['id'] ?>">
                                        <?= esc($addon['name']) ?> ($<?= number_format((float)$addon['price'], 2) ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <button class="btn btn-outline-primary mt-2" type="submit"><?= esc(lang('Entrant.update_addons')) ?></button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card border-left-success">
                <div class="card-body">
                    <p class="small text-muted"><?= esc(lang('Entrant.proceed_secure_checkout_hint')) ?></p>
                    <a class="btn btn-success btn-block" href="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/checkout') ?>"><?= esc(lang('Entrant.proceed_to_payment')) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
