<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Forgot Your Login?</h1>

                <?= view('partials/flash', ['showSuccess' => false]) ?>

                <?php if ($sent ?? false): ?>
                    <div class="alert alert-success">If your email exists in our system, a reset link has been sent.</div>
                    <a href="<?= site_url('auth/login') ?>" class="btn btn-primary">Back to login</a>
                <?php else: ?>
                    <p class="text-muted">Enter your account email and we will send a password reset link.</p>
                    <form action="<?= site_url('auth/forgot') ?>" method="post" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="forgot_email">Email Address</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                                </div>
                                <input id="forgot_email" name="forgot_email" type="email" maxlength="100" class="form-control" value="<?= esc(old('forgot_email')) ?>" required>
                            </div>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Request</button>
                        <a href="<?= site_url('auth/login') ?>" class="btn btn-link">Back to login</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
