<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$request = is_array($request ?? null) ? $request : [];
$prefill = is_array($prefill ?? null) ? $prefill : [];
$isReadOnly = (bool)($isReadOnly ?? false);
$requestStatus = trim((string)($request['request_status'] ?? ''));
$val = static function (string $field, string $default = '') use ($request, $prefill): string {
    $old = old($field);
    if ($old !== null) {
        return (string)$old;
    }
    if (array_key_exists($field, $request)) {
        return (string)$request[$field];
    }
    if (array_key_exists($field, $prefill)) {
        return (string)$prefill[$field];
    }
    return $default;
};
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Certificate Request</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('certificates') ?>">Back to Certificates</a>
    </div>

    <?= view('partials/flash', ['showSuccess' => false]) ?>
    <?php if ($isReadOnly): ?>
        <div class="alert alert-info">
            This request is currently <strong><?= esc($requestStatus) ?></strong> and can no longer be edited.
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-2"><strong>Submission:</strong> <?= esc((string)($entry['design_name'] ?? '')) ?></p>
            <p class="mb-2"><strong>Competition:</strong> <?= esc((string)($entry['comp_type_name'] ?? '')) ?> <?= (int)($entry['comp_year'] ?? 0) ?></p>
            <p class="mb-0 text-muted">
                Spark certificates are available for entrants, finalists, and winners. Printed certificates are $125 each plus shipping/handling. Lamination is optional at $30 each.
            </p>
        </div>
    </div>

    <form method="post" action="<?= site_url('certificates/request/' . rawurlencode((string)$entry['entry_id'])) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <fieldset <?= $isReadOnly ? 'disabled' : '' ?>>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Required Information</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="certificate_quantity">How Many Certificates <span class="text-danger">*</span></label>
                        <input id="certificate_quantity" name="certificate_quantity" type="number" min="1" max="999" class="form-control" required value="<?= esc($val('certificate_quantity', '1')) ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="shipping_method">Shipping Method <span class="text-danger">*</span></label>
                        <?php $ship = $val('shipping_method', 'least_expensive'); ?>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-shipping-fast" aria-hidden="true"></i></span>
                            </div>
                            <select id="shipping_method" name="shipping_method" class="form-control" required>
                                <option value="least_expensive" <?= $ship === 'least_expensive' ? 'selected' : '' ?>>Least Expensive</option>
                                <option value="express" <?= $ship === 'express' ? 'selected' : '' ?>>Express (more expensive)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="designer_names">Designer Name(s) <span class="text-danger">*</span> <small class="text-muted">(120 characters max)</small></label>
                        <input id="designer_names" name="designer_names" class="form-control" maxlength="120" required value="<?= esc($val('designer_names')) ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="additional_persons">Additional Persons <small class="text-muted">(30 characters max)</small></label>
                        <input id="additional_persons" name="additional_persons" class="form-control" maxlength="30" value="<?= esc($val('additional_persons')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="printed_organization">Design firm, company, department, school or organization to be printed on the certificate (if any)</label>
                        <input id="printed_organization" name="printed_organization" class="form-control" maxlength="120" value="<?= esc($val('printed_organization')) ?>">
                    </div>
                    <div class="col-md-6 form-group"></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Contact & Shipping</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="contact_person">Contact Person <span class="text-danger">*</span></label>
                        <input id="contact_person" name="contact_person" class="form-control" maxlength="120" required value="<?= esc($val('contact_person')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="contact_phone">Contact Phone / Mobile <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-phone" aria-hidden="true"></i></span>
                            </div>
                            <input id="contact_phone" name="contact_phone" class="form-control" maxlength="25" required value="<?= esc($val('contact_phone')) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="contact_email">
                            Email Address <span class="text-danger">*</span>
                            <button
                                type="button"
                                class="btn btn-link btn-sm p-0 ml-1 align-baseline"
                                data-toggle="modal"
                                data-target="#certificateEmailHelpModal"
                                aria-label="Email provider guidance"
                                title="Email provider guidance"
                            >
                                <i class="fas fa-question-circle" aria-hidden="true"></i>
                            </button>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                            </div>
                            <input id="contact_email" name="contact_email" type="email" class="form-control" maxlength="120" required value="<?= esc($val('contact_email')) ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="shipping_company">Company / Organization (if any)</label>
                        <input id="shipping_company" name="shipping_company" class="form-control" maxlength="120" value="<?= esc($val('shipping_company')) ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="shipping_address1">Shipping Address <span class="text-danger">*</span></label>
                        <input id="shipping_address1" name="shipping_address1" class="form-control" maxlength="150" required value="<?= esc($val('shipping_address1')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="shipping_address2">Address Line 2</label>
                        <input id="shipping_address2" name="shipping_address2" class="form-control" maxlength="150" value="<?= esc($val('shipping_address2')) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="shipping_city">City <span class="text-danger">*</span></label>
                        <input id="shipping_city" name="shipping_city" class="form-control" maxlength="80" required value="<?= esc($val('shipping_city')) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="shipping_state">State / Province <span class="text-danger">*</span></label>
                        <input id="shipping_state" name="shipping_state" class="form-control" maxlength="80" required value="<?= esc($val('shipping_state')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="shipping_postal_code">Postal Code <span class="text-danger">*</span></label>
                        <input id="shipping_postal_code" name="shipping_postal_code" class="form-control" maxlength="30" required value="<?= esc($val('shipping_postal_code')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="shipping_country">Country <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-flag" aria-hidden="true"></i></span>
                            </div>
                            <input id="shipping_country" name="shipping_country" class="form-control" maxlength="80" required value="<?= esc($val('shipping_country')) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 form-group">
                        <?php $lam = $val('lamination_requested', '0') === '1'; ?>
                        <label for="lamination_requested">Lamination</label>
                        <select id="lamination_requested" name="lamination_requested" class="form-control">
                            <option value="0" <?= !$lam ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $lam ? 'selected' : '' ?>>Yes (+$30 each)</option>
                        </select>
                        <small class="text-muted">Recommended for extended durability.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="special_instructions">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" class="form-control" rows="4" maxlength="2000"><?= esc($val('special_instructions')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        </fieldset>

        <?php if (!$isReadOnly): ?>
            <button class="btn btn-primary" type="submit">Save Certificate Request</button>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= site_url('certificates') ?>">Back to Certificates</a>
    </form>
</div>

<div class="modal fade" id="certificateEmailHelpModal" tabindex="-1" role="dialog" aria-labelledby="certificateEmailHelpModalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certificateEmailHelpModalTitle">Email Delivery Tip</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                For the most reliable delivery of payment and shipping updates, please use a widely supported email provider such as Gmail or Outlook/Hotmail.
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

