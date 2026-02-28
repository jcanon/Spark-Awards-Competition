<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('partials/flash') ?>

<div class="row no-gutters">
            <div class="col-12 col-lg-7 mb-4 pr-lg-3">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">Login to Spark</h1>
                        <p>Welcome to the <strong>Spark Awards Competitions</strong>.</p>
                        <p class="text-muted">Login below to access your design submissions, submit payments and see your submissions results.</p>

                        <form id="loginForm" action="<?= site_url('auth/login') ?>" method="post" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label for="login_email_address">Email Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                                    </div>
                                    <input id="login_email_address" name="email" type="email" maxlength="100" class="form-control" value="<?= esc(old('email')) ?>" required>
                                </div>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="form-group">
                                <label for="login_password">Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                    </div>
                                    <input id="login_password" name="password" type="password" maxlength="128" class="form-control" autocomplete="current-password" required>
                                </div>
                                <div class="invalid-feedback">Password is required.</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Login</button>
                        </form>

                        <p class="mt-3 mb-0"><a href="<?= site_url('auth/forgot') ?>">Forgot your password?</a></p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5 mb-4 pl-lg-3">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">Create Account</h2>
                        <p class="text-muted">Before you can submit your entry you must set up an account. Setting up an account is quick and easy.</p>

                        <form id="registerForm" action="<?= site_url('auth/register') ?>" method="post" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label for="reg_email">Email Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                                    </div>
                                    <input id="reg_email" name="email" type="email" maxlength="100" class="form-control" required>
                                </div>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="form-group">
                                <label for="reg_password">Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                    </div>
                                    <input id="reg_password" name="password" type="password" maxlength="128" class="form-control" autocomplete="new-password" required>
                                </div>
                                <div class="invalid-feedback">Please enter a password that meets all requirements.</div>
                                <div id="signupPasswordRules" class="small mt-2 d-none">
                                    <div data-rule="length" class="text-danger">At least 8 characters</div>
                                    <div data-rule="upper" class="text-danger">At least 1 uppercase letter</div>
                                    <div data-rule="lower" class="text-danger">At least 1 lowercase letter</div>
                                    <div data-rule="number" class="text-danger">At least 1 number</div>
                                    <div data-rule="symbol" class="text-danger">At least 1 symbol</div>
                                </div>
                                <div id="signupPasswordStatus" class="small text-danger mt-1 d-none">Password does not meet all requirements yet.</div>
                            </div>
                            <div class="form-group">
                                <label for="reg_confirm_password">Confirm Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                    </div>
                                    <input id="reg_confirm_password" name="confirm_password" type="password" maxlength="128" class="form-control" autocomplete="new-password" required>
                                </div>
                                <div class="invalid-feedback">Confirm Password</div>
                                <div id="signupConfirmStatus" class="small mt-1 d-none"></div>
                            </div>
                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="<?= esc($siteKey) ?>"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                        </form>
                    </div>
                </div>
            </div>
</div>

<script src="/js/utils/password-policy.js"></script>
<script src="/js/pages/auth-login.js"></script>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?= $this->endSection() ?>
