<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Judging</h1>
    </div>

    <?php if ($msg = session('success')): ?>
        <div class="alert alert-success"><?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = session('error')): ?>
        <div class="alert alert-danger"><?= esc($msg) ?></div>
    <?php endif; ?>

    <?php
    $phase2SeedCompId = 0;
    if (!empty($openPhase2) && !empty($openPhase2[0]['comp_id'])) {
        $phase2SeedCompId = (int)$openPhase2[0]['comp_id'];
    } elseif (!empty($upcomingPhase2) && !empty($upcomingPhase2[0]['comp_id'])) {
        $phase2SeedCompId = (int)$upcomingPhase2[0]['comp_id'];
    }
    $phase2AllEntriesUrl = $phase2SeedCompId > 0
        ? site_url('judging/entries/competition/' . $phase2SeedCompId . '/status/Finalist?phase=2&all=1')
        : null;
    ?>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Open Phase 1 Entrant Judging</h6></div>
        <div class="card-body table-responsive">
            <?php if (empty($openPhase1)): ?>
                <p class="mb-0">There are currently no open phase 1 competitions at this time.</p>
            <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead><tr><th>Competition</th><th>Entries</th><th>My Votes</th><th>Closes</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($openPhase1 as $row): ?>
                    <?php $entries = service('judging')->judgingEntries((int)$row['comp_id'], 'Entrant'); ?>
                    <?php $entryIds = array_values(array_map(static fn (array $entry): string => (string)($entry['entry_id'] ?? ''), $entries)); ?>
                    <?php $scoreMap = service('judging')->judgingScoresByEntryIds($entryIds, '1'); ?>
                    <?php $scoredCount = 0; ?>
                    <?php foreach ($entryIds as $entryId): ?>
                        <?php if (array_key_exists($entryId, $scoreMap) && $scoreMap[$entryId] !== null) { $scoredCount++; } ?>
                    <?php endforeach; ?>
                    <?php $voteUrl = site_url('judging/entries/competition/' . (int)$row['comp_id'] . '/status/Entrant?phase=1'); ?>
                    <tr>
                        <td><a class="font-weight-bold" href="<?= $voteUrl ?>">SPARK:<?= esc(strtoupper((string)$row['comp_type_name'])) ?> <?= esc((string)$row['comp_year']) ?></a></td>
                        <td><?= count($entries) ?></td>
                        <td><?= (int)$scoredCount ?> out of <?= count($entries) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row['jury_phase_1_close'])) ?></td>
                        <td class="text-center"><a class="btn btn-sm btn-primary" href="<?= $voteUrl ?>">Vote Now</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Upcoming Phase 1 Entrant Judging</h6></div>
        <div class="card-body table-responsive">
            <?php if (empty($upcomingPhase1)): ?>
                <p class="mb-0">There are currently no upcoming phase 1 competitions at this time.</p>
            <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead><tr><th>Competition</th><th>Entries</th><th>My Votes</th><th>Opens</th></tr></thead>
                <tbody>
                <?php foreach ($upcomingPhase1 as $row): ?>
                    <?php $entries = service('judging')->judgingEntries((int)$row['comp_id'], 'Entrant'); ?>
                    <?php $entryIds = array_values(array_map(static fn (array $entry): string => (string)($entry['entry_id'] ?? ''), $entries)); ?>
                    <?php $scoreMap = service('judging')->judgingScoresByEntryIds($entryIds, '1'); ?>
                    <?php $scoredCount = 0; ?>
                    <?php foreach ($entryIds as $entryId): ?>
                        <?php if (array_key_exists($entryId, $scoreMap) && $scoreMap[$entryId] !== null) { $scoredCount++; } ?>
                    <?php endforeach; ?>
                    <?php $voteUrl = site_url('judging/entries/competition/' . (int)$row['comp_id'] . '/status/Entrant?phase=1'); ?>
                    <tr>
                        <td><a class="font-weight-bold" href="<?= $voteUrl ?>">SPARK:<?= esc(strtoupper((string)$row['comp_type_name'])) ?> <?= esc((string)$row['comp_year']) ?></a></td>
                        <td><?= count($entries) ?></td>
                        <td><?= (int)$scoredCount ?> out of <?= count($entries) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row['jury_phase_1_open'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Open Phase 2 Finalist Judging</h6></div>
        <div class="card-body table-responsive">
            <?php if (empty($openPhase2)): ?>
                <p class="mb-0">There are currently no open phase 2 competitions at this time.</p>
            <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead><tr><th>Competition</th><th>Entries</th><th>My Votes</th><th>Closes</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($openPhase2 as $row): ?>
                    <?php $entries = service('judging')->judgingEntries((int)$row['comp_id'], 'Finalist'); ?>
                    <?php $entryIds = array_values(array_map(static fn (array $entry): string => (string)($entry['entry_id'] ?? ''), $entries)); ?>
                    <?php $scoreMap = service('judging')->judgingScoresByEntryIds($entryIds, '2'); ?>
                    <?php $scoredCount = 0; ?>
                    <?php foreach ($entryIds as $entryId): ?>
                        <?php if (array_key_exists($entryId, $scoreMap) && $scoreMap[$entryId] !== null) { $scoredCount++; } ?>
                    <?php endforeach; ?>
                    <?php $voteUrl = site_url('judging/entries/competition/' . (int)$row['comp_id'] . '/status/Finalist?phase=2'); ?>
                    <tr>
                        <td><a class="font-weight-bold" href="<?= $voteUrl ?>">SPARK:<?= esc(strtoupper((string)$row['comp_type_name'])) ?> <?= esc((string)$row['comp_year']) ?></a></td>
                        <td><?= count($entries) ?></td>
                        <td><?= (int)$scoredCount ?> out of <?= count($entries) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row['jury_phase_2_close'])) ?></td>
                        <td class="text-center"><a class="btn btn-sm btn-primary" href="<?= $voteUrl ?>">Vote Now</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php if ($phase2AllEntriesUrl !== null): ?>
                <p class="mb-0 mt-2"><a class="font-weight-bold" href="<?= esc($phase2AllEntriesUrl) ?>">SPARK: ALL ENTRIES LIST &raquo;</a></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Open Phase 2 Shortlist Judging</h6></div>
        <div class="card-body table-responsive">
            <?php if (empty($openPhase2Short)): ?>
                <p class="mb-0">There are currently no open phase 2 shortlist competitions at this time.</p>
            <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead><tr><th>Competition</th><th>Entries</th><th>My Votes</th><th>Closes</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($openPhase2Short as $row): ?>
                    <?php $entries = service('judging')->judgingEntries((int)$row['comp_id'], 'Finalist'); ?>
                    <?php $entryIds = array_values(array_map(static fn (array $entry): string => (string)($entry['entry_id'] ?? ''), $entries)); ?>
                    <?php $scoreMap = service('judging')->judgingScoresByEntryIds($entryIds, '2'); ?>
                    <?php $scoredCount = 0; ?>
                    <?php foreach ($entryIds as $entryId): ?>
                        <?php if (array_key_exists($entryId, $scoreMap) && $scoreMap[$entryId] !== null) { $scoredCount++; } ?>
                    <?php endforeach; ?>
                    <?php $voteUrl = site_url('judging/entries/competition/' . (int)$row['comp_id'] . '/status/Finalist?phase=2'); ?>
                    <tr>
                        <td><a class="font-weight-bold" href="<?= $voteUrl ?>">SPARK:<?= esc(strtoupper((string)$row['comp_type_name'])) ?> <?= esc((string)$row['comp_year']) ?></a></td>
                        <td><?= count($entries) ?></td>
                        <td><?= (int)$scoredCount ?> out of <?= count($entries) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row['jury_phase_2_close'])) ?></td>
                        <td class="text-center"><a class="btn btn-sm btn-primary" href="<?= $voteUrl ?>">Vote Now</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Upcoming Phase 2 Finalist Judging</h6></div>
        <div class="card-body table-responsive">
            <?php if (empty($upcomingPhase2)): ?>
                <p class="mb-0">There are currently no upcoming phase 2 competitions at this time.</p>
            <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead><tr><th>Competition</th><th>Entries</th><th>My Votes</th><th>Opens</th></tr></thead>
                <tbody>
                <?php foreach ($upcomingPhase2 as $row): ?>
                    <?php $entries = service('judging')->judgingEntries((int)$row['comp_id'], 'Finalist'); ?>
                    <?php $entryIds = array_values(array_map(static fn (array $entry): string => (string)($entry['entry_id'] ?? ''), $entries)); ?>
                    <?php $scoreMap = service('judging')->judgingScoresByEntryIds($entryIds, '2'); ?>
                    <?php $scoredCount = 0; ?>
                    <?php foreach ($entryIds as $entryId): ?>
                        <?php if (array_key_exists($entryId, $scoreMap) && $scoreMap[$entryId] !== null) { $scoredCount++; } ?>
                    <?php endforeach; ?>
                    <?php $voteUrl = site_url('judging/entries/competition/' . (int)$row['comp_id'] . '/status/Finalist?phase=2'); ?>
                    <tr>
                        <td><a class="font-weight-bold" href="<?= $voteUrl ?>">SPARK:<?= esc(strtoupper((string)$row['comp_type_name'])) ?> <?= esc((string)$row['comp_year']) ?></a></td>
                        <td><?= count($entries) ?></td>
                        <td><?= (int)$scoredCount ?> out of <?= count($entries) ?></td>
                        <td><?= esc(format_datetime_ui((string)$row['jury_phase_2_open'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php if ($phase2AllEntriesUrl !== null): ?>
                <p class="mb-0 mt-2"><a class="font-weight-bold" href="<?= esc($phase2AllEntriesUrl) ?>">SPARK: ALL ENTRIES LIST &raquo;</a></p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
