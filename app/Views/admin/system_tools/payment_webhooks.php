<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$configuration = $report['configuration'] ?? [];
$recentPayments = $report['recent_payments'] ?? [];
$heldForReview = $report['held_for_review'] ?? [];
$recentLogs = $report['recent_logs'] ?? [];
$checklist = $report['checklist'] ?? [];

$formatBool = static function ($v): string {
    $ok = (bool) $v;
    $cls = $ok ? 'success' : 'danger';
    $txt = $ok ? 'Yes' : 'No';
    return '<span class="badge badge-' . $cls . '">' . $txt . '</span>';
};
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment Webhooks</h1>
        <div>
            <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools') ?>">Back to System Tools</a>
        </div>
    </div>

    <?= view('partials/flash') ?>

    <div class="card mb-4">
        <div class="card-header"><strong>Configuration</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                <tr><th class="spark-w-260">Generated At</th><td><?= esc((string) ($report['generated_at'] ?? '')) ?></td></tr>
                <tr><th>Configured Mode</th><td><?= esc((string) ($configuration['configured_mode'] ?? '')) ?></td></tr>
                <tr><th>Active Environment</th><td><?= esc((string) ($configuration['active_environment'] ?? '')) ?></td></tr>
                <tr><th>Webhook URL</th><td><code><?= esc((string) ($configuration['webhook_url'] ?? '')) ?></code></td></tr>
                <tr><th>Hosted Return URL</th><td><code><?= esc((string) ($configuration['hosted_return_url'] ?? '')) ?></code></td></tr>
                <tr><th>API Login Present</th><td><?= $formatBool((bool) ($configuration['api_login_present'] ?? false)) ?></td></tr>
                <tr><th>Transaction Key Present</th><td><?= $formatBool((bool) ($configuration['transaction_key_present'] ?? false)) ?></td></tr>
                <tr><th>Client Key Present</th><td><?= $formatBool((bool) ($configuration['client_key_present'] ?? false)) ?></td></tr>
                <tr><th>Signature Key Present</th><td><?= $formatBool((bool) ($configuration['signature_key_present'] ?? false)) ?></td></tr>
                <tr><th>Signature Key Length</th><td><?= (int) ($configuration['signature_key_length'] ?? 0) ?></td></tr>
                <tr><th>Expected Webhook Success Code</th><td><?= (int) ($configuration['expected_success_code'] ?? 200) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Checklist</strong></div>
        <div class="card-body">
            <ul class="mb-0 pl-3">
                <?php foreach ($checklist as $item): ?>
                    <li><?= esc((string) $item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Held For Review</strong>
            <span class="small text-muted"><?= (int) count($heldForReview) ?> shown</span>
        </div>
        <div class="card-body table-responsive">
            <?php if ($heldForReview !== []): ?>
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                    <tr>
                        <th>Entry</th>
                        <th>Phase</th>
                        <th>Entry Status</th>
                        <th>Payment Status</th>
                        <th>Transaction ID</th>
                        <th>Status Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($heldForReview as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/submissions/edit/' . rawurlencode((string) ($row['entry_id'] ?? ''))) ?>">
                                    <?= esc((string) ($row['design_name'] ?? (string) ($row['entry_id'] ?? ''))) ?>
                                </a>
                            </td>
                            <td><?= (int) ($row['payment_phase'] ?? 0) ?></td>
                            <td><?= esc((string) ($row['entry_status'] ?? '')) ?></td>
                            <td><span class="badge badge-warning"><?= esc((string) ($row['payment_status'] ?? '')) ?></span></td>
                            <td><code><?= esc((string) ($row['payment_transaction_id'] ?? '')) ?></code></td>
                            <td><?= esc((string) ($row['payment_status_updated_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="mb-0 text-muted">No held-for-review payments are currently stored.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Recent Payment Status Activity</strong>
            <span class="small text-muted"><?= (int) count($recentPayments) ?> shown</span>
        </div>
        <div class="card-body table-responsive">
            <?php if ($recentPayments !== []): ?>
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                    <tr>
                        <th>Entry</th>
                        <th>Phase</th>
                        <th>Entry Status</th>
                        <th>Payment Status</th>
                        <th>Total</th>
                        <th>Transaction ID</th>
                        <th>Message</th>
                        <th>Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentPayments as $row): ?>
                        <?php $status = strtolower((string) ($row['payment_status'] ?? 'pending')); ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/submissions/edit/' . rawurlencode((string) ($row['entry_id'] ?? ''))) ?>">
                                    <?= esc((string) ($row['design_name'] ?? (string) ($row['entry_id'] ?? ''))) ?>
                                </a>
                            </td>
                            <td><?= (int) ($row['payment_phase'] ?? 0) ?></td>
                            <td><?= esc((string) ($row['entry_status'] ?? '')) ?></td>
                            <td>
                                <span class="badge badge-<?= $status === 'paid' ? 'success' : ($status === 'held_for_review' ? 'warning' : ($status === 'declined' ? 'danger' : 'secondary')) ?>">
                                    <?= esc((string) ($row['payment_status'] ?? 'pending')) ?>
                                </span>
                            </td>
                            <td><?= esc((string) ($row['payment_total'] ?? '')) ?></td>
                            <td><code><?= esc((string) ($row['payment_transaction_id'] ?? '')) ?></code></td>
                            <td><?= esc((string) ($row['payment_status_message'] ?? '')) ?></td>
                            <td><?= esc((string) (($row['payment_status_updated_at'] ?? '') !== '' ? $row['payment_status_updated_at'] : ($row['payment_date'] ?? ''))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="mb-0 text-muted">No recent non-pending payment activity was found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Recent Webhook / Reconciliation Logs</strong>
            <span class="small text-muted"><?= (int) count($recentLogs) ?> lines</span>
        </div>
        <div class="card-body">
            <?php if ($recentLogs !== []): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                        <tr>
                            <th>Log File</th>
                            <th>Line</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentLogs as $row): ?>
                            <tr>
                                <td><code><?= esc((string) ($row['file'] ?? '')) ?></code></td>
                                <td><code><?= esc((string) ($row['line'] ?? '')) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="mb-0 text-muted">No recent webhook or reconciliation log lines were found in the current log files.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
