<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm border-left-warning">
            <div class="card-body p-4 text-center">
                <h1 class="h4 mb-3"><?= esc(lang('Entrant.two_factor_authentication')) ?></h1>

                <?php if ($msg = session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger text-left"><?= esc($msg) ?></div>
                <?php endif; ?>

                <p class="text-muted"><?= esc(lang('Entrant.two_factor_required_notice')) ?></p>

                <a href="<?= esc($duoUrl) ?>" class="btn btn-primary"><?= esc(lang('Entrant.continue_to_duo')) ?></a>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-secondary ml-2"><?= esc(lang('Entrant.cancel_and_log_out')) ?></a>

                <hr class="my-4">

                <h2 class="h6 mb-3"><?= esc(lang('Entrant.cant_access_duo')) ?></h2>
                <?php if (!empty($hasRecoveryCodes)): ?>
                    <p class="text-muted small mb-2">
                        <?= esc(lang('Entrant.use_recovery_code_instead')) ?>
                        <?php if (isset($remainingRecoveryCodes)): ?>
                            <?= esc(lang('Entrant.codes_remaining', [(int)$remainingRecoveryCodes])) ?>
                        <?php endif; ?>
                    </p>
                    <form action="<?= site_url('auth/2fa/recovery') ?>" method="post" class="text-left">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="recovery_code"><?= esc(lang('Entrant.recovery_code')) ?></label>
                            <input id="recovery_code" name="recovery_code" type="text" maxlength="32" class="form-control" autocomplete="one-time-code" required>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary btn-block"><?= esc(lang('Entrant.use_recovery_code')) ?></button>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">
                        <?= esc(lang('Entrant.no_recovery_codes_message')) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
