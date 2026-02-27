<?php

declare(strict_types=1);

$entryId = (string)($entryId ?? '');
$phase = (int)($phase ?? 1);
$label = (string)($label ?? 'Paid - View Receipt');
$class = (string)($class ?? 'btn btn-sm btn-primary shadow-sm js-receipt-modal-link');
$title = (string)($title ?? 'View Receipt');

if ($entryId === '') {
    return;
}
?>
<a
    class="<?= esc($class, 'attr') ?>"
    href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/' . $phase . '/receipt') ?>"
    data-receipt-url="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/' . $phase . '/receipt-content') ?>"
    data-receipt-pdf-url="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/' . $phase . '/receipt-pdf') ?>"
    title="<?= esc($title, 'attr') ?>"
><?= esc($label) ?></a>
