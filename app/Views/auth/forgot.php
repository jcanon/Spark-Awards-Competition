<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3"><?= esc(lang('Entrant.forgot_login_title')) ?></h1>

                <?php if ($msg = session('error')): ?>
                    <div class="alert alert-danger"><?= esc($msg) ?></div>
                <?php endif; ?>

                <?php if ($sent ?? false): ?>
                    <div class="alert alert-success"><?= esc(lang('Entrant.forgot_sent')) ?></div>
                    <a href="<?= site_url('auth/login') ?>" class="btn btn-primary"><?= esc(lang('Entrant.back_to_login')) ?></a>
                <?php else: ?>
                    <p class="text-muted"><?= esc(lang('Entrant.forgot_intro')) ?></p>
                    <form action="<?= site_url('auth/forgot') ?>" method="post" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="forgot_email"><?= esc(lang('Entrant.email_address')) ?></label>
                            <input id="forgot_email" name="forgot_email" type="email" maxlength="100" class="form-control" value="<?= esc(old('forgot_email')) ?>" required>
                            <div class="invalid-feedback"><?= esc(lang('Entrant.valid_email_required')) ?></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= esc(lang('Entrant.send_request')) ?></button>
                        <a href="<?= site_url('auth/login') ?>" class="btn btn-link"><?= esc(lang('Entrant.back_to_login')) ?></a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?= $this->endSection() ?>
