<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if ($msg = session('error')): ?>
    <div class="alert alert-danger"><?= esc($msg) ?></div>
<?php endif; ?>
<?php if ($msg = session('success')): ?>
    <div class="alert alert-success"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="row no-gutters">
            <div class="col-12 col-lg-7 mb-4 pr-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3"><?= esc(lang('Entrant.login_title')) ?></h1>
                        <p><?= lang('Entrant.login_welcome') ?></p>
                        <p class="text-muted"><?= esc(lang('Entrant.login_subtitle')) ?></p>

                        <form id="loginForm" action="<?= site_url('auth/login') ?>" method="post" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label for="login_email_address"><?= esc(lang('Entrant.email_address')) ?></label>
                                <input id="login_email_address" name="email" type="email" maxlength="100" class="form-control" value="<?= esc(old('email')) ?>" required>
                                <div class="invalid-feedback"><?= esc(lang('Entrant.valid_email_required')) ?></div>
                            </div>
                            <div class="form-group">
                                <label for="login_password"><?= esc(lang('Entrant.password')) ?></label>
                                <input id="login_password" name="password" type="password" maxlength="128" class="form-control" autocomplete="current-password" required>
                                <div class="invalid-feedback"><?= esc(lang('Entrant.password_required')) ?></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block"><?= esc(lang('Entrant.login')) ?></button>
                        </form>

                        <p class="mt-3 mb-0"><a href="<?= site_url('auth/forgot') ?>"><?= esc(lang('Entrant.forgot_password')) ?></a></p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5 mb-4 pl-lg-3">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3"><?= esc(lang('Entrant.create_account')) ?></h2>
                        <p class="text-muted"><?= esc(lang('Entrant.create_account_intro')) ?></p>

                        <form id="registerForm" action="<?= site_url('auth/register') ?>" method="post" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label for="reg_email"><?= esc(lang('Entrant.email_address')) ?></label>
                                <input id="reg_email" name="email" type="email" maxlength="100" class="form-control" required>
                                <div class="invalid-feedback"><?= esc(lang('Entrant.valid_email_required')) ?></div>
                            </div>
                            <div class="form-group">
                                <label for="reg_password"><?= esc(lang('Entrant.password')) ?></label>
                                <input id="reg_password" name="password" type="password" maxlength="128" class="form-control" autocomplete="new-password" required>
                                <div class="invalid-feedback"><?= esc(lang('Entrant.password_requirements_hint')) ?></div>
                                <div id="signupPasswordRules" class="small mt-2 d-none">
                                    <div data-rule="length" class="text-danger"><?= esc(lang('Entrant.password_rule_length')) ?></div>
                                    <div data-rule="upper" class="text-danger"><?= esc(lang('Entrant.password_rule_upper')) ?></div>
                                    <div data-rule="lower" class="text-danger"><?= esc(lang('Entrant.password_rule_lower')) ?></div>
                                    <div data-rule="number" class="text-danger"><?= esc(lang('Entrant.password_rule_number')) ?></div>
                                    <div data-rule="symbol" class="text-danger"><?= esc(lang('Entrant.password_rule_symbol')) ?></div>
                                </div>
                                <div id="signupPasswordStatus" class="small text-danger mt-1 d-none"><?= esc(lang('Entrant.password_strength_needs_work')) ?></div>
                            </div>
                            <div class="form-group">
                                <label for="reg_confirm_password"><?= esc(lang('Entrant.confirm_password')) ?></label>
                                <input id="reg_confirm_password" name="confirm_password" type="password" maxlength="128" class="form-control" autocomplete="new-password" required>
                                <div class="invalid-feedback"><?= esc(lang('Entrant.confirm_password')) ?></div>
                                <div id="signupConfirmStatus" class="small mt-1 d-none"></div>
                            </div>
                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="<?= esc($siteKey) ?>"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block"><?= esc(lang('Entrant.create_account_button')) ?></button>
                        </form>
                    </div>
                </div>
            </div>
</div>

<script>
    (function () {
        var form = document.getElementById('registerForm');
        var loginForm = document.getElementById('loginForm');
        var loginEmail = document.getElementById('login_email_address');
        var regEmail = document.getElementById('reg_email');
        var passwordInput = document.getElementById('reg_password');
        var confirmInput = document.getElementById('reg_confirm_password');
        var rules = document.getElementById('signupPasswordRules');
        var status = document.getElementById('signupPasswordStatus');
        var confirmStatus = document.getElementById('signupConfirmStatus');
        if (!form || !loginForm || !loginEmail || !regEmail || !passwordInput || !confirmInput || !rules || !status || !confirmStatus) {
            return;
        }

        var i18n = {
            emailRequired: <?= json_encode(lang('Entrant.email_required')) ?>,
            emailInvalid: <?= json_encode(lang('Entrant.valid_email_required')) ?>,
            passwordWeak: <?= json_encode(lang('Entrant.password_requirements_hint')) ?>,
            passwordStrong: <?= json_encode(lang('Entrant.password_strength_strong')) ?>,
            passwordNeedsWork: <?= json_encode(lang('Entrant.password_strength_needs_work')) ?>,
            confirmPrompt: <?= json_encode(lang('Entrant.confirm_password_prompt')) ?>,
            confirmMatch: <?= json_encode(lang('Entrant.passwords_match')) ?>,
            confirmNoMatch: <?= json_encode(lang('Entrant.passwords_do_not_match_with_period')) ?>
        };

        var normalizeEmailInput = function (input) {
            input.value = (input.value || '').trim();
        };

        var setEmailValidity = function (input) {
            normalizeEmailInput(input);
            var value = input.value || '';
            if (value === '') {
                input.setCustomValidity(i18n.emailRequired);
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                input.setCustomValidity(i18n.emailInvalid);
                return;
            }
            input.setCustomValidity('');
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
            var allPassed = true;

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

            if (value === '') {
                allPassed = false;
            }

            passwordInput.setCustomValidity(allPassed ? '' : i18n.passwordWeak);

            if (allPassed) {
                status.textContent = i18n.passwordStrong;
                status.className = 'small text-success mt-1';
            } else {
                status.textContent = i18n.passwordNeedsWork;
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

        passwordInput.addEventListener('input', updateIndicator);
        passwordInput.addEventListener('input', updateConfirmStatus);
        confirmInput.addEventListener('input', updateConfirmStatus);
        loginEmail.addEventListener('input', function () { setEmailValidity(loginEmail); });
        regEmail.addEventListener('input', function () { setEmailValidity(regEmail); });
        loginEmail.addEventListener('blur', function () { setEmailValidity(loginEmail); });
        regEmail.addEventListener('blur', function () { setEmailValidity(regEmail); });
        passwordInput.addEventListener('focus', updateVisibility);
        passwordInput.addEventListener('blur', updateVisibility);
        confirmInput.addEventListener('focus', updateVisibility);
        confirmInput.addEventListener('blur', updateVisibility);
        loginForm.addEventListener('submit', function (event) {
            setEmailValidity(loginEmail);
            if (!loginForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            loginForm.classList.add('was-validated');
        });
        form.addEventListener('submit', function (event) {
            setEmailValidity(regEmail);
            updateIndicator();
            updateConfirmStatus();
            updateVisibility();
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        setEmailValidity(loginEmail);
        setEmailValidity(regEmail);
        updateIndicator();
        updateConfirmStatus();
        updateVisibility();
    })();
</script>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?= $this->endSection() ?>
