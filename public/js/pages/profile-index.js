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
        i18n: window.sparkProfileI18n || {}
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
