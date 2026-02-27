<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Receipt</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('submissions') ?>">Back to Submissions</a>
    </div>

    <?php if ($paid): ?>
        <div class="card border-left-success shadow">
            <div class="card-body">
                <h5 class="text-success">Payment Confirmed</h5>
                <?= view('payment/_receipt_content', ['payment' => $payment]) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-left-warning shadow">
            <div class="card-body">
                <h5 class="text-warning">Payment Pending</h5>
                <p>Your payment may still be processing. Refresh this page after a moment.</p>
                <a class="btn btn-primary" href="<?= esc(current_url(true)->__toString()) ?>">Refresh Receipt</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
