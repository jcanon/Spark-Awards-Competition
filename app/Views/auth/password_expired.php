<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3"><?= esc(lang('Entrant.password_update_required')) ?></h1>
                <div class="alert alert-warning">
                    <?= esc(lang('Entrant.password_update_required_notice')) ?>
                </div>

                <?php if ($msg = session('error')): ?>
                    <div class="alert alert-danger"><?= esc($msg) ?></div>
                <?php endif; ?>

                <form id="passwordExpiredForm" action="<?= site_url('auth/password-expired') ?>" method="post" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="password"><?= esc(lang('Entrant.new_password')) ?></label>
                        <input id="password" name="password" type="password" maxlength="128" class="form-control" required>
                        <div class="invalid-feedback"><?= esc(lang('Entrant.password_requirements_hint')) ?></div>
                        <small class="form-text text-muted">
                            <?= esc(lang('Entrant.password_requirements_short')) ?>
                        </small>
                        <div id="passwordRules" class="small mt-2 d-none">
                            <div data-rule="length" class="text-danger"><?= esc(lang('Entrant.password_rule_length')) ?></div>
                            <div data-rule="upper" class="text-danger"><?= esc(lang('Entrant.password_rule_upper')) ?></div>
                            <div data-rule="lower" class="text-danger"><?= esc(lang('Entrant.password_rule_lower')) ?></div>
                            <div data-rule="number" class="text-danger"><?= esc(lang('Entrant.password_rule_number')) ?></div>
                            <div data-rule="symbol" class="text-danger"><?= esc(lang('Entrant.password_rule_symbol')) ?></div>
                        </div>
                        <div id="passwordStatus" class="small text-danger mt-1 d-none"><?= esc(lang('Entrant.password_strength_needs_work')) ?></div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm"><?= esc(lang('Entrant.confirm_new_password')) ?></label>
                        <input id="password_confirm" name="password_confirm" type="password" maxlength="128" class="form-control" required>
                        <div class="invalid-feedback"><?= esc(lang('Entrant.confirm_password_required')) ?></div>
                        <div id="confirmStatus" class="small mt-1 d-none"></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= esc(lang('Entrant.update_password')) ?></button>
                    <a href="<?= site_url('auth/logout') ?>" class="btn btn-link"><?= esc(lang('Entrant.log_out')) ?></a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var form = document.getElementById('passwordExpiredForm');
        var passwordInput = document.getElementById('password');
        var confirmInput = document.getElementById('password_confirm');
        var rules = document.getElementById('passwordRules');
        var status = document.getElementById('passwordStatus');
        var confirmStatus = document.getElementById('confirmStatus');
        if (!form || !passwordInput || !confirmInput || !rules || !status || !confirmStatus) {
            return;
        }

        var i18n = {
            weak: <?= json_encode(lang('Entrant.password_requirements_hint')) ?>,
            strong: <?= json_encode(lang('Entrant.password_strength_strong')) ?>,
            needsWork: <?= json_encode(lang('Entrant.password_strength_needs_work')) ?>,
            confirmPrompt: <?= json_encode(lang('Entrant.confirm_new_password_prompt')) ?>,
            confirmMatch: <?= json_encode(lang('Entrant.passwords_match')) ?>,
            confirmNoMatch: <?= json_encode(lang('Entrant.passwords_do_not_match_with_period')) ?>
        };

        var ruleEls = {
            length: rules.querySelector('[data-rule="length"]'),
            upper: rules.querySelector('[data-rule="upper"]'),
            lower: rules.querySelector('[data-rule="lower"]'),
            number: rules.querySelector('[data-rule="number"]'),
            symbol: rules.querySelector('[data-rule="symbol"]')
        };

        var evaluate = function (value) {
            return {
                length: value.length >= 8,
                upper: /[A-Z]/.test(value),
                lower: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                symbol: /[^A-Za-z0-9\s]/.test(value)
            };
        };

        var updateIndicator = function () {
            var value = passwordInput.value || '';
            var checks = evaluate(value);
            var allPassed = value !== '';

            Object.keys(ruleEls).forEach(function (key) {
                var el = ruleEls[key];
                if (!el) {
                    return;
                }
                var ok = checks[key];
                el.classList.remove('text-success', 'text-danger');
                el.classList.add(ok ? 'text-success' : 'text-danger');
                if (!ok) {
                    allPassed = false;
                }
            });

            passwordInput.setCustomValidity(allPassed ? '' : i18n.weak);
            if (allPassed) {
                status.textContent = i18n.strong;
                status.className = 'small text-success mt-1';
            } else {
                status.textContent = i18n.needsWork;
                status.className = 'small text-danger mt-1';
            }
        };

        var updateConfirmStatus = function () {
            var pwd = passwordInput.value || '';
            var cfm = confirmInput.value || '';

            if (cfm === '') {
                confirmStatus.textContent = i18n.confirmPrompt;
                confirmStatus.className = 'small text-muted mt-1';
                confirmInput.setCustomValidity('');
                return;
            }
            if (pwd !== '' && cfm === pwd) {
                confirmStatus.textContent = i18n.confirmMatch;
                confirmStatus.className = 'small text-success mt-1';
                confirmInput.setCustomValidity('');
                return;
            }
            confirmStatus.textContent = i18n.confirmNoMatch;
            confirmStatus.className = 'small text-danger mt-1';
            confirmInput.setCustomValidity(i18n.confirmNoMatch);
        };

        var updateVisibility = function () {
            var showRules = document.activeElement === passwordInput || (passwordInput.value || '') !== '';
            var showConfirm = document.activeElement === confirmInput || (confirmInput.value || '') !== '';
            rules.classList.toggle('d-none', !showRules);
            status.classList.toggle('d-none', !showRules);
            confirmStatus.classList.toggle('d-none', !showConfirm);
        };

        form.addEventListener('submit', function (event) {
            updateIndicator();
            updateConfirmStatus();
            updateVisibility();
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        passwordInput.addEventListener('input', updateIndicator);
        passwordInput.addEventListener('input', updateConfirmStatus);
        confirmInput.addEventListener('input', updateConfirmStatus);
        passwordInput.addEventListener('focus', updateVisibility);
        passwordInput.addEventListener('blur', updateVisibility);
        confirmInput.addEventListener('focus', updateVisibility);
        confirmInput.addEventListener('blur', updateVisibility);
        updateIndicator();
        updateConfirmStatus();
        updateVisibility();
    })();
</script>

<?= $this->endSection() ?>
