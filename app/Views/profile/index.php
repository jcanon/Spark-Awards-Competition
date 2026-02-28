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
            <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
        </div>

        <hr class="mb-4">

        <?php if (!$isProfileComplete): ?>
            <div class="alert alert-warning border-left-warning shadow-sm" role="alert">
                <h6 class="font-weight-bold mb-2">Profile completion required before entering competitions</h6>
                <p class="mb-0">
                    You cannot enter a competition or submit an entry until your profile is complete. Please fill out all required fields marked with * and click Update Profile.
                </p>
            </div>
        <?php endif; ?>

        <?= view('partials/flash', ['showError' => false]) ?>

        <?php $newRecoveryCodes = session()->getFlashdata('recovery_codes'); ?>
        <?php if (!empty($newRecoveryCodes) && is_array($newRecoveryCodes)): ?>
            <div class="alert alert-warning border-left-warning shadow-sm">
                <h6 class="font-weight-bold mb-2">New Recovery Codes (Save Now)</h6>
                <p class="mb-2">These codes are shown one time only. Store them in a secure offline location.</p>
                <div class="row">
                    <?php foreach ($newRecoveryCodes as $rc): ?>
                        <div class="col-md-3"><code><?= esc((string)$rc) ?></code></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?= view('partials/form-errors') ?>

        <?php if (!empty($canManageRecoveryCodes)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Two-Factor Recovery Codes</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <?php if (!empty($hasRecoveryCodes)): ?>
                            You currently have <strong><?= (int)($remainingRecoveryCodes ?? 0) ?></strong> active recovery code(s).
                        <?php else: ?>
                            You do not have active recovery codes yet.
                        <?php endif; ?>
                    </p>
                    <p class="text-muted small">
                        Recovery codes are one-time backup codes used only if Duo is unavailable. Generating a new set invalidates all previous codes.
                    </p>
                    <form action="<?= site_url('profile/recovery-codes/regenerate') ?>" method="post" class="form-inline">
                        <?= csrf_field() ?>
                        <div class="form-group mr-2 mb-2">
                            <label for="recovery_current_password" class="sr-only">Current Password</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                </div>
                                <input type="password" id="recovery_current_password" name="recovery_current_password" class="form-control" placeholder="Current password" maxlength="128" autocomplete="current-password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary mb-2">
                            <?= !empty($hasRecoveryCodes) ? 'Regenerate Recovery Codes' : 'Generate Recovery Codes' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <form id="profileForm" action="<?= base_url('profile/update') ?>" method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Login Information</h6>
                </div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label for="email_address" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                            </div>
                            <input type="email" class="form-control" id="email_address" name="email_address" required maxlength="100" value="<?= esc((string)(old('email_address') ?? ($user->email_address ?? ''))) ?>">
                        </div>
                        <div class="invalid-feedback">A valid, unique email address is required.</div>
                    </div>
                    <div class="col-md-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            </div>
                            <input type="password" class="form-control" id="current_password" name="current_password" maxlength="128" autocomplete="current-password">
                        </div>
                        <div class="invalid-feedback">Current password is required to change your password.</div>
                    </div>
                    <div class="col-md-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            </div>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password">
                        </div>
                        <div class="invalid-feedback">Please enter a password that meets all requirements.</div>
                        <div id="profilePasswordRules" class="small mt-2 d-none">
                            <div data-rule="length" class="text-muted">At least 8 characters</div>
                            <div data-rule="upper" class="text-muted">At least 1 uppercase letter</div>
                            <div data-rule="lower" class="text-muted">At least 1 lowercase letter</div>
                            <div data-rule="number" class="text-muted">At least 1 number</div>
                            <div data-rule="symbol" class="text-muted">At least 1 symbol</div>
                        </div>
                        <div id="profilePasswordStatus" class="small text-muted mt-1 d-none">Optional: leave blank to keep your current password.</div>
                    </div>
                    <div class="col-md-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            </div>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                        </div>
                        <div class="invalid-feedback">Please confirm your password.</div>
                        <div id="profileConfirmStatus" class="small mt-1 d-none"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="100" value="<?= old('title') ?? ($user->title ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="100" value="<?= old('first_name') ?? ($user->first_name ?? '') ?>">
                            <div class="invalid-feedback">First name is required.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="100" value="<?= old('last_name') ?? ($user->last_name ?? '') ?>">
                            <div class="invalid-feedback">Last name is required.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="website" class="form-label">Website</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-globe" aria-hidden="true"></i></span>
                                </div>
                                <input type="url" class="form-control" id="website" name="website" maxlength="100" value="<?= old('website') ?? ($user->website ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="phone" class="form-label">Telephone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone" aria-hidden="true"></i></span>
                                </div>
                                <input type="text" class="form-control" id="phone" name="phone" required maxlength="25" value="<?= old('phone') ?? ($user->phone ?? '') ?>">
                            </div>
                            <div class="invalid-feedback">Telephone is required.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="mobile" class="form-label">Mobile</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-mobile-alt" aria-hidden="true"></i></span>
                                </div>
                                <input type="text" class="form-control" id="mobile" name="mobile" maxlength="25" value="<?= old('mobile') ?? ($user->mobile ?? '') ?>">
                            </div>
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
                            <label for="user_type_id" class="form-label">Entrant Type <span class="text-danger">*</span></label>
                            <select id="user_type_id" name="user_type_id" class="form-control" required>
                                <option value=""></option>
                                <?php foreach ($dropdowns['userTypes'] as $ut): ?>
                                    <option value="<?= $ut['user_type_id'] ?>" <?= (old('user_type_id') == $ut['user_type_id'] ? 'selected' : ((string)($user->user_type_id ?? '') === (string)$ut['user_type_id'] ? 'selected' : '')) ?>>
                                        <?= esc($ut['user_type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Entrant type is required.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="company_name" class="form-label">Organization <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required maxlength="100" value="<?= old('company_name') ?? ($user->company_name ?? '') ?>">
                            <div class="invalid-feedback">Organization is required.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="address1" class="form-label">Street Address 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address1" name="address1" required maxlength="100" value="<?= old('address1') ?? ($user->address1 ?? '') ?>">
                            <div class="invalid-feedback">Street Address 1 is required.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="address2" class="form-label">Street Address 2</label>
                            <input type="text" class="form-control" id="address2" name="address2" maxlength="100" value="<?= old('address2') ?? ($user->address2 ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" required maxlength="100" value="<?= old('city') ?? ($user->city ?? '') ?>">
                            <div class="invalid-feedback">City is required.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <?php
                        $selectedState = (string)(old('state') ?? ($user->state ?? ''));
                        if ($selectedState === 'None') {
                            $selectedState = 'NA';
                        }
                        ?>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State / Province <span class="text-danger">*</span></label>
                            <select id="state" name="state" class="form-control" required>
                                <option value=""></option>
                                <?php foreach ($dropdowns['states'] as $idx => $st): ?>
                                    <option value="<?= $st['scode'] ?>" <?= $selectedState === (string)$st['scode'] ? 'selected' : '' ?>>
                                        <?= esc($st['state']) ?>
                                    </option>
                                    <?php if ($idx === 0 && (string)$st['scode'] === 'NA'): ?>
                                        <option value="" disabled>--------------------</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">State or Province is required.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="zipcode" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="zipcode" name="zipcode" maxlength="25" value="<?= old('zipcode') ?? ($user->zipcode ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-flag" aria-hidden="true"></i></span>
                                </div>
                                <select id="country" name="country" class="form-control" required>
                                    <option value=""></option>
                                    <option value="None" <?= (old('country') == 'None' ? 'selected' : '') ?>>Not Listed</option>
                                    <option value="US" <?= (old('country') == 'US' ? 'selected' : (($user->country ?? '') === 'US' ? 'selected' : '')) ?>>United States</option>
                                    <?php foreach ($dropdowns['countries'] as $ct): ?>
                                        <option value="<?= $ct['ccode'] ?>" <?= (old('country') == $ct['ccode'] ? 'selected' : (($user->country ?? '') == $ct['ccode'] ? 'selected' : '')) ?>>
                                            <?= esc($ct['country']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="invalid-feedback">Country is required.</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $newsletterOptions = [
                'CleanTech Design',
                'Digital Design',
                'Experience Design',
                'Graphic Design',
                'Health & Medical Design',
                'Package Design',
                'Product Design',
                'Spark-E Design',
                'Spaces Design',
                'Student Design',
                'Transport Design',
                'Wear Design',
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
                    <h6 class="m-0 font-weight-bold text-primary">Additional Information</h6>
                </div>
                <div class="card-body">
                    <label for="how_did_you_find_us" class="form-label">How did you find us? <span class="text-danger">*</span></label>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <select id="how_did_you_find_us" name="how_did_you_find_us" class="form-control" required>
                                <option value=""></option>
                                <?php
                                $options = [
                                    'Colleague/Coworker',
                                    'Friend',
                                    'LinkedIn Design Group',
                                    'Poster',
                                    'Publication/Media',
                                    'Search Engine',
                                    'Spark International',
                                    'Other',
                                ];
                                foreach ($options as $opt) {
                                    $selected = (old('how_did_you_find_us') == $opt) ? 'selected' : ((($user->how_did_you_find_us ?? '') === $opt) ? 'selected' : '');
                                    echo "<option value=\"" . esc($opt) . "\" $selected>" . esc($opt) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Please select how you found us.</div>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="how_did_you_find_us_other" name="how_did_you_find_us_other" maxlength="20" placeholder="Other" value="<?= old('how_did_you_find_us_other') ?? ($user->how_did_you_find_us_other ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label d-block mb-2">Which Spark Awards Categories are you interested in?</label>

                            <div class="row">
                                <?php foreach ($newsletterOptions as $label): ?>
                                    <?php
                                    $id = 'email_newsletters_' . $mkId($label);
                                    $isChecked = in_array($label, $oldSelections, true) || in_array($label, $storedSelections, true);
                                    ?>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="email_newsletters[]" id="<?= esc($id) ?>" value="<?= esc($label) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="<?= esc($id) ?>"><?= esc($label) ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 mb-4">
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>

    <script src="/js/utils/password-policy.js"></script>
    <script src="/js/pages/profile-index.js"></script>

<?= $this->endSection() ?>

