(function () {
    var registerForm = document.getElementById('registerForm');
    var loginForm = document.getElementById('loginForm');
    var loginEmail = document.getElementById('login_email_address');
    var regEmail = document.getElementById('reg_email');
    if (!registerForm || !loginForm || !loginEmail || !regEmail || !window.SparkPasswordPolicy) {
        return;
    }

    var i18n = window.sparkAuthLoginI18n || {};
    var policy = window.SparkPasswordPolicy.init({
        formId: 'registerForm',
        passwordId: 'reg_password',
        confirmId: 'reg_confirm_password',
        rulesId: 'signupPasswordRules',
        statusId: 'signupPasswordStatus',
        confirmStatusId: 'signupConfirmStatus',
        mode: 'required',
        rulesEmptyClass: 'text-danger',
        i18n: {
            passwordWeak: i18n.passwordWeak,
            passwordStrong: i18n.passwordStrong,
            passwordNeedsWork: i18n.passwordNeedsWork,
            confirmPrompt: i18n.confirmPrompt,
            confirmMatch: i18n.confirmMatch,
            confirmNoMatch: i18n.confirmNoMatch
        }
    });
    if (!policy) {
        return;
    }

    var normalizeEmailInput = function (input) {
        input.value = (input.value || '').trim();
    };

    var setEmailValidity = function (input) {
        normalizeEmailInput(input);
        var value = input.value || '';
        if (value === '') {
            input.setCustomValidity(i18n.emailRequired || 'Email address is required.');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            input.setCustomValidity(i18n.emailInvalid || 'Please enter a valid email address.');
            return;
        }
        input.setCustomValidity('');
    };

    loginEmail.addEventListener('input', function () { setEmailValidity(loginEmail); });
    regEmail.addEventListener('input', function () { setEmailValidity(regEmail); });
    loginEmail.addEventListener('blur', function () { setEmailValidity(loginEmail); });
    regEmail.addEventListener('blur', function () { setEmailValidity(regEmail); });

    loginForm.addEventListener('submit', function (event) {
        setEmailValidity(loginEmail);
        if (!loginForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        loginForm.classList.add('was-validated');
    });

    registerForm.addEventListener('submit', function (event) {
        setEmailValidity(regEmail);
        policy.updateIndicator();
        policy.updateConfirmStatus();
        policy.updateVisibility();
        if (!registerForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        registerForm.classList.add('was-validated');
    });

    setEmailValidity(loginEmail);
    setEmailValidity(regEmail);
})();
