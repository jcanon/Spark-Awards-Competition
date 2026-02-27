<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Spark Certificates</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p>Spark produces customized certificates, with design name, designers, organization and category—exclusively for Spark Entrants, Finalists and Winners. They are a great gift for your clients, design team, professors—or to yourself for a job well done!</p>
            <p>These are available for any year of Spark, from our beginning in 2007 to present. There’s production and shipping charges for the printed gold foil version—however, a free PDF version can be found and downloaded from the <a href="https://galleries.sparkawards.com/" target="_blank">Entrant’s Gallery</a> page. <em>(30 days after winner announcement)</em></p>
            <p>The gold-foil Spark Certificate is a paper certificate. It is customized to the winner and available in any quantity. They are $125 each, plus shipping/handling, which depends on distance, beginning at $20 to USA, $100 International. Usually multiple certificates can be shipped with one shipping charge. All certificate charges must be paid for in advance of production. Payment will be by Paypal or bank wire <em>(with their usual small service charge)</em>.</p>
            <p class="mb-0">Lamination is available at extra charge, upon request. <em>(Recommended for extended durability)</em></p>
        </div>
    </div>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                    <tr>
                        <th>Submission Name</th>
                        <th>Competition Type</th>
                        <th>Year</th>
                        <th>Entry Status</th>
                        <th>Request Status</th>
                        <th>Request Certificate</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $entryId = (string)($row['entry_id'] ?? '');
                        [$status, $statusClass, $medalIconClass] = entry_status_pill($row);
                        $judgingClosedAt = trim((string)($row['jury_phase_2_close'] ?? ''));
                        $isAvailable = $judgingClosedAt !== '' && $judgingClosedAt <= date('Y-m-d H:i:s');
                        [$requestLabel, $requestPillClass, $requestKey] = certificate_request_status_pill((string)($row['request_status'] ?? ''), false);
                        $isPendingRequest = $requestKey === 'pending';
                    ?>
                    <tr>
                        <td><?= esc((string)($row['design_name'] ?? '')) ?></td>
                        <td><?= esc((string)($row['comp_type_name'] ?? '')) ?></td>
                        <td><?= (int)($row['comp_year'] ?? 0) ?></td>
                        <td>
                            <span class="status-pill <?= esc($statusClass) ?>">
                                <?php if ($medalIconClass !== ''): ?>
                                    <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= esc($status) ?>
                            </span>
                        </td>
                        <td><span class="request-pill <?= esc($requestPillClass) ?>"><?= esc($requestLabel) ?></span></td>
                        <td>
                            <?php if ($isAvailable): ?>
                                <a class="btn btn-sm btn-info" href="<?= site_url('certificates/request/' . rawurlencode($entryId)) ?>">
                                    <?= $requestStatus === '' ? 'Request Certificate' : ($isPendingRequest ? 'Update Request' : 'View Request') ?>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" type="button" disabled>Locked</button>
                                <div class="small text-muted mt-1">Available after judging closes<?= $judgingClosedAt !== '' ? ' (' . esc(format_datetime_ui($judgingClosedAt)) . ')' : '' ?>.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
