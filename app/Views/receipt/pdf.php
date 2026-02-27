<?php
declare(strict_types=1);

$payment = is_array($payment ?? null) ? $payment : [];
$entryMeta = is_array($entryMeta ?? null) ? $entryMeta : [];
$title = (string)($title ?? 'Payment Receipt');
$entryId = (string)($entryId ?? '');
$logoPath = (string)($logoPath ?? '');
$logoDataUri = '';
if ($logoPath !== '' && is_file($logoPath)) {
    $logoData = @file_get_contents($logoPath);
    if ($logoData !== false) {
        $logoDataUri = 'data:image/jpeg;base64,' . base64_encode($logoData);
    }
}
$fmtDate = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '-';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }
    return date('M j, Y g:i A', $ts);
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; margin: 0; }
        .page { padding: 28px 34px; }
        .header { width: 100%; margin-bottom: 18px; border-bottom: 2px solid #0f766e; padding-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo { height: 34px; }
        .title { text-align: right; font-size: 18px; font-weight: 700; letter-spacing: 0.2px; color: #0f172a; }
        .meta { text-align: right; font-size: 11px; color: #64748b; margin-top: 4px; }
        .section { margin-bottom: 16px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #0f766e; margin-bottom: 6px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 5px 0; }
        .label { width: 34%; color: #475569; font-weight: 700; }
        .value { color: #0f172a; }
        .receipt-reference { border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; padding: 10px; line-height: 1.45; }
        .total-bar { margin-top: 12px; border-top: 2px solid #cbd5e1; padding-top: 8px; text-align: right; font-size: 16px; font-weight: 700; color: #0f172a; }
        .footer { margin-top: 28px; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        <?php if ($logoDataUri !== ''): ?>
                            <img class="logo" src="<?= esc($logoDataUri) ?>" alt="Spark Awards">
                        <?php else: ?>
                            <strong>SPARK AWARDS</strong>
                        <?php endif; ?>
                    </td>
                    <td><div class="title">Payment Receipt</div></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Receipt Details</div>
            <table class="grid">
                <tr><td class="label">Entry ID</td><td class="value"><?= esc($entryId) ?></td></tr>
                <tr><td class="label">Payment ID</td><td class="value"><?= esc((string)($payment['payment_id'] ?? '')) ?></td></tr>
                <tr><td class="label">Payment Phase</td><td class="value"><?= esc((string)($payment['payment_phase'] ?? '')) ?></td></tr>
                <tr><td class="label">Payment Date</td><td class="value"><?= esc($fmtDate((string)($payment['payment_date'] ?? ''))) ?></td></tr>
                <tr><td class="label">Competition</td><td class="value"><?= esc(trim((string)($entryMeta['comp_type_name'] ?? '') . ' ' . (string)($entryMeta['comp_year'] ?? ''))) ?></td></tr>
                <tr><td class="label">Design Name</td><td class="value"><?= esc((string)($entryMeta['design_name'] ?? '')) ?></td></tr>
                <tr><td class="label">Company</td><td class="value"><?= esc((string)($entryMeta['company_name'] ?? '')) ?></td></tr>
                <tr><td class="label">Contact</td><td class="value"><?= esc(trim((string)($entryMeta['first_name'] ?? '') . ' ' . (string)($entryMeta['last_name'] ?? ''))) ?><?= !empty($entryMeta['email_address']) ? ' (' . esc((string)$entryMeta['email_address']) . ')' : '' ?></td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Payment Reference</div>
            <div class="receipt-reference">
                <?= sanitize_receipt_html((string)($payment['payment_receipt'] ?? '')) ?>
            </div>
            <div class="total-bar">
                Total Paid: $<?= number_format((float)($payment['payment_total'] ?? 0), 2) ?>
            </div>
        </div>

        <div class="footer">
            Spark Awards - Official Payment Confirmation. Keep this receipt for your records.
        </div>
    </div>
</body>
</html>
