<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

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

    <?= view('partials/flash') ?>
    <?php
        $oldOrRow = static function (string $key, string $default = '') use ($row): string {
            $old = old($key);
            if ($old !== null) {
                return (string)$old;
            }
            return (string)($row[$key] ?? $default);
        };
    ?>

    <form id="adminUserEditForm" action="<?= site_url('admin/users/update/' . rawurlencode((string)$row['user_id'])) ?>" method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Login & Role</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="email_address">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                            </div>
                            <input id="email_address" type="email" name="email_address" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('email_address')) ?>">
                        </div>
                        <div class="invalid-feedback">A valid, unique email address is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="admin_user_password">New Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            </div>
                            <input id="admin_user_password" type="password" name="password" class="form-control" maxlength="128">
                        </div>
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
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            </div>
                            <input id="admin_user_confirm_password" type="password" name="confirm_password" class="form-control" maxlength="128">
                        </div>
                        <div id="adminUserConfirmStatus" class="small mt-1 d-none"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Admin</label>
                        <?php if (!empty($canManageElevatedRoles)): ?>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                                </div>
                                <select id="is_admin" name="is_admin" class="form-control">
                                    <option value="No" <?= ($row['is_admin'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                    <option value="Yes" <?= ($row['is_admin'] ?? 'No') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="is_admin" value="No">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                                </div>
                                <select id="is_admin" class="form-control" disabled>
                                    <option value="No" selected>No</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Editor</label>
                        <?php if (!empty($canManageElevatedRoles)): ?>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user-edit" aria-hidden="true"></i></span>
                                </div>
                                <select id="is_editor" name="is_editor" class="form-control">
                                    <option value="No" <?= ($row['is_editor'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                    <option value="Yes" <?= ($row['is_editor'] ?? 'No') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="is_editor" value="No">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user-edit" aria-hidden="true"></i></span>
                                </div>
                                <select id="is_editor" class="form-control" disabled>
                                    <option value="No" selected>No</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Judge</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-balance-scale" aria-hidden="true"></i></span>
                            </div>
                            <select name="is_judge" class="form-control">
                                <option value="No" <?= ($row['is_judge'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                <option value="Yes" <?= ($row['is_judge'] ?? 'No') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Account Active</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                            </div>
                            <select name="account_active" class="form-control">
                                <option value="Yes" <?= ($row['account_active'] ?? 'Yes') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= ($row['account_active'] ?? 'Yes') === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
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
                    <div class="col-md-4 form-group"><label for="title">Title</label><input id="title" type="text" name="title" class="form-control" maxlength="100" value="<?= esc($oldOrRow('title')) ?>"></div>
                    <div class="col-md-4 form-group">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input id="first_name" type="text" name="first_name" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('first_name')) ?>">
                        <div class="invalid-feedback">First name is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input id="last_name" type="text" name="last_name" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('last_name')) ?>">
                        <div class="invalid-feedback">Last name is required.</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="website">Website</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-globe" aria-hidden="true"></i></span>
                            </div>
                            <input id="website" type="text" name="website" class="form-control" maxlength="100" value="<?= esc($oldOrRow('website')) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="phone">Telephone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-phone" aria-hidden="true"></i></span>
                            </div>
                            <input id="phone" type="text" name="phone" class="form-control" maxlength="25" required value="<?= esc($oldOrRow('phone')) ?>">
                        </div>
                        <div class="invalid-feedback">Telephone is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="mobile">Mobile</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-mobile-alt" aria-hidden="true"></i></span>
                            </div>
                            <input id="mobile" type="text" name="mobile" class="form-control" maxlength="25" value="<?= esc($oldOrRow('mobile')) ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="user_type_id">User Type <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-users" aria-hidden="true"></i></span>
                            </div>
                            <select id="user_type_id" name="user_type_id" class="form-control" required>
                                <option value=""></option>
                                <?php foreach ($userTypes as $ut): ?>
                                    <option value="<?= (int)$ut['user_type_id'] ?>" <?= $oldOrRow('user_type_id') === (string)$ut['user_type_id'] ? 'selected' : '' ?>><?= esc($ut['user_type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="invalid-feedback">Entrant type is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="company_name">Company <span class="text-danger">*</span></label>
                        <input id="company_name" type="text" name="company_name" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('company_name')) ?>">
                        <div class="invalid-feedback">Organization is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="address1">Address 1 <span class="text-danger">*</span></label>
                        <input id="address1" type="text" name="address1" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('address1')) ?>">
                        <div class="invalid-feedback">Street Address 1 is required.</div>
                    </div>
                </div>
                <div class="row">
                    <?php
                        $selectedState = $oldOrRow('state');
                        if ($selectedState === 'None') {
                            $selectedState = 'NA';
                        }
                    ?>
                    <div class="col-md-4 form-group"><label for="address2">Address 2</label><input id="address2" type="text" name="address2" class="form-control" maxlength="100" value="<?= esc($oldOrRow('address2')) ?>"></div>
                    <div class="col-md-4 form-group">
                        <label for="city">City <span class="text-danger">*</span></label>
                        <input id="city" type="text" name="city" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('city')) ?>">
                        <div class="invalid-feedback">City is required.</div>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="state">State / Province <span class="text-danger">*</span></label>
                        <select id="state" name="state" class="form-control" required>
                            <option value=""></option>
                            <?php foreach ($states as $idx => $st): ?>
                                <option value="<?= esc($st['scode']) ?>" <?= $selectedState === (string)$st['scode'] ? 'selected' : '' ?>><?= esc($st['state']) ?></option>
                                <?php if ($idx === 0 && (string)$st['scode'] === 'NA'): ?>
                                    <option value="" disabled>--------------------</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">State or Province is required.</div>
                    </div>
                    <div class="col-md-2 form-group"><label for="zipcode">Postal Code</label><input id="zipcode" type="text" name="zipcode" class="form-control" maxlength="25" value="<?= esc($oldOrRow('zipcode')) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="country">Country <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-flag" aria-hidden="true"></i></span>
                            </div>
                            <select id="country" name="country" class="form-control" required>
                                <option value=""></option>
                                <?php foreach ($countries as $ct): ?>
                                    <option value="<?= esc($ct['ccode']) ?>" <?= $oldOrRow('country') === (string)$ct['ccode'] ? 'selected' : '' ?>><?= esc($ct['country']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="invalid-feedback">Country is required.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="how_did_you_find_us">How did you find us? <span class="text-danger">*</span></label>
                        <input id="how_did_you_find_us" type="text" name="how_did_you_find_us" class="form-control" maxlength="100" required value="<?= esc($oldOrRow('how_did_you_find_us')) ?>">
                        <div class="invalid-feedback">Please select or enter how you found us.</div>
                    </div>
                    <div class="col-md-4 form-group"><label for="how_did_you_find_us_other">How did you find us (Other)</label><input id="how_did_you_find_us_other" type="text" name="how_did_you_find_us_other" class="form-control" maxlength="100" value="<?= esc($oldOrRow('how_did_you_find_us_other')) ?>"></div>
                </div>
                <div class="form-group mb-0"><label for="internal_notes">Internal Notes</label><textarea id="internal_notes" name="internal_notes" class="form-control" rows="4"><?= esc($oldOrRow('internal_notes')) ?></textarea></div>
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
                                $isMarkedNonFinalist = strtolower(trim((string)($sub['entry_non_finalist'] ?? 'No'))) === 'yes';
                                [$statusLabel, $statusClass, $medalIconClass] = entry_status_pill($sub);

                                if ($isMarkedNonFinalist) {
                                    $statusLabel .= ' (Non-Finalist)';
                                }
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('admin/submissions/edit/' . rawurlencode($entryId)) ?>">
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

<script src="/js/utils/password-policy.js"></script>
<script src="/js/pages/admin-users-edit.js"></script>

<?= $this->endSection() ?>

