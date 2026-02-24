<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc(lang('Entrant.submit_payment')) ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase) ?>"><?= esc(lang('Entrant.back_to_cart')) ?></a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <p><strong><?= esc(lang('Entrant.total_to_be_charged')) ?></strong> $<?= number_format((float)$cart['total'], 2) ?></p>
            <p class="text-muted"><?= esc(lang('Entrant.redirect_authorizenet')) ?></p>

            <form id="anetHostedForm" method="post" action="<?= esc($hostedAction) ?>">
                <input type="hidden" name="token" value="<?= esc($token) ?>">
                <button type="submit" class="btn btn-success"><?= esc(lang('Entrant.continue_secure_checkout')) ?></button>
            </form>

            <p class="mt-3 small text-muted"><?= esc(lang('Entrant.receipt_after_webhook')) ?></p>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('anetHostedForm');
        if (form) {
            setTimeout(function () { form.submit(); }, 300);
        }
    })();
</script>

<?= $this->endSection() ?>
