<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .status-pill .medal-icon {
        font-size: 0.82rem;
    }

    .status-draft {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #374151;
    }

    .status-entrant {
        background: #e8f3ff;
        border-color: #bcd8ff;
        color: #0f4c81;
    }

    .status-finalist {
        background: #fff8e5;
        border-color: #ffd67a;
        color: #7a4a00;
    }

    .status-winner {
        background: #f8f4ff;
        border-color: #d9c7ff;
        color: #4b2b8a;
    }

    .winner-platinum {
        background: #f4f7fb;
        border-color: #c9d4e5;
        color: #2f425b;
    }

    .winner-gold {
        background: #fff6de;
        border-color: #f2ce6a;
        color: #7d5600;
    }

    .winner-silver {
        background: #f4f6f8;
        border-color: #cfd5dd;
        color: #4b5563;
    }

    .winner-bronze {
        background: #f8eee7;
        border-color: #d8ae8e;
        color: #764a2f;
    }
</style>

<div class="container-fluid">
    <?php
    $queryBase = [
        'phase' => (string)$phase,
    ];
    if (!empty($fullList)) {
        $queryBase['all'] = '1';
    }
    $applicationsUrl = site_url('judging/entries/competition/' . (int)$compId . '/status/' . rawurlencode((string)$status)) . '?' . http_build_query(array_merge($queryBase, ['view' => 'applications']));
    $scoreUrl = site_url('judging/entries/competition/' . (int)$compId . '/status/' . rawurlencode((string)$status)) . '?' . http_build_query(array_merge($queryBase, ['view' => 'score']));
    $currentView = (string)($viewMode ?? 'applications');
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">Judging Entries</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('judging') ?>">Back to Competitions</a>
    </div>
    <?php if (!empty($judgingContextLabel ?? null)): ?>
        <p class="mb-1 text-muted"><?= esc((string)$judgingContextLabel) ?></p>
    <?php endif; ?>
    <?php if (!empty($competitionLabel ?? null)): ?>
        <p class="mb-3 font-weight-bold text-primary"><?= esc((string)$competitionLabel) ?></p>
    <?php endif; ?>

    <?php if (!empty($fallbackNotice ?? null)): ?>
        <div class="alert alert-warning"><?= esc((string)($fallbackNotice ?? '')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link<?= $currentView === 'applications' ? ' active' : '' ?>" href="<?= esc($applicationsUrl) ?>">Entries List</a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $currentView === 'score' ? ' active' : '' ?>" href="<?= esc($scoreUrl) ?>">Score Review</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover datatable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Entry Name</th>
                    <th>Status</th>
                    <th>Score</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $i => $row): ?>
                    <?php
                        $statusLabel = (string)($row['entry_status'] ?? '');
                        $statusClass = 'status-draft';
                        $medalIconClass = '';
                        $winnerLevel = trim((string)($row['winner_level_name'] ?? ''));

                        if ($statusLabel === 'Winner' && $winnerLevel !== '') {
                            $statusLabel .= ': ' . $winnerLevel;
                        }

                        if ($statusLabel === 'Entrant') {
                            $statusClass = 'status-entrant';
                        } elseif ($statusLabel === 'Finalist') {
                            $statusClass = 'status-finalist';
                        } elseif (str_starts_with($statusLabel, 'Winner')) {
                            $statusClass = 'status-winner';
                            if (strcasecmp($winnerLevel, 'Platinum') === 0) {
                                $statusClass = 'winner-platinum';
                                $medalIconClass = 'fas fa-medal';
                            } elseif (strcasecmp($winnerLevel, 'Gold') === 0) {
                                $statusClass = 'winner-gold';
                                $medalIconClass = 'fas fa-medal';
                            } elseif (strcasecmp($winnerLevel, 'Silver') === 0) {
                                $statusClass = 'winner-silver';
                                $medalIconClass = 'fas fa-medal';
                            } elseif (strcasecmp($winnerLevel, 'Bronze') === 0) {
                                $statusClass = 'winner-bronze';
                                $medalIconClass = 'fas fa-medal';
                            }
                        }
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="font-weight-bold" href="<?= site_url('judging/entry/' . rawurlencode((string)$row['entry_id']) . '/status/' . rawurlencode($status) . '?phase=' . rawurlencode((string)$phase) . '&comp=' . (int)($row['comp_id'] ?? $compId) . ($fullList ? '&all=1' : '') . '&view=' . rawurlencode($currentView)) ?>">
                                <?= esc((string)$row['design_name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="status-pill <?= esc($statusClass) ?>">
                                <?php if ($medalIconClass !== ''): ?>
                                    <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= esc($statusLabel) ?>
                            </span>
                        </td>
                        <td><?= esc((string)($row['_my_score'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
