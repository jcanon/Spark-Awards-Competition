<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$isProfileComplete = method_exists($user, 'isProfileCompleted')
    ? $user->isProfileCompleted()
    : ((string)($user->profile_completed ?? 'No') === 'Yes');
?>

    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><?= esc(lang('Entrant.my_profile')) ?></h1>
        </div>

        <hr class="mb-4">

        <?php if (!$isProfileComplete): ?>
            <div class="alert alert-warning border-left-warning shadow-sm" role="alert">
                <h6 class="font-weight-bold mb-2"><?= esc(lang('Entrant.profile_completion_required_title')) ?></h6>
                <p class="mb-0">
                    <?= esc(lang('Entrant.profile_completion_required_body')) ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty(session()->getFlashdata('success'))): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <?php $newRecoveryCodes = session()->getFlashdata('recovery_codes'); ?>
        <?php if (!empty($newRecoveryCodes) && is_array($newRecoveryCodes)): ?>
            <div class="alert alert-warning border-left-warning shadow-sm">
                <h6 class="font-weight-bold mb-2"><?= esc(lang('Entrant.new_recovery_codes_save_now')) ?></h6>
                <p class="mb-2"><?= esc(lang('Entrant.new_recovery_codes_help')) ?></p>
                <div class="row">
                    <?php foreach ($newRecoveryCodes as $rc): ?>
                        <div class="col-md-3"><code><?= esc((string)$rc) ?></code></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty(session()->get('errors'))): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->get('errors') as $err): ?>
                        <li><?= is_array($err) ? esc(implode(', ', $err)) : esc($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($canManageRecoveryCodes)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.recovery_codes_title')) ?></h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <?php if (!empty($hasRecoveryCodes)): ?>
                            <?= lang('Entrant.recovery_codes_have', [(int)($remainingRecoveryCodes ?? 0)]) ?>
                        <?php else: ?>
                            <?= esc(lang('Entrant.recovery_codes_none')) ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-muted small">
                        <?= esc(lang('Entrant.recovery_codes_help')) ?>
                    </p>
                    <form action="<?= site_url('profile/recovery-codes/regenerate') ?>" method="post" class="form-inline">
                        <?= csrf_field() ?>
                        <div class="form-group mr-2 mb-2">
                            <label for="recovery_current_password" class="sr-only"><?= esc(lang('Entrant.current_password')) ?></label>
                            <input type="password" id="recovery_current_password" name="recovery_current_password" class="form-control" placeholder="<?= esc(lang('Entrant.current_password_placeholder')) ?>" maxlength="128" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary mb-2">
                            <?= !empty($hasRecoveryCodes) ? esc(lang('Entrant.regenerate_recovery_codes')) : esc(lang('Entrant.generate_recovery_codes')) ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <form id="profileForm" action="<?= base_url('profile/update') ?>" method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.login_information')) ?></h6>
                </div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label for="email_address" class="form-label"><?= esc(lang('Entrant.email_address')) ?> <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email_address" name="email_address" required maxlength="100" value="<?= esc((string)(old('email_address') ?? ($user->email_address ?? ''))) ?>">
                        <div class="invalid-feedback"><?= esc(lang('Entrant.valid_unique_email_required')) ?></div>
                    </div>
                    <div class="col-md-3">
                        <label for="current_password" class="form-label"><?= esc(lang('Entrant.current_password')) ?></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" maxlength="128" autocomplete="current-password">
                        <div class="invalid-feedback"><?= esc(lang('Entrant.current_password_required_to_change')) ?></div>
                    </div>
                    <div class="col-md-3">
                        <label for="password" class="form-label"><?= esc(lang('Entrant.password')) ?></label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password">
                        <div class="invalid-feedback"><?= esc(lang('Entrant.password_requirements_hint')) ?></div>
                        <div id="profilePasswordRules" class="small mt-2 d-none">
                            <div data-rule="length" class="text-muted"><?= esc(lang('Entrant.password_rule_length')) ?></div>
                            <div data-rule="upper" class="text-muted"><?= esc(lang('Entrant.password_rule_upper')) ?></div>
                            <div data-rule="lower" class="text-muted"><?= esc(lang('Entrant.password_rule_lower')) ?></div>
                            <div data-rule="number" class="text-muted"><?= esc(lang('Entrant.password_rule_number')) ?></div>
                            <div data-rule="symbol" class="text-muted"><?= esc(lang('Entrant.password_rule_symbol')) ?></div>
                        </div>
                        <div id="profilePasswordStatus" class="small text-muted mt-1 d-none"><?= esc(lang('Entrant.optional_keep_current_password')) ?></div>
                    </div>
                    <div class="col-md-3">
                        <label for="confirm_password" class="form-label"><?= esc(lang('Entrant.confirm_password')) ?></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                        <div class="invalid-feedback"><?= esc(lang('Entrant.confirm_password_required')) ?></div>
                        <div id="profileConfirmStatus" class="small mt-1 d-none"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.contact_information')) ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="title" class="form-label"><?= esc(lang('Entrant.title_label')) ?></label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="100" value="<?= old('title') ?? ($user->title ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="first_name" class="form-label"><?= esc(lang('Entrant.first_name')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="100" value="<?= old('first_name') ?? ($user->first_name ?? '') ?>">
                            <div class="invalid-feedback"><?= esc(lang('Entrant.first_name_required')) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label for="last_name" class="form-label"><?= esc(lang('Entrant.last_name')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="100" value="<?= old('last_name') ?? ($user->last_name ?? '') ?>">
                            <div class="invalid-feedback"><?= esc(lang('Entrant.last_name_required')) ?></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="website" class="form-label"><?= esc(lang('Entrant.website')) ?></label>
                            <input type="url" class="form-control" id="website" name="website" maxlength="100" value="<?= old('website') ?? ($user->website ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="phone" class="form-label"><?= esc(lang('Entrant.telephone')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" required maxlength="25" value="<?= old('phone') ?? ($user->phone ?? '') ?>">
                            <div class="invalid-feedback"><?= esc(lang('Entrant.telephone_required')) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label for="mobile" class="form-label"><?= esc(lang('Entrant.mobile')) ?></label>
                            <input type="text" class="form-control" id="mobile" name="mobile" maxlength="25" value="<?= old('mobile') ?? ($user->mobile ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Entrant Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="user_type_id" class="form-label"><?= esc(lang('Entrant.entrant_type')) ?> <span class="text-danger">*</span></label>
                            <select id="user_type_id" name="user_type_id" class="form-control" required>
                                <option value=""></option>
                                <?php foreach ($dropdowns['userTypes'] as $ut): ?>
                                    <option value="<?= $ut['user_type_id'] ?>" <?= (old('user_type_id') == $ut['user_type_id'] ? 'selected' : ((string)($user->user_type_id ?? '') === (string)$ut['user_type_id'] ? 'selected' : '')) ?>>
                                        <?= esc($ut['user_type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= esc(lang('Entrant.entrant_type_required')) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label for="company_name" class="form-label"><?= esc(lang('Entrant.organization')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required maxlength="100" value="<?= old('company_name') ?? ($user->company_name ?? '') ?>">
                            <div class="invalid-feedback"><?= esc(lang('Entrant.organization_required')) ?></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="address1" class="form-label"><?= esc(lang('Entrant.street_address_1')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address1" name="address1" required maxlength="100" value="<?= old('address1') ?? ($user->address1 ?? '') ?>">
                            <div class="invalid-feedback"><?= esc(lang('Entrant.street_address_1_required')) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label for="address2" class="form-label"><?= esc(lang('Entrant.street_address_2')) ?></label>
                            <input type="text" class="form-control" id="address2" name="address2" maxlength="100" value="<?= old('address2') ?? ($user->address2 ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="city" class="form-label"><?= esc(lang('Entrant.city')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" required maxlength="100" value="<?= old('city') ?? ($user->city ?? '') ?>">
                            <div class="invalid-feedback"><?= esc(lang('Entrant.city_required')) ?></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="state" class="form-label"><?= esc(lang('Entrant.state_or_province')) ?> <span class="text-danger">*</span></label>
                            <select id="state" name="state" class="form-control" required>
                                <option value=""></option>
                                <option value="None" <?= (old('state') == 'None' ? 'selected' : '') ?> ><?= esc(lang('Entrant.not_listed')) ?></option>
                                <?php foreach ($dropdowns['states'] as $st): ?>
                                    <option value="<?= $st['scode'] ?>" <?= (old('state') == $st['scode'] ? 'selected' : (($user->state ?? '') == $st['scode'] ? 'selected' : '')) ?>>
                                        <?= esc($st['state']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= esc(lang('Entrant.state_or_province_required')) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label for="zipcode" class="form-label"><?= esc(lang('Entrant.postcode')) ?></label>
                            <input type="text" class="form-control" id="zipcode" name="zipcode" maxlength="25" value="<?= old('zipcode') ?? ($user->zipcode ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="country" class="form-label"><?= esc(lang('Entrant.country')) ?> <span class="text-danger">*</span></label>
                            <select id="country" name="country" class="form-control" required>
                                <option value=""></option>
                                <option value="None" <?= (old('country') == 'None' ? 'selected' : '') ?>><?= esc(lang('Entrant.not_listed')) ?></option>
                                <option value="US" <?= (old('country') == 'US' ? 'selected' : (($user->country ?? '') === 'US' ? 'selected' : '')) ?>><?= esc(lang('Entrant.united_states')) ?></option>
                                <?php foreach ($dropdowns['countries'] as $ct): ?>
                                    <option value="<?= $ct['ccode'] ?>" <?= (old('country') == $ct['ccode'] ? 'selected' : (($user->country ?? '') == $ct['ccode'] ? 'selected' : '')) ?>>
                                        <?= esc($ct['country']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= esc(lang('Entrant.country_required')) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $newsletterOptions = [
                'CleanTech Design' => 'category_cleantech_design',
                'Digital Design' => 'category_digital_design',
                'Experience Design' => 'category_experience_design',
                'Graphic Design' => 'category_graphic_design',
                'Health & Medical Design' => 'category_health_medical_design',
                'Package Design' => 'category_package_design',
                'Product Design' => 'category_product_design',
                'Spark-E Design' => 'category_spark_e_design',
                'Spaces Design' => 'category_spaces_design',
                'Student Design' => 'category_student_design',
                'Transport Design' => 'category_transport_design',
                'Wear Design' => 'category_wear_design',
            ];

            $oldSelections = (array) old('email_newsletters');

            $storedSelections = [];
            $storedRaw = (string) ($user->email_newsletters ?? '');
            if ($storedRaw !== '') {
                $decoded = json_decode($storedRaw, true);
                if (is_array($decoded)) {
                    $storedSelections = $decoded;
                } else {
                    $csv = array_filter(array_map('trim', explode(',', $storedRaw)));
                    $storedSelections = $csv;
                }
            }

            $mkId = function (string $label): string {
                $id = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label));
                return trim($id, '_');
            };
            ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.additional_information')) ?></h6>
                </div>
                <div class="card-body">
                    <label for="how_did_you_find_us" class="form-label"><?= esc(lang('Entrant.how_find_us')) ?> <span class="text-danger">*</span></label>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <select id="how_did_you_find_us" name="how_did_you_find_us" class="form-control" required>
                                <option value=""></option>
                                <?php
                                $options = [
                                    'Colleague/Coworker' => 'find_us_colleague_coworker',
                                    'Friend' => 'find_us_friend',
                                    'LinkedIn Design Group' => 'find_us_linkedin',
                                    'Poster' => 'find_us_poster',
                                    'Publication/Media' => 'find_us_publication_media',
                                    'Search Engine' => 'find_us_search_engine',
                                    'Spark International' => 'find_us_spark_international',
                                    'Other' => 'other',
                                ];
                                foreach ($options as $opt => $optKey) {
                                    $selected = (old('how_did_you_find_us') == $opt) ? 'selected' : ((($user->how_did_you_find_us ?? '') === $opt) ? 'selected' : '');
                                    echo "<option value=\"" . esc($opt) . "\" $selected>" . esc(lang('Entrant.' . $optKey)) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback"><?= esc(lang('Entrant.how_find_us_select')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="how_did_you_find_us_other" name="how_did_you_find_us_other" maxlength="20" placeholder="<?= esc(lang('Entrant.other')) ?>" value="<?= old('how_did_you_find_us_other') ?? ($user->how_did_you_find_us_other ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label d-block mb-2"><?= esc(lang('Entrant.categories_interest')) ?></label>

                            <div class="row">
                                <?php foreach ($newsletterOptions as $label => $labelKey): ?>
                                    <?php
                                    $id = 'email_newsletters_' . $mkId($label);
                                    $isChecked = in_array($label, $oldSelections, true) || in_array($label, $storedSelections, true);
                                    ?>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="email_newsletters[]" id="<?= esc($id) ?>" value="<?= esc($label) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="<?= esc($id) ?>"><?= esc(lang('Entrant.' . $labelKey)) ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 mb-4">
                <button type="submit" class="btn btn-primary"><?= esc(lang('Entrant.update_profile')) ?></button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.id === 'profileForm') {
                            updateIndicator();
                            updateConfirmStatus();
                            updateVisibility();
                        }
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });

            var passwordInput = document.getElementById('password');
            var confirmInput = document.getElementById('confirm_password');
            var currentInput = document.getElementById('current_password');
            var rules = document.getElementById('profilePasswordRules');
            var status = document.getElementById('profilePasswordStatus');
            var confirmStatus = document.getElementById('profileConfirmStatus');
            if (!passwordInput || !confirmInput || !currentInput || !rules || !status || !confirmStatus) {
                return;
            }

            var ruleEls = {
                length: rules.querySelector('[data-rule="length"]'),
                upper: rules.querySelector('[data-rule="upper"]'),
                lower: rules.querySelector('[data-rule="lower"]'),
                number: rules.querySelector('[data-rule="number"]'),
                symbol: rules.querySelector('[data-rule="symbol"]')
            };
            var i18n = {
                optionalKeepCurrent: <?= json_encode(lang('Entrant.optional_keep_current_password')) ?>,
                currentPasswordRequired: <?= json_encode(lang('Entrant.current_password_required_to_change')) ?>,
                passwordWeak: <?= json_encode(lang('Entrant.password_requirements_hint')) ?>,
                passwordStrong: <?= json_encode(lang('Entrant.password_strength_strong')) ?>,
                passwordNeedsWork: <?= json_encode(lang('Entrant.password_strength_needs_work')) ?>,
                enterNewPasswordFirst: <?= json_encode(lang('Entrant.enter_new_password_first')) ?>,
                confirmNewPassword: <?= json_encode(lang('Entrant.confirm_new_password_prompt')) ?>,
                passwordsMatch: <?= json_encode(lang('Entrant.passwords_match')) ?>,
                passwordsNoMatch: <?= json_encode(lang('Entrant.passwords_do_not_match_with_period')) ?>
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
                    currentInput.setCustomValidity('');
                    Object.keys(ruleEls).forEach(function (key) {
                        var el = ruleEls[key];
                        if (!el) {
                            return;
                        }
                        el.classList.remove('text-success', 'text-danger');
                        el.classList.add('text-muted');
                    });
                    passwordInput.setCustomValidity('');
                    status.textContent = i18n.optionalKeepCurrent;
                    status.className = 'small text-muted mt-1';
                    updateConfirmStatus();
                    updateVisibility();
                    return;
                }

                if ((currentInput.value || '') === '') {
                    currentInput.setCustomValidity(i18n.currentPasswordRequired);
                } else {
                    currentInput.setCustomValidity('');
                }

                var checks = evaluate(value);
                var allPassed = true;
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

                passwordInput.setCustomValidity(allPassed ? '' : i18n.passwordWeak);

                if (allPassed) {
                    status.textContent = i18n.passwordStrong;
                    status.className = 'small text-success mt-1';
                } else {
                    status.textContent = i18n.passwordNeedsWork;
                    status.className = 'small text-danger mt-1';
                }
                updateConfirmStatus();
                updateVisibility();
            };

            var updateConfirmStatus = function () {
                var pwd = passwordInput.value || '';
                var cfm = confirmInput.value || '';

                if (pwd === '' && cfm === '') {
                    confirmInput.setCustomValidity('');
                    confirmStatus.textContent = '';
                    confirmStatus.className = 'small mt-1 d-none';
                    return;
                }

                if (pwd === '' && cfm !== '') {
                    confirmStatus.textContent = i18n.enterNewPasswordFirst;
                    confirmStatus.className = 'small text-danger mt-1';
                    confirmInput.setCustomValidity(i18n.enterNewPasswordFirst);
                    return;
                }

                if (cfm === '') {
                    confirmStatus.textContent = i18n.confirmNewPassword;
                    confirmStatus.className = 'small text-muted mt-1';
                    confirmInput.setCustomValidity('');
                    return;
                }

                if (pwd === cfm) {
                    confirmStatus.textContent = i18n.passwordsMatch;
                    confirmStatus.className = 'small text-success mt-1';
                    confirmInput.setCustomValidity('');
                    return;
                }

                confirmStatus.textContent = i18n.passwordsNoMatch;
                confirmStatus.className = 'small text-danger mt-1';
                confirmInput.setCustomValidity(i18n.passwordsNoMatch);
            };

            var updateVisibility = function () {
                var showRules = document.activeElement === passwordInput || (passwordInput.value || '') !== '';
                var showConfirm = document.activeElement === confirmInput || (confirmInput.value || '') !== '';

                rules.classList.toggle('d-none', !showRules);
                status.classList.toggle('d-none', !showRules);
                confirmStatus.classList.toggle('d-none', !showConfirm);
            };

            passwordInput.addEventListener('input', updateIndicator);
            passwordInput.addEventListener('focus', updateVisibility);
            passwordInput.addEventListener('blur', updateVisibility);
            confirmInput.addEventListener('input', function () {
                updateConfirmStatus();
                updateVisibility();
            });
            confirmInput.addEventListener('focus', updateVisibility);
            confirmInput.addEventListener('blur', updateVisibility);
            updateConfirmStatus();
            updateVisibility();
            updateIndicator();
        })();
    </script>

<?= $this->endSection() ?>
