<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm border-left-warning">
            <div class="card-body p-4 text-center">
                <h1 class="h4 mb-3">Two-Factor Authentication</h1>

                <?php if ($msg = session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger text-left"><?= esc($msg) ?></div>
                <?php endif; ?>

                <p class="text-muted">Admin and Editor accounts require Duo verification.</p>

                <a href="<?= esc($duoUrl) ?>" class="btn btn-primary">Continue to Duo</a>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-secondary ml-2">Cancel and Log Out</a>

                <hr class="my-4">

                <h2 class="h6 mb-3">Can't access Duo?</h2>
                <?php if (!empty($hasRecoveryCodes)): ?>
                    <p class="text-muted small mb-2">
                        Use a one-time recovery code instead.
                        <?php if (isset($remainingRecoveryCodes)): ?>
                            <?= esc((int)$remainingRecoveryCodes . ' code(s) remaining.') ?>
                        <?php endif; ?>
                    </p>
                    <form action="<?= site_url('auth/2fa/recovery') ?>" method="post" class="text-left">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="recovery_code">Recovery Code</label>
                            <input id="recovery_code" name="recovery_code" type="text" maxlength="32" class="form-control" autocomplete="one-time-code" required>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary btn-block">Use Recovery Code</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">
                        No recovery codes are set for this account. After login, go to My Profile to generate a recovery code set.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
