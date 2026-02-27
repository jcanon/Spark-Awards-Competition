<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$row = is_array($row ?? null) ? $row : [];
$filters = is_array($filters ?? null) ? $filters : [];
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
$v = static function (string $field, string $default = '') use ($row): string {
    $old = old($field);
    if ($old !== null) {
        return (string)$old;
    }
    if (array_key_exists($field, $row)) {
        return (string)$row[$field];
    }
    return $default;
};

$entryStatus = trim((string)($row['entry_status'] ?? ''));
$winnerLevel = trim((string)($row['winner_level_name'] ?? ''));
if (strcasecmp($entryStatus, 'Winner') === 0 && $winnerLevel !== '') {
    $entryStatus .= ': ' . $winnerLevel;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Certificate Request</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/certificate-requests?' . http_build_query(array_filter([
            'year' => (int)($filters['year'] ?? 0) ?: null,
            'competition_id' => (int)($filters['competition_id'] ?? 0) ?: null,
            'status' => trim((string)($filters['status'] ?? '')) ?: null,
        ]))) ?>">Back to Certificate Requests</a>
    </div>

    <?= view('partials/flash', ['showSuccess' => false]) ?>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-1"><strong>Submission:</strong> <?= esc((string)($row['design_name'] ?? '')) ?></p>
            <p class="mb-1"><strong>Competition:</strong> <?= esc((string)($row['comp_type_name'] ?? '')) ?> <?= (int)($row['comp_year'] ?? 0) ?></p>
            <p class="mb-0"><strong>Entry Status:</strong> <?= esc($entryStatus) ?></p>
        </div>
    </div>

    <form method="post" action="<?= site_url('admin/certificate-requests/update/' . (int)($row['certificate_request_id'] ?? 0)) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="year" value="<?= (int)($filters['year'] ?? 0) ?>">
        <input type="hidden" name="competition_id" value="<?= (int)($filters['competition_id'] ?? 0) ?>">
        <input type="hidden" name="status" value="<?= esc((string)($filters['status'] ?? '')) ?>">

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Request Details</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="request_status">Request Status</label>
                        <select id="request_status" name="request_status" class="form-control" required>
                            <?php $status = $v('request_status', 'Pending'); ?>
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?= esc($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="certificate_quantity">Certificate Quantity</label>
                        <input id="certificate_quantity" name="certificate_quantity" type="number" min="0" max="999" class="form-control" value="<?= esc($v('certificate_quantity', '0')) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="shipping_method">Shipping Method</label>
                        <?php $ship = $v('shipping_method', 'least_expensive'); ?>
                        <select id="shipping_method" name="shipping_method" class="form-control">
                            <option value="least_expensive" <?= $ship === 'least_expensive' ? 'selected' : '' ?>>Least Expensive</option>
                            <option value="express" <?= $ship === 'express' ? 'selected' : '' ?>>Express</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="lamination_requested">Lamination</label>
                        <?php $lam = $v('lamination_requested', '0') === '1'; ?>
                        <select id="lamination_requested" name="lamination_requested" class="form-control">
                            <option value="0" <?= !$lam ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $lam ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="designer_names">Designer Names</label>
                        <input id="designer_names" name="designer_names" class="form-control" maxlength="120" value="<?= esc($v('designer_names')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="additional_persons">Additional Persons</label>
                        <input id="additional_persons" name="additional_persons" class="form-control" maxlength="30" value="<?= esc($v('additional_persons')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="printed_organization">Printed Organization</label>
                        <input id="printed_organization" name="printed_organization" class="form-control" maxlength="120" value="<?= esc($v('printed_organization')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Contact & Shipping</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="contact_person">Contact Person</label>
                        <input id="contact_person" name="contact_person" class="form-control" maxlength="120" value="<?= esc($v('contact_person')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input id="contact_phone" name="contact_phone" class="form-control" maxlength="25" value="<?= esc($v('contact_phone')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="contact_email">Contact Email</label>
                        <input id="contact_email" name="contact_email" class="form-control" maxlength="120" value="<?= esc($v('contact_email')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="shipping_company">Company / Organization</label>
                        <input id="shipping_company" name="shipping_company" class="form-control" maxlength="120" value="<?= esc($v('shipping_company')) ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="shipping_address1">Shipping Address 1</label>
                        <input id="shipping_address1" name="shipping_address1" class="form-control" maxlength="150" value="<?= esc($v('shipping_address1')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="shipping_address2">Shipping Address 2</label>
                        <input id="shipping_address2" name="shipping_address2" class="form-control" maxlength="150" value="<?= esc($v('shipping_address2')) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="shipping_city">City</label>
                        <input id="shipping_city" name="shipping_city" class="form-control" maxlength="80" value="<?= esc($v('shipping_city')) ?>">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="shipping_state">State / Province</label>
                        <input id="shipping_state" name="shipping_state" class="form-control" maxlength="80" value="<?= esc($v('shipping_state')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="shipping_postal_code">Postal Code</label>
                        <input id="shipping_postal_code" name="shipping_postal_code" class="form-control" maxlength="30" value="<?= esc($v('shipping_postal_code')) ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="shipping_country">Country</label>
                        <input id="shipping_country" name="shipping_country" class="form-control" maxlength="80" value="<?= esc($v('shipping_country')) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-0">
                        <label for="special_instructions">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" class="form-control" rows="4" maxlength="2000"><?= esc($v('special_instructions')) ?></textarea>
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label for="admin_notes">Admin Notes</label>
                        <textarea id="admin_notes" name="admin_notes" class="form-control" rows="4" maxlength="2000"><?= esc($v('admin_notes')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Update Certificate Request</button>
        <a class="btn btn-secondary" href="<?= site_url('admin/certificate-requests?' . http_build_query(array_filter([
            'year' => (int)($filters['year'] ?? 0) ?: null,
            'competition_id' => (int)($filters['competition_id'] ?? 0) ?: null,
            'status' => trim((string)($filters['status'] ?? '')) ?: null,
        ]))) ?>">Back to Certificate Requests</a>
    </form>
</div>

<?= $this->endSection() ?>

