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

    var adminSelect = document.getElementById('is_admin');
    var editorSelect = document.getElementById('is_editor');
    var roleConflictHelp = document.getElementById('role_conflict_help');
    if (adminSelect && editorSelect && roleConflictHelp) {
        var syncRoleState = function (source) {
            if (adminSelect.value === 'Yes' && editorSelect.value === 'Yes') {
                if (source === 'admin') {
                    editorSelect.value = 'No';
                } else if (source === 'editor') {
                    adminSelect.value = 'No';
                } else {
                    editorSelect.value = 'No';
                }
                roleConflictHelp.classList.remove('d-none');
            } else {
                roleConflictHelp.classList.add('d-none');
            }
        };

        adminSelect.addEventListener('change', function () {
            syncRoleState('admin');
        });
        editorSelect.addEventListener('change', function () {
            syncRoleState('editor');
        });
        syncRoleState('');
    }

    if (!window.SparkPasswordPolicy) {
        return;
    }

    window.SparkPasswordPolicy.init({
        formId: 'adminUserEditForm',
        passwordId: 'admin_user_password',
        confirmId: 'admin_user_confirm_password',
        rulesId: 'adminUserPasswordRules',
        statusId: 'adminUserPasswordStatus',
        confirmStatusId: 'adminUserConfirmStatus',
        mode: 'optional',
        rulesEmptyClass: 'text-danger',
        i18n: {
            optionalKeepCurrent: 'Password does not meet all requirements yet.',
            passwordWeak: 'Password does not meet the required complexity.',
            passwordStrong: 'Strong password.',
            passwordNeedsWork: 'Password does not meet all requirements yet.',
            confirmPrompt: 'Confirm the new password.',
            confirmMatch: 'Passwords match.',
            confirmNoMatch: 'Passwords do not match.'
        }
    });
})();
