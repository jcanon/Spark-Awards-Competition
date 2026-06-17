<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Receipt</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions/edit/' . rawurlencode((string)$entryId)) ?>">Back to Submission</a>
    </div>

    <?php $paymentStatus = strtolower(trim((string)($payment['payment_status'] ?? 'pending'))); ?>
    <div class="card<?= $paymentStatus === 'paid' ? ' border-left-success' : ($paymentStatus === 'held_for_review' ? ' border-left-warning' : '') ?>">
        <div class="card-body">
            <?php if ($paymentStatus === 'paid'): ?>
                <h5 class="text-success">Payment Confirmed</h5>
            <?php elseif ($paymentStatus === 'held_for_review'): ?>
                <h5 class="text-warning">Payment Under Review</h5>
            <?php else: ?>
                <h5 class="text-muted">Payment Details</h5>
            <?php endif; ?>
            <?= view('admin/submissions/_receipt_content', ['payment' => $payment]) ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
