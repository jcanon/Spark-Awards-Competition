<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Reset Password</h1>

                <?php if ($msg = session('error')): ?>
                    <div class="alert alert-danger"><?= esc($msg) ?></div>
                <?php endif; ?>

                <form id="passwordResetForm" action="<?= site_url('auth/reset/' . $token) ?>" method="post" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input id="password" name="password" type="password" maxlength="128" class="form-control" required>
                        <div class="invalid-feedback">Please enter a password that meets all requirements.</div>
                        <div id="passwordRules" class="small mt-2 d-none">
                            <div data-rule="length" class="text-danger">At least 8 characters</div>
                            <div data-rule="upper" class="text-danger">At least 1 uppercase letter</div>
                            <div data-rule="lower" class="text-danger">At least 1 lowercase letter</div>
                            <div data-rule="number" class="text-danger">At least 1 number</div>
                            <div data-rule="symbol" class="text-danger">At least 1 symbol</div>
                        </div>
                        <div id="passwordStatus" class="small text-danger mt-1 d-none">Password does not meet all requirements yet.</div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password</label>
                        <input id="password_confirm" name="password_confirm" type="password" maxlength="128" class="form-control" required>
                        <div class="invalid-feedback">Please confirm your password.</div>
                        <div id="confirmStatus" class="small mt-1 d-none"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                    <a href="<?= site_url('auth/login') ?>" class="btn btn-link">Back to login</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.sparkPasswordResetI18n = {
        passwordWeak: <?= json_encode('Please enter a password that meets all requirements.') ?>,
        passwordStrong: <?= json_encode('Strong password.') ?>,
        passwordNeedsWork: <?= json_encode('Password does not meet all requirements yet.') ?>,
        confirmPrompt: <?= json_encode('Confirm your new password.') ?>,
        confirmMatch: <?= json_encode('Passwords match.') ?>,
        confirmNoMatch: <?= json_encode('Passwords do not match.') ?>
    };
</script>
<script src="/js/utils/password-policy.js"></script>
<script src="/js/pages/auth-reset.js"></script>

<?= $this->endSection() ?>
