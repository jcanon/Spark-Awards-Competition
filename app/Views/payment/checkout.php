<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$showAnetDebug = trim((string) service('request')->getGet('anet_debug')) === '1'
    && in_array((string) session('role'), ['admin', 'editor'], true);
$anetDebugMeta = is_array($anetDebugMeta ?? null) ? $anetDebugMeta : [];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Submit Payment</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase) ?>">Back to Cart</a>
    </div>

    <?= view('partials/flash') ?>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Payment Details</h6>
                </div>
                <div class="card-body">
                    <form
                        id="anetPaymentForm"
                        method="post"
                        action="<?= site_url('payments/entry/' . rawurlencode($entryId) . '/phase/' . (int)$phase . '/checkout') ?>"
                        data-api-login-id="<?= esc($apiLoginId) ?>"
                        data-client-key="<?= esc($clientKey) ?>"
                    >
                        <?= csrf_field() ?>
                        <input type="hidden" name="data_descriptor" id="data_descriptor" value="">
                        <input type="hidden" name="data_value" id="data_value" value="">

                        <div id="paymentStatus" class="alert alert-danger d-none" role="alert"></div>

                        <div class="border rounded p-3 mb-4">
                            <h6 class="font-weight-bold text-dark mb-3">Billing Information</h6>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="billing_first_name">First Name <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="billing_first_name"
                                        name="billing_first_name"
                                        maxlength="50"
                                        autocomplete="given-name"
                                        value="<?= esc(old('billing_first_name', '')) ?>"
                                        required
                                    >
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="billing_last_name">Last Name <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="billing_last_name"
                                        name="billing_last_name"
                                        maxlength="50"
                                        autocomplete="family-name"
                                        value="<?= esc(old('billing_last_name', '')) ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="billing_address">Street Address <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="billing_address"
                                    name="billing_address"
                                    maxlength="60"
                                    autocomplete="address-line1"
                                    value="<?= esc(old('billing_address', '')) ?>"
                                    required
                                >
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="billing_city">City <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="billing_city"
                                        name="billing_city"
                                        maxlength="40"
                                        autocomplete="address-level2"
                                        value="<?= esc(old('billing_city', '')) ?>"
                                        required
                                    >
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="billing_state">State / Province / Region</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="billing_state"
                                        name="billing_state"
                                        maxlength="40"
                                        autocomplete="address-level1"
                                        value="<?= esc(old('billing_state', '')) ?>"
                                    >
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="billing_zip">ZIP / Postal Code</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="billing_zip"
                                        name="billing_zip"
                                        autocomplete="postal-code"
                                        maxlength="20"
                                        value="<?= esc(old('billing_zip', '')) ?>"
                                    >
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label for="billing_country">Country <span class="text-danger">*</span></label>
                                <select
                                    class="form-control"
                                    id="billing_country"
                                    name="billing_country"
                                    autocomplete="country-name"
                                    required
                                >
                                    <option value="">Select country</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= esc((string)$country['country']) ?>" <?= old('billing_country', '') === (string)$country['country'] ? 'selected' : '' ?>><?= esc((string)$country['country']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-4">
                            <h6 class="font-weight-bold text-dark mb-3">Contact Information</h6>

                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="billing_phone">Phone Number <span class="text-danger">*</span></label>
                                    <input
                                        type="tel"
                                        class="form-control"
                                        id="billing_phone"
                                        name="billing_phone"
                                        maxlength="25"
                                        autocomplete="tel"
                                        value="<?= esc(old('billing_phone', '')) ?>"
                                        required
                                    >
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="billing_email">Email <span class="text-danger">*</span></label>
                                    <input
                                        type="email"
                                        class="form-control"
                                        id="billing_email"
                                        name="billing_email"
                                        maxlength="120"
                                        autocomplete="email"
                                        value="<?= esc(old('billing_email', '')) ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-4">
                            <h6 class="font-weight-bold text-dark mb-3">Card Information</h6>

                            <div class="form-group">
                                <label for="card_number">Card Number <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="card_number"
                                    inputmode="numeric"
                                    autocomplete="cc-number"
                                    maxlength="23"
                                    placeholder="Card number"
                                    required
                                >
                            </div>

                            <div class="form-row mb-0">
                                <div class="form-group col-md-4">
                                    <label for="expiry_month">Expiration Month <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="expiry_month"
                                        inputmode="numeric"
                                        autocomplete="cc-exp-month"
                                        maxlength="2"
                                        placeholder="MM"
                                        required
                                    >
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="expiry_year">Expiration Year <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="expiry_year"
                                        inputmode="numeric"
                                        autocomplete="cc-exp-year"
                                        maxlength="4"
                                        placeholder="YYYY"
                                        required
                                    >
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="card_code">Security Code <span class="text-danger">*</span></label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="card_code"
                                        inputmode="numeric"
                                        autocomplete="cc-csc"
                                        maxlength="4"
                                        placeholder="CVV"
                                        required
                                    >
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg" id="paymentSubmitButton">Pay $<?= number_format((float)$cart['total'], 2) ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Charge Today</span>
                        <strong class="h5 mb-0">$<?= number_format((float)$cart['total'], 2) ?></strong>
                    </div>
                    <p class="small text-muted mb-0">After approval, this page will send you directly to your receipt.</p>
                </div>
            </div>

            <div class="card border-left-info shadow">
                <div class="card-header bg-white">
                    <h6 class="m-0 font-weight-bold text-info">International Address Notes</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">State / province / region and postal code can be left blank if they do not apply to the billing address.</p>
                    <p class="small text-muted mb-0">If the bank or gateway declines the charge, the returned error message will be shown on this page.</p>
                </div>
            </div>

            <div class="card shadow mt-4">
                <div class="card-header bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Secure Checkout</h6>
                </div>
                <div class="card-body text-center">
                    <div class="AuthorizeNetSeal">
                        <script type="text/javascript" language="javascript">var ANS_customer_id="f6a2e288-9285-4782-aa07-4d868a85115f";</script>
                        <script type="text/javascript" language="javascript" src="//verify.authorize.net:443/anetseal/seal.js"></script>
                    </div>
                </div>
            </div>

            <?php if ($showAnetDebug): ?>
                <div class="card border-left-warning shadow mt-4">
                    <div class="card-header bg-white">
                        <h6 class="m-0 font-weight-bold text-warning">Authorize.Net Debug</h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-2"><strong>Selected Env:</strong> <?= esc((string)($anetDebugMeta['selectedEnv'] ?? 'unknown')) ?></p>
                        <p class="small mb-2"><strong>Configured Mode:</strong> <code><?= esc((string)($anetDebugMeta['configuredMode'] ?? '')) ?></code></p>
                        <p class="small mb-2"><strong>CI Environment:</strong> <code><?= esc((string)($anetDebugMeta['ciEnvironment'] ?? '')) ?></code></p>
                        <p class="small mb-2"><strong>API Login ID:</strong> <code><?= esc((string)($anetDebugMeta['apiLoginId'] ?? ($apiLoginId ?? '')) ) ?></code></p>
                        <p class="small mb-2"><strong>Client Key:</strong> <code><?= esc((string)($anetDebugMeta['clientKeyMasked'] ?? '[empty]')) ?></code></p>
                        <p class="small mb-2"><strong>Client Key Length:</strong> <?= esc((string)($anetDebugMeta['clientKeyLength'] ?? 0)) ?></p>
                        <p class="small mb-2"><strong>Client Key SHA-256 Prefix:</strong> <code><?= esc((string)($anetDebugMeta['clientKeyHashPrefix'] ?? '')) ?></code></p>
                        <p class="small mb-2"><strong>Transaction Key Present:</strong> <?= !empty($anetDebugMeta['transactionKeyPresent']) ? 'yes' : 'no' ?></p>
                        <p class="small mb-2"><strong>Transaction Key Length:</strong> <?= esc((string)($anetDebugMeta['transactionKeyLength'] ?? 0)) ?></p>
                        <p class="small mb-2"><strong>Transaction Key SHA-256 Prefix:</strong> <code><?= esc((string)($anetDebugMeta['transactionKeyHashPrefix'] ?? '')) ?></code></p>
                        <p class="small mb-0"><strong>Accept.js URL:</strong> <code><?= esc((string)($anetDebugMeta['acceptJsUrl'] ?? ($acceptJsUrl ?? '')) ) ?></code></p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script type="text/javascript" src="<?= esc($acceptJsUrl) ?>" charset="utf-8"></script>
<script src="/js/pages/payment-checkout.js"></script>

<?= $this->endSection() ?>
