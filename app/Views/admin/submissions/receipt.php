<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Receipt</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/submissions/edit/' . rawurlencode((string)$entryId)) ?>">Back to Submission</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?= view('admin/submissions/_receipt_content', ['payment' => $payment]) ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
