(function () {
    'use strict';

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

    if (!window.SparkPasswordPolicy) {
        return;
    }

    var i18n = {
        optionalKeepCurrent: 'Optional: leave blank to keep your current password.',
        currentPasswordRequired: 'Current password is required to change your password.',
        passwordWeak: 'Please enter a password that meets all requirements.',
        passwordStrong: 'Strong password.',
        passwordNeedsWork: 'Password does not meet all requirements yet.',
        enterNewPasswordFirst: 'Enter a new password first.',
        confirmPrompt: 'Confirm your new password.',
        confirmMatch: 'Passwords match.',
        confirmNoMatch: 'Passwords do not match.'
    };

    var policy = window.SparkPasswordPolicy.init({
        formId: 'profileForm',
        passwordId: 'password',
        confirmId: 'confirm_password',
        currentId: 'current_password',
        rulesId: 'profilePasswordRules',
        statusId: 'profilePasswordStatus',
        confirmStatusId: 'profileConfirmStatus',
        mode: 'optional',
        rulesEmptyClass: 'text-muted',
        requireCurrentForChange: true,
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
