<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$phase1_total = is_countable($phase1) ? count($phase1) : 0;
$phase2_total = is_countable($phase2) ? count($phase2) : 0;
$phase3_total = is_countable($phase3) ? count($phase3) : 0;
$past_total = is_countable($past) ? count($past) : 0;
$isEntryEditLocked = static function ($entry): bool {
    $status = trim((string)($entry->entry_status ?? ''));
    $now = date('Y-m-d H:i:s');

    if ($status === 'Draft' || $status === 'Entrant') {
        $open = trim((string)($entry->jury_phase_1_open ?? ''));
        $close = trim((string)($entry->jury_phase_1_close ?? ''));
        return $open !== '' && $close !== '' && $open <= $now && $close > $now;
    }

    if ($status === 'Finalist') {
        $open = trim((string)($entry->jury_phase_2_open ?? ''));
        $close = trim((string)($entry->jury_phase_2_close ?? ''));
        return $open !== '' && $close !== '' && $open <= $now && $close > $now;
    }

    return false;
};
?>

    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Submissions</h1>
        </div>

        <p class="mb-3">
            To add a new entry submission to your account, go to the
            <a href="<?= site_url('competitions') ?>"><strong>Competitions</strong></a> page and select an open competition.
        </p>

        <hr class="mb-4">

        <?= view('partials/flash') ?>

        <!-- Phase 1 Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Phase 1 Submissions (<?= $phase1_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($phase1)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Design Name</th>
                                <th>Competition</th>
                                <th>Status</th>
                                <th>Payment (Phase 1)</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($phase1 as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                $p1 = (string)($e->phase_1_payment ?? 'Unpaid');
                                $isPaid = strcasecmp($p1, 'Paid') === 0;
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td><?= esc($e->entry_status ?? '') ?></td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <?= view('partials/payments/receipt-link', [
                                                'entryId' => $entryId,
                                                'phase' => 1,
                                                'label' => 'Paid - View Receipt',
                                            ]) ?>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-danger shadow-sm" href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/1') ?>" title="Submit Payment">Unpaid - Submit Payment</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$isEntryEditLocked($e)): ?>
                                            <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="Edit">Edit</a>
                                        <?php else: ?>
                                            <span class="text-muted">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form action="<?= site_url('submissions/delete/' . urlencode($entryId)) ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this entry? This cannot be undone.');" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-danger shadow-sm" type="submit" title="Delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0">No open phase 1 submissions found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Finalist (Phase 2) Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Finalist Submissions (<?= $phase2_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($phase2)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Design Name</th>
                                <th>Competition</th>
                                <th>Status</th>
                                <th>Payment (Phase 2)</th>
                                <th class="text-center">Edit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($phase2 as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                $p2 = (string)($e->phase_2_payment ?? 'Unpaid');
                                $isPaid = strcasecmp($p2, 'Paid') === 0;
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td><?= esc($e->entry_status ?? '') ?></td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <?= view('partials/payments/receipt-link', [
                                                'entryId' => $entryId,
                                                'phase' => 2,
                                                'label' => 'Paid - View Receipt',
                                            ]) ?>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-danger shadow-sm" href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/2') ?>" title="Submit Payment">Unpaid - Submit Payment</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$isEntryEditLocked($e)): ?>
                                            <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="Edit">Edit</a>
                                        <?php else: ?>
                                            <span class="text-muted">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0">No finalist submissions found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Winning (Phase 3) Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Winning Submissions (<?= $phase3_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($phase3)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle datatable">
                            <thead>
                            <tr>
                                <th>Design Name</th>
                                <th>Competition</th>
                                <th>Status</th>
                                <th>Payment Receipts</th>
                                <th class="text-center">Edit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($phase3 as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                $p1 = (string)($e->phase_1_payment ?? 'Unpaid');
                                $p2 = (string)($e->phase_2_payment ?? 'Unpaid');
                                $isPhase1Paid = strcasecmp($p1, 'Paid') === 0;
                                $isPhase2Paid = strcasecmp($p2, 'Paid') === 0;
                                [$statusLabel, $statusClass, $statusIcon] = $statusPill($e);
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td>
                                        <span class="status-pill <?= esc($statusClass) ?>">
                                            <?php if ($statusIcon !== ''): ?>
                                                <i class="<?= esc($statusIcon) ?> medal-icon" aria-hidden="true"></i>
                                            <?php endif; ?>
                                            <?= esc($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isPhase1Paid): ?>
                                            <?= view('partials/payments/receipt-link', [
                                                'entryId' => $entryId,
                                                'phase' => 1,
                                                'label' => 'Phase 1 - Paid Receipt',
                                                'class' => 'btn btn-sm btn-primary shadow-sm js-receipt-modal-link mb-1',
                                            ]) ?>
                                        <?php else: ?>
                                            <div class="small text-muted mb-1">Phase 1 - Unpaid</div>
                                        <?php endif; ?>

                                        <?php if ($isPhase2Paid): ?>
                                            <?= view('partials/payments/receipt-link', [
                                                'entryId' => $entryId,
                                                'phase' => 2,
                                                'label' => 'Phase 2 - Paid Receipt',
                                            ]) ?>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-danger shadow-sm"  href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/2') ?>" title="Submit Payment">Phase 2 - Unpaid, Submit Payment</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$isEntryEditLocked($e)): ?>
                                            <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="Edit">Edit</a>
                                        <?php else: ?>
                                            <span class="text-muted">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0">No winning submissions found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Past Submissions (<?= $past_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($past)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle datatable">
                            <thead>
                            <tr>
                                <th>Design Name</th>
                                <th>Competition</th>
                                <th>Status</th>
                                <th>Payment Receipts</th>
                                <th class="text-center">Edit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($past as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                $p1 = (string)($e->phase_1_payment ?? 'Unpaid');
                                $p2 = (string)($e->phase_2_payment ?? 'Unpaid');
                                $isPhase1Paid = strcasecmp($p1, 'Paid') === 0;
                                $isPhase2Paid = strcasecmp($p2, 'Paid') === 0;
                                [$statusLabel, $statusClass, $statusIcon] = $statusPill($e);
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($entryId !== ''): ?>
                                            <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                        <?php else: ?>
                                            <?= esc($e->design_name ?? '') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td>
                                        <span class="status-pill <?= esc($statusClass) ?>">
                                            <?php if ($statusIcon !== ''): ?>
                                                <i class="<?= esc($statusIcon) ?> medal-icon" aria-hidden="true"></i>
                                            <?php endif; ?>
                                            <?= esc($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isPhase1Paid): ?>
                                            <?= view('partials/payments/receipt-link', [
                                                'entryId' => $entryId,
                                                'phase' => 1,
                                                'label' => 'Phase 1 - Paid Receipt',
                                                'class' => 'btn btn-sm btn-primary shadow-sm js-receipt-modal-link mb-1',
                                            ]) ?>
                                        <?php else: ?>
                                            <div class="small text-muted mb-1">Phase 1 - Unpaid</div>
                                        <?php endif; ?>

                                        <?php if ($isPhase2Paid): ?>
                                            <?= view('partials/payments/receipt-link', [
                                                'entryId' => $entryId,
                                                'phase' => 2,
                                                'label' => 'Phase 2 - Paid Receipt',
                                            ]) ?>
                                        <?php else: ?>
                                            <div class="small text-muted">Phase 2 - Unpaid</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$isEntryEditLocked($e)): ?>
                                            <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="Edit">Edit</a>
                                        <?php else: ?>
                                            <span class="text-muted">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0">No past submissions found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Payment Receipt</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="receiptModalBody">
                    <div class="text-muted">Loading receipt...</div>
                </div>
                <div class="modal-footer">
                    <a id="receiptModalPdfBtn" class="btn btn-sm btn-primary" href="#" target="_blank" rel="noopener">Save as PDF</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/pages/submissions-index.js"></script>

<?= $this->endSection() ?>
