<p><strong>Phase:</strong> <?= esc((string)($payment['payment_phase'] ?? '')) ?></p>
<p><strong>Status:</strong> <?= esc(ucwords(str_replace('_', ' ', (string)($payment['payment_status'] ?? 'pending')))) ?></p>
<p><strong>Date:</strong> <?= esc(format_datetime_ui((string)($payment['payment_date'] ?? ''))) ?></p>
<?php if ((string)($payment['payment_status_updated_at'] ?? '') !== ''): ?>
    <p><strong>Status Updated:</strong> <?= esc(format_datetime_ui((string)($payment['payment_status_updated_at'] ?? ''))) ?></p>
<?php endif; ?>
<?php if ((string)($payment['payment_transaction_id'] ?? '') !== ''): ?>
    <p><strong>Transaction ID:</strong> <?= esc((string)($payment['payment_transaction_id'] ?? '')) ?></p>
<?php endif; ?>
<?php if ((string)($payment['payment_status_message'] ?? '') !== ''): ?>
    <p><strong>Gateway Message:</strong> <?= esc((string)($payment['payment_status_message'] ?? '')) ?></p>
<?php endif; ?>
<?php if ((string)($payment['payment_receipt'] ?? '') !== ''): ?>
    <p class="mb-1"><strong>Reference:</strong></p>
    <div class="p-2 mb-3 border rounded bg-light">
        <?= sanitize_receipt_html((string)($payment['payment_receipt'] ?? '')) ?>
    </div>
<?php endif; ?>
<p class="mb-0"><strong>Total:</strong> $<?= number_format((float)($payment['payment_total'] ?? 0), 2) ?></p>
