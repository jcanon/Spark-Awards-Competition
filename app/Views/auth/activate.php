<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Account Activation</h1>

                <?php if ($status === 'doesNotExist'): ?>
                    <div class="alert alert-danger">This account does not exist. Please verify the activation link.</div>
                <?php elseif ($status === 'alreadyActive'): ?>
                    <div class="alert alert-info">This account has already been activated.</div>
                <?php else: ?>
                    <div class="alert alert-success">Your account has been activated. You can now sign in.</div>
                <?php endif; ?>

                <a href="<?= site_url('auth/login') ?>" class="btn btn-primary">Back to login</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
