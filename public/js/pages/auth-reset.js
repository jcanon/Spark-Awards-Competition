(function () {
    if (!window.SparkPasswordPolicy) {
        return;
    }

    var i18n = {
        passwordWeak: 'Please enter a password that meets all requirements.',
        passwordStrong: 'Strong password.',
        passwordNeedsWork: 'Password does not meet all requirements yet.',
        confirmPrompt: 'Confirm your new password.',
        confirmMatch: 'Passwords match.',
        confirmNoMatch: 'Passwords do not match.'
    };

    var policy = window.SparkPasswordPolicy.init({
        formId: 'passwordResetForm',
        passwordId: 'password',
        confirmId: 'password_confirm',
        rulesId: 'passwordRules',
        statusId: 'passwordStatus',
        confirmStatusId: 'confirmStatus',
        mode: 'required',
        rulesEmptyClass: 'text-danger',
        i18n: i18n
    });
    if (!policy) {
        return;
    }

    policy.form.addEventListener('submit', function (event) {
        policy.updateIndicator();
        policy.updateConfirmStatus();
        policy.updateVisibility();
        if (!policy.form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        policy.form.classList.add('was-validated');
    });
})();
