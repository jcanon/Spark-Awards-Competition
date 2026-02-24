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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Competition Score Results</h1>
        <a
            class="btn btn-sm btn-primary"
            href="<?= site_url('admin/score-results/export?' . http_build_query($filters)) ?>"
            target="_blank"
            rel="noopener"
        >Export Score Results</a>
    </div>
    <p>This tool will allow you to tally the scores of each competition during the judging process.</p>

    <?php if ($msg = session('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = session('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/score-results') ?>" class="form-inline mb-3">
                <label class="mr-2" for="year">Year</label>
                <select id="year" name="year" class="form-control mr-2">
                    <?php foreach ($years as $year): ?>
                        <option value="<?= (int)$year['comp_year'] ?>" <?= (int)$filters['year'] === (int)$year['comp_year'] ? 'selected' : '' ?>><?= (int)$year['comp_year'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Change Year</button>
            </form>

            <form method="get" action="<?= site_url('admin/score-results') ?>" class="form-row">
                <input type="hidden" name="year" value="<?= (int)$filters['year'] ?>">
                <div class="col-md-2 mb-2">
                    <label for="phase">Phase</label>
                    <select id="phase" name="phase" class="form-control">
                        <option value="1" <?= $filters['phase'] === '1' ? 'selected' : '' ?>>Phase 1</option>
                        <option value="2" <?= $filters['phase'] === '2' ? 'selected' : '' ?>>Phase 2</option>
                        <option value="AllSpark" <?= $filters['phase'] === 'AllSpark' ? 'selected' : '' ?>>AllSpark</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="type">Competition Type</label>
                    <select id="type" name="type" class="form-control">
                        <option value="0" <?= (int)$filters['type'] === 0 ? 'selected' : '' ?>>All</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= (int)$type['comp_type_id'] ?>" <?= (int)$filters['type'] === (int)$type['comp_type_id'] ? 'selected' : '' ?>><?= esc($type['comp_type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="userType">User Type</label>
                    <select id="userType" name="userType" class="form-control">
                        <option value="student" <?= strtolower((string)$filters['userType']) === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="pro" <?= strtolower((string)$filters['userType']) === 'pro' ? 'selected' : '' ?>>Pro</option>
                        <option value="all" <?= strtolower((string)$filters['userType']) === 'all' ? 'selected' : '' ?>>All</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status">Entry Status</label>
                    <?php $status = (string)$filters['status']; ?>
                    <select id="status" name="status" class="form-control">
                        <option value="All" <?= strcasecmp($status, 'All') === 0 ? 'selected' : '' ?>>All</option>
                        <option value="Winner" <?= strcasecmp($status, 'Winner') === 0 ? 'selected' : '' ?>>Winner</option>
                        <option value="Finalist" <?= strcasecmp($status, 'Finalist') === 0 ? 'selected' : '' ?>>Finalist</option>
                        <option value="Entrant" <?= strcasecmp($status, 'Entrant') === 0 ? 'selected' : '' ?>>Entrant</option>
                        <option value="Draft" <?= strcasecmp($status, 'Draft') === 0 ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="shortlist">Shortlist?</label>
                    <?php $shortlist = (string)$filters['shortlist']; ?>
                    <select id="shortlist" name="shortlist" class="form-control">
                        <option value="All" <?= strcasecmp($shortlist, 'All') === 0 ? 'selected' : '' ?>>All</option>
                        <option value="Yes" <?= strcasecmp($shortlist, 'Yes') === 0 ? 'selected' : '' ?>>Yes</option>
                        <option value="No" <?= strcasecmp($shortlist, 'No') === 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit">Tally Scores</button>
                </div>
            </form>
        </div>
    </div>

    <form id="bulk_score_results_form" action="<?= site_url('admin/score-results/bulk') ?>" method="post">
        <?= csrf_field() ?>
        <div class="card mb-3">
            <div class="card-body form-inline">
                <label for="action" class="mr-2">Bulk update selected entries:</label>
                <select name="action" id="action" class="form-control mr-2">
                    <option value="">Select an option</option>
                    <option value="">---</option>
                    <option value="shortlist_add">Add to Shortlist</option>
                    <option value="shortlist_remove">Remove from Shortlist</option>
                    <option value="non_finalist">Change Status - Non-Finalist</option>
                    <option value="finalist">Change Status - Finalist</option>
                    <option value="winner_platinum">Change Status - Winner: Platinum</option>
                    <option value="winner_gold">Change Status - Winner: Gold</option>
                    <option value="winner_silver">Change Status - Winner: Silver</option>
                    <option value="winner_bronze">Change Status - Winner: Bronze</option>
                    <?php if ($canDelete): ?>
                        <option value="">---</option>
                        <option value="delete">DELETE - This CANNOT be undone!</option>
                    <?php endif; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Update</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover datatable">
                    <thead><tr><th><input type="checkbox" id="select_all"></th><th>Entry</th><th>Status</th><th>Shortlist</th><th>Competition</th><th>Score</th><th>Judges</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $statusLabel = (string)($row['entry_status'] ?? '');
                            $statusClass = 'status-draft';
                            $medalIconClass = '';

                            $winnerLevel = trim((string)($row['winner_level_name'] ?? ''));
                            if ($statusLabel === 'Winner' && !empty($row['winner_level_name'])) {
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
                            <td><input type="checkbox" name="entry_ids[]" value="<?= esc((string)$row['entry_id']) ?>"></td>
                            <td><a href="https://galleries.sparkawards.com/index.cfm?entry=<?= rawurlencode((string)$row['entry_id']) ?>" target="_blank" rel="noopener"><strong><?= esc((string)$row['design_name']) ?></strong></a></td>
                            <td>
                                <span class="status-pill <?= esc($statusClass) ?>">
                                    <?php if ($medalIconClass !== ''): ?>
                                        <i class="<?= esc($medalIconClass) ?> medal-icon" aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <?= esc($statusLabel) ?>
                                </span>
                            </td>
                            <td><?= ((int)($row['shortlist'] ?? 0) === 1) ? 'Yes' : 'No' ?></td>
                            <td><?= esc((string)$row['comp_type_name']) ?></td>
                            <td><?= (int)($row['total_score'] ?? 0) ?></td>
                            <td><?= (int)($row['total_judges'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        const all = document.getElementById('select_all');
        if (all) {
            all.addEventListener('change', function () {
                document.querySelectorAll('input[name="entry_ids[]"]').forEach(function (el) {
                    el.checked = all.checked;
                });
            });
        }

        const bulkForm = document.getElementById('bulk_score_results_form');
        const action = document.getElementById('action');
        if (bulkForm && action) {
            bulkForm.addEventListener('submit', function (e) {
                if (action.value === 'delete') {
                    const ok = confirm('Delete selected score result entries? This cannot be undone.');
                    if (!ok) {
                        e.preventDefault();
                    }
                }
            });
        }
    })();
</script>

<?= $this->endSection() ?>
