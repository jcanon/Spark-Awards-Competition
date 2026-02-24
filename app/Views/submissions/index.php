<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$phase1_total = is_countable($phase1) ? count($phase1) : 0;
$phase2_total = is_countable($phase2) ? count($phase2) : 0;
$phase3_total = is_countable($phase3) ? count($phase3) : 0;
$past_total = is_countable($past) ? count($past) : 0;
?>

    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><?= esc(lang('Entrant.my_submissions')) ?></h1>
        </div>

        <p class="mb-3">
            <?= esc(lang('Entrant.submissions_intro')) ?>
            <a href="<?= site_url('competitions') ?>"><strong><?= esc(lang('Entrant.nav_competitions')) ?></strong></a> <?= esc(lang('Entrant.submissions_intro_suffix')) ?>
        </p>

        <hr class="mb-4">

        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc($msg) ?></div>
        <?php endif; ?>
        <?php if ($err = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc($err) ?></div>
        <?php endif; ?>

        <!-- Phase 1 Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.phase1_submissions')) ?> (<?= $phase1_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($phase1)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?= esc(lang('Entrant.design_name')) ?></th>
                                <th><?= esc(lang('Entrant.competition')) ?></th>
                                <th><?= esc(lang('Entrant.status')) ?></th>
                                <th><?= esc(lang('Entrant.payment_phase_1')) ?></th>
                                <th class="text-center"><?= esc(lang('Entrant.edit')) ?></th>
                                <th class="text-center"><?= esc(lang('Entrant.delete')) ?></th>
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
                                        <a class="font-weight-bold" href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td><?= esc($e->entry_status ?? '') ?></td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <a
                                                class="btn btn-sm btn-primary shadow-sm js-receipt-modal-link"
                                                href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/1/receipt') ?>"
                                                data-receipt-url="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/1/receipt-content') ?>"
                                                title="<?= esc(lang('Entrant.view_receipt')) ?>"
                                            ><?= esc(lang('Entrant.paid_view_receipt')) ?></a>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-danger shadow-sm" href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/1') ?>" title="<?= esc(lang('Entrant.submit_payment_action')) ?>"><?= esc(lang('Entrant.unpaid_submit_payment')) ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="<?= esc(lang('Entrant.edit')) ?>"><?= esc(lang('Entrant.edit')) ?></a>
                                    </td>
                                    <td class="text-center">
                                        <form action="<?= site_url('submissions/delete/' . urlencode($entryId)) ?>" method="post" onsubmit="return confirm('<?= esc(lang('Entrant.confirm_delete_entry'), 'js') ?>');" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-danger shadow-sm" type="submit" title="<?= esc(lang('Entrant.delete')) ?>"><?= esc(lang('Entrant.delete')) ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0"><?= esc(lang('Entrant.no_phase1')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Finalist (Phase 2) Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.finalist_submissions')) ?> (<?= $phase2_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($phase2)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?= esc(lang('Entrant.design_name')) ?></th>
                                <th><?= esc(lang('Entrant.competition')) ?></th>
                                <th><?= esc(lang('Entrant.status')) ?></th>
                                <th><?= esc(lang('Entrant.payment_phase_2')) ?></th>
                                <th class="text-center"><?= esc(lang('Entrant.edit')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($phase2 as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                $p3 = (string)($e->phase_3_payment ?? 'Unpaid');
                                $isPaid = strcasecmp($p3, 'Paid') === 0;
                                ?>
                                <tr>
                                    <td>
                                        <a class="font-weight-bold" href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td><?= esc($e->entry_status ?? '') ?></td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <a
                                                class="btn btn-sm btn-primary shadow-sm js-receipt-modal-link"
                                                href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/2/receipt') ?>"
                                                data-receipt-url="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/2/receipt-content') ?>"
                                                title="<?= esc(lang('Entrant.view_receipt')) ?>"
                                            ><?= esc(lang('Entrant.paid_view_receipt')) ?></a>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-danger shadow-sm" href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/2') ?>" title="<?= esc(lang('Entrant.submit_payment_action')) ?>"><?= esc(lang('Entrant.unpaid_submit_payment')) ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="<?= esc(lang('Entrant.edit')) ?>"><?= esc(lang('Entrant.edit')) ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0"><?= esc(lang('Entrant.no_phase2')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Winning (Phase 3) Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.winning_submissions')) ?> (<?= $phase3_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($phase3)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?= esc(lang('Entrant.design_name')) ?></th>
                                <th><?= esc(lang('Entrant.competition')) ?></th>
                                <th><?= esc(lang('Entrant.status')) ?></th>
                                <th><?= esc(lang('Entrant.payment_phase_2')) ?></th>
                                <th class="text-center"><?= esc(lang('Entrant.edit')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($phase3 as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                $p2 = (string)($e->phase_2_payment ?? 'Unpaid');
                                $isPaid = strcasecmp($p2, 'Paid') === 0;

                                // Optional: only allow edit up to 30 days after jury_phase_2_close
                                $juryClose = (string)($e->jury_phase_2_close ?? '');
                                $allowEdit = false;
                                if ($juryClose !== '') {
                                    $allowEdit = (time() - strtotime($juryClose)) <= (30 * 24 * 3600);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <a class="font-weight-bold" href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td><?= esc($e->entry_status ?? '') ?></td>
                                    <td>
                                        <?php if ($isPaid): ?>
                                            <a
                                                class="btn btn-sm btn-primary shadow-sm js-receipt-modal-link"
                                                href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/3/receipt') ?>"
                                                data-receipt-url="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/3/receipt-content') ?>"
                                                title="<?= esc(lang('Entrant.view_receipt')) ?>"
                                            ><?= esc(lang('Entrant.paid_view_receipt')) ?></a>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-danger shadow-sm"  href="<?= site_url('payments/entry/' . urlencode($entryId) . '/phase/3') ?>" title="<?= esc(lang('Entrant.submit_payment_action')) ?>"><?= esc(lang('Entrant.unpaid_submit_payment')) ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($allowEdit): ?>
                                            <a href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>" class="btn btn-sm btn-primary shadow-sm" title="<?= esc(lang('Entrant.edit')) ?>"><?= esc(lang('Entrant.edit')) ?></a>
                                        <?php else: ?>
                                            <span class="text-muted"><?= esc(lang('Entrant.closed')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0"><?= esc(lang('Entrant.no_phase3')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Submissions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary"><?= esc(lang('Entrant.past_submissions')) ?> (<?= $past_total ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($past)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                            <tr>
                                <th><?= esc(lang('Entrant.design_name')) ?></th>
                                <th><?= esc(lang('Entrant.competition')) ?></th>
                                <th><?= esc(lang('Entrant.status')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($past as $e): ?>
                                <?php
                                $entryId = (string) ($e->entry_id ?? '');
                                $compName = trim((string)($e->comp_type_name ?? '') . ' ' . (string)($e->comp_year ?? ''));
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($entryId !== ''): ?>
                                            <a class="font-weight-bold" href="<?= site_url('submissions/update/' . urlencode($entryId)) ?>"><?= esc($e->design_name ?? '') ?></a>
                                        <?php else: ?>
                                            <?= esc($e->design_name ?? '') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($compName) ?></td>
                                    <td><?= esc($e->entry_status ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="mb-0"><?= esc(lang('Entrant.no_past')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel"><?= esc(lang('Entrant.payment_receipt')) ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="receiptModalBody">
                    <div class="text-muted">Loading receipt...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modalEl = document.getElementById('receiptModal');
            var modalBody = document.getElementById('receiptModalBody');
            var links = document.querySelectorAll('.js-receipt-modal-link');

            var showModal = function (element) {
                if (window.bootstrap && window.bootstrap.Modal) {
                    if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                        window.bootstrap.Modal.getOrCreateInstance(element).show();
                        return;
                    }
                    try {
                        (new window.bootstrap.Modal(element)).show();
                        return;
                    } catch (e) {
                        // fall through
                    }
                }
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                    window.jQuery(element).modal('show');
                }
            };

            var escapeHtml = function (value) {
                var div = document.createElement('div');
                div.textContent = value;
                return div.innerHTML;
            };

            if (!modalEl || !modalBody || links.length === 0) {
                return;
            }

            links.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    var url = this.getAttribute('data-receipt-url') || '';
                    if (!url) {
                        window.location.href = this.getAttribute('href') || '';
                        return;
                    }

                    modalBody.innerHTML = '<div class="text-muted">Loading receipt...</div>';
                    showModal(modalEl);

                    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Unable to load receipt.');
                            }
                            return response.text();
                        })
                        .then(function (html) {
                            modalBody.innerHTML = html;
                        })
                        .catch(function (err) {
                            modalBody.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(err.message || 'Unable to load receipt.') + '</div>';
                        });
                });
            });
        })();
    </script>

<?= $this->endSection() ?>
