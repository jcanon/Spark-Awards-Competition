<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Submit Payment</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase) ?>">Back to Cart</a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <p><strong>Total to be charged:</strong> $<?= number_format((float)$cart['total'], 2) ?></p>
            <p class="text-muted">You will be redirected to the secure Authorize.Net payment form.</p>

            <form id="anetHostedForm" method="post" action="<?= esc($hostedAction) ?>">
                <input type="hidden" name="token" value="<?= esc($token) ?>">
                <button type="submit" class="btn btn-success">Continue to Secure Checkout</button>
            </form>

            <p class="mt-3 small text-muted">If payment completes, the receipt will appear after webhook confirmation.</p>
        </div>
    </div>
</div>

<script src="/js/pages/payment-checkout.js"></script>

<?= $this->endSection() ?>
