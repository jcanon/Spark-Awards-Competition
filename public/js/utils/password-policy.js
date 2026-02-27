(function (window) {
    if (!window) {
        return;
    }

    var evaluatePassword = function (value) {
        return {
            length: value.length >= 8,
            upper: /[A-Z]/.test(value),
            lower: /[a-z]/.test(value),
            number: /[0-9]/.test(value),
            symbol: /[^A-Za-z0-9\s]/.test(value)
        };
    };

    var toggleVisibility = function (element, show) {
        if (!element) {
            return;
        }
        element.classList.toggle('d-none', !show);
    };

    var initPasswordPolicy = function (config) {
        var form = document.getElementById(config.formId || '');
        var passwordInput = document.getElementById(config.passwordId || '');
        var confirmInput = document.getElementById(config.confirmId || '');
        var rules = document.getElementById(config.rulesId || '');
        var status = document.getElementById(config.statusId || '');
        var confirmStatus = document.getElementById(config.confirmStatusId || '');
        var currentInput = config.currentId ? document.getElementById(config.currentId) : null;
        if (!form || !passwordInput || !confirmInput || !rules || !status || !confirmStatus) {
            return null;
        }

        var i18n = config.i18n || {};
        var mode = config.mode === 'optional' ? 'optional' : 'required';
        var rulesEmptyClass = config.rulesEmptyClass || 'text-danger';

        var ruleEls = {
            length: rules.querySelector('[data-rule="length"]'),
            upper: rules.querySelector('[data-rule="upper"]'),
            lower: rules.querySelector('[data-rule="lower"]'),
            number: rules.querySelector('[data-rule="number"]'),
            symbol: rules.querySelector('[data-rule="symbol"]')
        };

        var updateConfirmStatus = function () {
            var pwd = passwordInput.value || '';
            var cfm = confirmInput.value || '';

            if (mode === 'optional' && pwd === '' && cfm === '') {
                confirmInput.setCustomValidity('');
                confirmStatus.textContent = '';
                confirmStatus.className = 'small mt-1 d-none';
                return;
            }

            if (pwd === '' && cfm !== '' && i18n.enterNewPasswordFirst) {
                confirmStatus.textContent = i18n.enterNewPasswordFirst;
                confirmStatus.className = 'small text-danger mt-1';
                confirmInput.setCustomValidity(i18n.enterNewPasswordFirst);
                return;
            }

            if (cfm === '') {
                confirmStatus.textContent = i18n.confirmPrompt || 'Confirm your password.';
                confirmStatus.className = 'small text-muted mt-1';
                confirmInput.setCustomValidity('');
                return;
            }

            if (pwd !== '' && cfm === pwd) {
                confirmStatus.textContent = i18n.confirmMatch || 'Passwords match.';
                confirmStatus.className = 'small text-success mt-1';
                confirmInput.setCustomValidity('');
                return;
            }

            var mismatch = i18n.confirmNoMatch || 'Passwords do not match.';
            confirmStatus.textContent = mismatch;
            confirmStatus.className = 'small text-danger mt-1';
            confirmInput.setCustomValidity(mismatch);
        };

        var updateIndicator = function () {
            var value = passwordInput.value || '';

            if (mode === 'optional' && value === '') {
                if (currentInput) {
                    currentInput.setCustomValidity('');
                }
                Object.keys(ruleEls).forEach(function (key) {
                    var el = ruleEls[key];
                    if (!el) {
                        return;
                    }
                    el.classList.remove('text-success', 'text-danger', 'text-muted');
                    el.classList.add(rulesEmptyClass);
                });
                passwordInput.setCustomValidity('');
                status.textContent = i18n.optionalKeepCurrent || '';
                status.className = 'small text-muted mt-1';
                updateConfirmStatus();
                return;
            }

            if (config.requireCurrentForChange && currentInput) {
                if ((currentInput.value || '') === '') {
                    currentInput.setCustomValidity(i18n.currentPasswordRequired || 'Current password is required.');
                } else {
                    currentInput.setCustomValidity('');
                }
            }

            var checks = evaluatePassword(value);
            var allPassed = value !== '';
            Object.keys(ruleEls).forEach(function (key) {
                var el = ruleEls[key];
                if (!el) {
                    return;
                }
                var ok = checks[key];
                el.classList.remove('text-success', 'text-danger', 'text-muted');
                el.classList.add(ok ? 'text-success' : 'text-danger');
                if (!ok) {
                    allPassed = false;
                }
            });

            var weak = i18n.passwordWeak || 'Password does not meet requirements.';
            passwordInput.setCustomValidity(allPassed ? '' : weak);
            if (allPassed) {
                status.textContent = i18n.passwordStrong || 'Strong password.';
                status.className = 'small text-success mt-1';
            } else {
                status.textContent = i18n.passwordNeedsWork || weak;
                status.className = 'small text-danger mt-1';
            }
            updateConfirmStatus();
        };

        var updateVisibility = function () {
            var showRules = document.activeElement === passwordInput || (passwordInput.value || '') !== '';
            var showConfirm = document.activeElement === confirmInput || (confirmInput.value || '') !== '';
            toggleVisibility(rules, showRules);
            toggleVisibility(status, showRules);
            toggleVisibility(confirmStatus, showConfirm);
        };

        passwordInput.addEventListener('input', function () {
            updateIndicator();
            updateVisibility();
        });
        passwordInput.addEventListener('focus', updateVisibility);
        passwordInput.addEventListener('blur', updateVisibility);
        confirmInput.addEventListener('input', function () {
            updateConfirmStatus();
            updateVisibility();
        });
        confirmInput.addEventListener('focus', updateVisibility);
        confirmInput.addEventListener('blur', updateVisibility);

        if (currentInput) {
            currentInput.addEventListener('input', function () {
                updateIndicator();
            });
        }

        updateIndicator();
        updateConfirmStatus();
        updateVisibility();

        return {
            form: form,
            updateIndicator: updateIndicator,
            updateConfirmStatus: updateConfirmStatus,
            updateVisibility: updateVisibility
        };
    };

    window.SparkPasswordPolicy = {
        init: initPasswordPolicy
    };
})(window);
