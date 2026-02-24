<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc(lang('Entrant.payment_receipt')) ?></h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('submissions') ?>"><?= esc(lang('Entrant.back_to_submissions')) ?></a>
    </div>

    <?php if ($paid): ?>
        <div class="card border-left-success shadow">
            <div class="card-body">
                <h5 class="text-success"><?= esc(lang('Entrant.payment_confirmed')) ?></h5>
                <?= view('payment/_receipt_content', ['payment' => $payment]) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-left-warning shadow">
            <div class="card-body">
                <h5 class="text-warning"><?= esc(lang('Entrant.payment_pending')) ?></h5>
                <p><?= esc(lang('Entrant.payment_processing_refresh')) ?></p>
                <a class="btn btn-primary" href="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/receipt') ?>"><?= esc(lang('Entrant.refresh_receipt')) ?></a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
