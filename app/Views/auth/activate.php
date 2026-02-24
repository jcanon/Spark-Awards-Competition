<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3"><?= esc(lang('Entrant.account_activation')) ?></h1>

                <?php if ($status === 'doesNotExist'): ?>
                    <div class="alert alert-danger"><?= esc(lang('Entrant.activation_account_not_exist')) ?></div>
                <?php elseif ($status === 'alreadyActive'): ?>
                    <div class="alert alert-info"><?= esc(lang('Entrant.activation_already_active')) ?></div>
                <?php else: ?>
                    <div class="alert alert-success"><?= esc(lang('Entrant.activation_success')) ?></div>
                <?php endif; ?>

                <a href="<?= site_url('auth/login') ?>" class="btn btn-primary"><?= esc(lang('Entrant.back_to_login')) ?></a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
