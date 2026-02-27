<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Review Your Order</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('submissions') ?>">Back to Submissions</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Order Summary</h6></div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Base Fee</th><td class="text-right">$<?= number_format((float)$cart['base'], 2) ?></td></tr>
                        <?php if ((float)$cart['series'] > 0): ?>
                            <tr><th>Series Fee</th><td class="text-right">$<?= number_format((float)$cart['series'], 2) ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($cart['addons'] as $addon): ?>
                            <?php if ($addon['selected']): ?>
                                <tr><th><?= esc($addon['name']) ?></th><td class="text-right">$<?= number_format((float)$addon['line_total'], 2) ?></td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <tr><th>Subtotal</th><td class="text-right">$<?= number_format((float)$cart['subtotal'], 2) ?></td></tr>
                        <tr><th>Discount</th><td class="text-right">- $<?= number_format((float)$cart['discount'], 2) ?></td></tr>
                        <tr class="table-primary"><th>Total</th><td class="text-right font-weight-bold">$<?= number_format((float)$cart['total'], 2) ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Coupon Code</h6></div>
                <div class="card-body">
                    <form action="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/coupon') ?>" method="post" class="form-inline">
                        <?= csrf_field() ?>
                        <input type="text" name="coupon_code" maxlength="25" class="form-control mr-2 mb-2" value="<?= esc((string)$cart['coupon_code']) ?>" placeholder="Enter coupon">
                        <button class="btn btn-primary mb-2" type="submit">Apply</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Additional Items</h6></div>
                <div class="card-body">
                    <form action="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/addons') ?>" method="post">
                        <?= csrf_field() ?>
                        <?php if ($cart['addons'] === []): ?>
                            <p class="text-muted mb-0">No add-on items are available for this phase.</p>
                        <?php else: ?>
                            <?php foreach ($cart['addons'] as $addon): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="addon_ids[]" id="addon_<?= (int)$addon['id'] ?>" value="<?= (int)$addon['id'] ?>" <?= $addon['selected'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="addon_<?= (int)$addon['id'] ?>">
                                        <?= esc($addon['name']) ?> ($<?= number_format((float)$addon['price'], 2) ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <button class="btn btn-outline-primary mt-2" type="submit">Update Add-ons</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card border-left-success">
                <div class="card-body">
                    <p class="small text-muted">Proceed to secure payment checkout.</p>
                    <a class="btn btn-success btn-block" href="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/checkout') ?>">Proceed to Payment</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

