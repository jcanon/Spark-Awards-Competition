(function () {
    if (!window.SparkPasswordPolicy) {
        return;
    }

    var policy = window.SparkPasswordPolicy.init({
        formId: 'passwordExpiredForm',
        passwordId: 'password',
        confirmId: 'password_confirm',
        rulesId: 'passwordRules',
        statusId: 'passwordStatus',
        confirmStatusId: 'confirmStatus',
        mode: 'required',
        rulesEmptyClass: 'text-danger',
        i18n: window.sparkPasswordExpiredI18n || {}
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
