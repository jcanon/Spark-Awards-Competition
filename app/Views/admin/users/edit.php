<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .status-pill .medal-icon {
        font-size: 0.82rem;
    }

    .status-draft {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #374151;
    }

    .status-entrant {
        background: #e8f3ff;
        border-color: #bcd8ff;
        color: #0f4c81;
    }

    .status-finalist {
        background: #fff8e5;
        border-color: #ffd67a;
        color: #7a4a00;
    }

    .status-winner {
        background: #f8f4ff;
        border-color: #d9c7ff;
        color: #4b2b8a;
    }

    .winner-platinum {
        background: #f4f7fb;
        border-color: #c9d4e5;
        color: #2f425b;
    }

    .winner-gold {
        background: #fff6de;
        border-color: #f2ce6a;
        color: #7d5600;
    }

    .winner-silver {
        background: #f4f6f8;
        border-color: #cfd5dd;
        color: #4b5563;
    }

    .winner-bronze {
        background: #f8eee7;
        border-color: #d8ae8e;
        color: #764a2f;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
        <div>
            <?php if (!empty($canImpersonate)): ?>
                <form action="<?= site_url('admin/users/impersonate/' . rawurlencode((string)$row['user_id'])) ?>" method="post" class="d-inline" onsubmit="return confirm('Impersonate this user now? You will switch into their account.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-info">Impersonate User</button>
                </form>
            <?php endif; ?>
            <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/users') ?>">Back to Users</a>
        </div>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <form action="<?= site_url('admin/users/update/' . rawurlencode((string)$row['user_id'])) ?>" method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Login & Role</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Email</label><input type="email" name="email_address" class="form-control" maxlength="100" required value="<?= esc((string)$row['email_address']) ?>"></div>
                    <div class="col-md-4 form-group">
                        <label for="admin_user_password">New Password</label>
                        <input id="admin_user_password" type="password" name="password" class="form-control" maxlength="128">
                        <div id="adminUserPasswordRules" class="small mt-2 d-none">
                            <div data-rule="length" class="text-danger">At least 8 characters</div>
                            <div data-rule="upper" class="text-danger">At least 1 uppercase letter</div>
                            <div data-rule="lower" class="text-danger">At least 1 lowercase letter</div>
                            <div data-rule="number" class="text-danger">At least 1 number</div>
                            <div data-rule="symbol" class="text-danger">At least 1 symbol</div>
                        </div>
                        <div id="adminUserPasswordStatus" class="small text-danger mt-1 d-none">Password does not meet all requirements yet.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="admin_user_confirm_password">Confirm Password</label>
                        <input id="admin_user_confirm_password" type="password" name="confirm_password" class="form-control" maxlength="128">
                        <div id="adminUserConfirmStatus" class="small mt-1 d-none"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Admin</label>
                        <?php if (!empty($canManageElevatedRoles)): ?>
                            <select id="is_admin" name="is_admin" class="form-control">
                                <option value="No" <?= ($row['is_admin'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                <option value="Yes" <?= ($row['is_admin'] ?? 'No') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="is_admin" value="No">
                            <select id="is_admin" class="form-control" disabled>
                                <option value="No" selected>No</option>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Editor</label>
                        <?php if (!empty($canManageElevatedRoles)): ?>
                            <select id="is_editor" name="is_editor" class="form-control">
                                <option value="No" <?= ($row['is_editor'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                <option value="Yes" <?= ($row['is_editor'] ?? 'No') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="is_editor" value="No">
                            <select id="is_editor" class="form-control" disabled>
                                <option value="No" selected>No</option>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 form-group"><label>Judge</label><select name="is_judge" class="form-control"><option value="No" <?= ($row['is_judge'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option><option value="Yes" <?= ($row['is_judge'] ?? 'No') === 'Yes' ? 'selected' : '' ?>>Yes</option></select></div>
                    <div class="col-md-3 form-group"><label>Account Active</label><select name="account_active" class="form-control"><option value="Yes" <?= ($row['account_active'] ?? 'Yes') === 'Yes' ? 'selected' : '' ?>>Yes</option><option value="No" <?= ($row['account_active'] ?? 'Yes') === 'No' ? 'selected' : '' ?>>No</option></select></div>
                </div>
                <?php if (empty($canManageElevatedRoles)): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <small class="text-muted">Only Admins can assign Admin or Editor roles. Editors may update regular users and judges.</small>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-12">
                        <small id="role_conflict_help" class="text-danger d-none">A user cannot be both Admin and Editor. Selecting one will set the other to No.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Profile</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Title</label><input type="text" name="title" class="form-control" maxlength="100" value="<?= esc((string)($row['title'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>First Name</label><input type="text" name="first_name" class="form-control" maxlength="100" value="<?= esc((string)($row['first_name'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>Last Name</label><input type="text" name="last_name" class="form-control" maxlength="100" value="<?= esc((string)($row['last_name'] ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>Website</label><input type="text" name="website" class="form-control" maxlength="100" value="<?= esc((string)($row['website'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>Phone</label><input type="text" name="phone" class="form-control" maxlength="25" value="<?= esc((string)($row['phone'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>Mobile</label><input type="text" name="mobile" class="form-control" maxlength="25" value="<?= esc((string)($row['mobile'] ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>User Type</label><select name="user_type_id" class="form-control"><?php foreach ($userTypes as $ut): ?><option value="<?= (int)$ut['user_type_id'] ?>" <?= (string)$row['user_type_id'] === (string)$ut['user_type_id'] ? 'selected' : '' ?>><?= esc($ut['user_type_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 form-group"><label>Company</label><input type="text" name="company_name" class="form-control" maxlength="100" value="<?= esc((string)($row['company_name'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>Address 1</label><input type="text" name="address1" class="form-control" maxlength="100" value="<?= esc((string)($row['address1'] ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>Address 2</label><input type="text" name="address2" class="form-control" maxlength="100" value="<?= esc((string)($row['address2'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>City</label><input type="text" name="city" class="form-control" maxlength="100" value="<?= esc((string)($row['city'] ?? '')) ?>"></div>
                    <div class="col-md-2 form-group"><label>State / Province</label><select name="state" class="form-control"><option value="">-</option><?php foreach ($states as $st): ?><option value="<?= esc($st['scode']) ?>" <?= (string)($row['state'] ?? '') === (string)$st['scode'] ? 'selected' : '' ?>><?= esc($st['state']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2 form-group"><label>Postal Code</label><input type="text" name="zipcode" class="form-control" maxlength="25" value="<?= esc((string)($row['zipcode'] ?? '')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>Country</label><select name="country" class="form-control"><option value="">-</option><?php foreach ($countries as $ct): ?><option value="<?= esc($ct['ccode']) ?>" <?= (string)($row['country'] ?? '') === (string)$ct['ccode'] ? 'selected' : '' ?>><?= esc($ct['country']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 form-group"><label>How did you find us?</label><input type="text" name="how_did_you_find_us" class="form-control" maxlength="100" value="<?= esc((string)($row['how_did_you_find_us'] ?? '')) ?>"></div>
                    <div class="col-md-4 form-group"><label>How did you find us (Other)</label><input type="text" name="how_did_you_find_us_other" class="form-control" maxlength="100" value="<?= esc((string)($row['how_did_you_find_us_other'] ?? '')) ?>"></div>
                </div>
                <div class="form-group mb-0"><label>Internal Notes</label><textarea name="internal_notes" class="form-control" rows="4"><?= esc((string)($row['internal_notes'] ?? '')) ?></textarea></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save User</button>
        <a class="btn btn-secondary" href="<?= site_url('admin/users') ?>">Back to Users</a>
    </form>

    <div class="card mb-4 mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">User Submissions</h6>
            <span class="small text-muted"><?= (int)count($userSubmissions ?? []) ?> total</span>
        </div>
        <div class="card-body p-3">
            <?php if (!empty($userSubmissions)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 datatable">
                        <thead>
                            <tr>
                                <th>Submission</th>
                                <th>Competition Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Phase 1</th>
                                <th>Phase 2</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($userSubmissions ?? []) as $sub): ?>
                            <?php
                                $entryId = (string)($sub['entry_id'] ?? '');
                                $winnerLabel = trim((string)($sub['winner_level_name'] ?? ''));
                                $isMarkedNonFinalist = strtolower(trim((string)($sub['entry_non_finalist'] ?? 'No'))) === 'yes';
                                $statusLabel = trim((string)($sub['entry_status'] ?? ''));
                                $statusClass = 'status-draft';
                                $medalIconClass = '';

                                if ($statusLabel === 'Entrant') {
                                    $statusClass = 'status-entrant';
                                } elseif ($statusLabel === 'Finalist') {
                                    $statusClass = 'status-finalist';
                                } elseif ($statusLabel === 'Winner') {
                                    $statusClass = 'status-winner';
                                    if ($winnerLabel !== '') {
                                        $statusLabel .= ': ' . $winnerLabel;
                                        if (strcasecmp($winnerLabel, 'Platinum') === 0) {
                                            $statusClass = 'winner-platinum';
                                            $medalIconClass = 'fas fa-medal';
                                        } elseif (strcasecmp($winnerLabel, 'Gold') === 0) {
                                            $statusClass = 'winner-gold';
                                            $medalIconClass = 'fas fa-medal';
                                        } elseif (strcasecmp($winnerLabel, 'Silver') === 0) {
                                            $statusClass = 'winner-silver';
                                            $medalIconClass = 'fas fa-medal';
                                        } elseif (strcasecmp($winnerLabel, 'Bronze') === 0) {
                                            $statusClass = 'winner-bronze';
                                            $medalIconClass = 'fas fa-medal';
                                        }
                                    }
                                }

                                if ($isMarkedNonFinalist) {
                                    $statusLabel .= ' (Non-Finalist)';
                                }
                            ?>
                            <tr>
                                <td>
                                    <a class="font-weight-bold" href="<?= site_url('admin/submissions/edit/' . rawurlencode($entryId)) ?>">
                                        <?= esc((string)($sub['design_name'] ?? '(Untitled Submission)')) ?>
                                    </a>
                                </td>
                                <td><?= esc((string)($sub['comp_year'] ?? '')) ?></td>
                                <td><?= esc((string)($sub['comp_type_name'] ?? '')) ?></td>
                                <td>
                                    <span class="status-pill <?= esc($statusClass) ?>">
                                        <?php if ($medalIconClass !== ''): ?>
                                            <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                        <?php endif; ?>
                                        <?= esc($statusLabel) ?>
                                    </span>
                                    <?php if ((int)($sub['shortlist'] ?? 0) === 1): ?>
                                        <span class="badge badge-info ml-1">Shortlist</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string)($sub['phase_1_payment'] ?? '')) ?></td>
                                <td><?= esc((string)($sub['phase_2_payment'] ?? '')) ?></td>
                                <td><?= esc((string)($sub['date_created'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-3">
                    <p class="mb-0 text-muted">No submissions found for this user.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (($row['is_judge'] ?? 'No') === 'Yes'): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Judging Activity</h6>
                <span class="small text-muted"><?= (int)count($judgeActivity ?? []) ?> competition/phase records</span>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($judgeActivity)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>Competition Year</th>
                                    <th>Competition Type</th>
                                    <th>Phase</th>
                                    <th>Submitted Scores</th>
                                    <th>Last Judged</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach (($judgeActivity ?? []) as $activity): ?>
                                <?php $phaseLabel = (string)($activity['entry_phase'] ?? ''); ?>
                                <tr>
                                    <td><?= esc((string)($activity['comp_year'] ?? '')) ?></td>
                                    <td><?= esc((string)($activity['comp_type_name'] ?? '')) ?></td>
                                    <td><?= esc($phaseLabel === 'AllSpark' ? 'AllSpark' : ('Phase ' . $phaseLabel)) ?></td>
                                    <td><?= (int)($activity['judging_count'] ?? 0) ?></td>
                                    <td><?= esc(format_datetime_ui((string)($activity['last_judged'] ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-3">
                        <p class="mb-0 text-muted">This judge has not submitted any judging scores yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
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

        var passwordInput = document.getElementById('admin_user_password');
        var confirmInput = document.getElementById('admin_user_confirm_password');
        var rules = document.getElementById('adminUserPasswordRules');
        var status = document.getElementById('adminUserPasswordStatus');
        var confirmStatus = document.getElementById('adminUserConfirmStatus');
        if (!passwordInput || !confirmInput || !rules || !status || !confirmStatus) {
            return;
        }

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
            if (value === '') {
                Object.keys(ruleEls).forEach(function (key) {
                    var el = ruleEls[key];
                    if (!el) {
                        return;
                    }
                    el.classList.remove('text-success', 'text-danger');
                    el.classList.add('text-danger');
                });
                status.textContent = 'Password does not meet all requirements yet.';
                status.className = 'small text-danger mt-1';
                passwordInput.setCustomValidity('');
                return;
            }

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

            passwordInput.setCustomValidity(allPassed ? '' : 'Password does not meet the required complexity.');
            if (allPassed) {
                status.textContent = 'Strong password.';
                status.className = 'small text-success mt-1';
            } else {
                status.textContent = 'Password does not meet all requirements yet.';
                status.className = 'small text-danger mt-1';
            }
        };

        var updateConfirmStatus = function () {
            var pwd = passwordInput.value || '';
            var cfm = confirmInput.value || '';
            if (pwd === '' && cfm === '') {
                confirmStatus.textContent = '';
                confirmStatus.className = 'small mt-1';
                confirmInput.setCustomValidity('');
                return;
            }

            if (cfm === '') {
                confirmStatus.textContent = 'Confirm the new password.';
                confirmStatus.className = 'small text-muted mt-1';
                confirmInput.setCustomValidity('');
                return;
            }

            if (pwd !== '' && cfm === pwd) {
                confirmStatus.textContent = 'Passwords match.';
                confirmStatus.className = 'small text-success mt-1';
                confirmInput.setCustomValidity('');
                return;
            }

            confirmStatus.textContent = 'Passwords do not match.';
            confirmStatus.className = 'small text-danger mt-1';
            confirmInput.setCustomValidity('Passwords do not match.');
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
