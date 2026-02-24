<p><strong>Phase:</strong> <?= esc((string)($payment['payment_phase'] ?? '')) ?></p>
<p><strong>Date:</strong> <?= esc(format_datetime_ui((string)($payment['payment_date'] ?? ''))) ?></p>
<p class="mb-1"><strong>Reference:</strong></p>
<div class="p-2 mb-3 border rounded bg-light">
    <?= sanitize_receipt_html((string)($payment['payment_receipt'] ?? '')) ?>
</div>
<p class="mb-0"><strong>Total:</strong> $<?= number_format((float)($payment['payment_total'] ?? 0), 2) ?></p>
