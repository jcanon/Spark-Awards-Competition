<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$money = static fn(float $value): string => '$' . number_format($value, 2);
$oneDecimal = static fn(float $value): string => number_format($value, 1);
?>
<link href="/css/pages/admin-index.css" rel="stylesheet">

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
        <form method="get" class="form-inline mt-3 mt-sm-0">
            <label class="mr-2 text-muted small" for="dashboardYear">Year</label>
            <select class="form-control form-control-sm mr-2" id="dashboardYear" name="year">
                <?php foreach ($yearOptions as $year): ?>
                    <option value="<?= (int)$year ?>" <?= (int)$year === (int)$selectedYear ? 'selected' : '' ?>>
                        <?= (int)$year ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
    <p class="text-muted small mb-4">Dashboard metrics below are focused on <?= (int)$selectedYear ?>, with <?= (int)$trendYears ?>-year trend charts for context.</p>

    <?= view('partials/flash') ?>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Users</div>
                    <div class="h5 mb-1 font-weight-bold text-gray-800"><?= (int)$stats['totalUsers'] ?> Total</div>
                    <div class="dashboard-metric">Active: <strong><?= (int)$stats['activeUsers'] ?></strong></div>
                    <div class="dashboard-meta">New in <?= (int)$selectedYear ?>: <?= (int)$stats['newUsersYear'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Entries</div>
                    <div class="h5 mb-1 font-weight-bold text-gray-800"><?= (int)$stats['entriesYear'] ?> In <?= (int)$selectedYear ?></div>
                    <div class="dashboard-metric">Finalists + Winners: <strong><?= (int)$stats['finalistsYear'] + (int)$stats['winnersYear'] ?></strong></div>
                    <div class="dashboard-meta">Paid-entry rate: <?= $oneDecimal((float)$stats['paidEntryRateYear']) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Revenue</div>
                    <div class="h5 mb-1 font-weight-bold text-gray-800"><?= $money((float)$stats['revenueYear']) ?> In <?= (int)$selectedYear ?></div>
                    <div class="dashboard-metric">Paid transactions: <strong><?= (int)$stats['paidTransactionsYear'] ?></strong></div>
                    <div class="dashboard-meta">Lifetime revenue: <?= $money((float)$stats['lifetimeRevenue']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Competitions</div>
                    <div class="h5 mb-1 font-weight-bold text-gray-800"><?= (int)$stats['competitionsYear'] ?> In <?= (int)$selectedYear ?></div>
                    <div class="dashboard-metric">Active categories: <strong><?= (int)$stats['activeCategories'] ?></strong></div>
                    <div class="dashboard-meta">Avg entries/competition: <?= $oneDecimal((float)$stats['avgEntriesPerCompetitionYear']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Entries By Competition Category (<?= (int)$selectedYear ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-xl">
                        <canvas id="entriesByCompetitionChart"></canvas>
                    </div>
                    <p class="small text-muted mt-3 mb-0 d-none" id="entriesByCompetitionEmpty">No entry data available for charting.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Submission Status Breakdown (<?= (int)$selectedYear ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-xl">
                        <canvas id="statusBreakdownChart"></canvas>
                    </div>
                    <p class="small text-muted mt-3 mb-0 d-none" id="statusBreakdownEmpty">No status data available for charting.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Trend (Last <?= (int)$trendYears ?> Years)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-md">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                    <p class="small text-muted mt-3 mb-0 d-none" id="revenueTrendEmpty">No revenue data available for charting.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">New User Signups (Last <?= (int)$trendYears ?> Years)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-md">
                        <canvas id="signupTrendChart"></canvas>
                    </div>
                    <p class="small text-muted mt-3 mb-0 d-none" id="signupTrendEmpty">No signup data available for charting.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Entries By Month (<?= (int)$selectedYear ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-md">
                        <canvas id="entriesByMonthChart"></canvas>
                    </div>
                    <p class="small text-muted mt-3 mb-0 d-none" id="entriesByMonthEmpty">No monthly entry activity available for charting.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Year Snapshot (<?= (int)$selectedYear ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">Draft</div>
                            <div class="h4 mb-0"><?= (int)$stats['draftYear'] ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-muted">Entrant</div>
                            <div class="h4 mb-0"><?= (int)$stats['entrantYear'] ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-xs text-uppercase text-muted">Finalist</div>
                            <div class="h4 mb-0"><?= (int)$stats['finalistsYear'] ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-xs text-uppercase text-muted">Winner</div>
                            <div class="h4 mb-0"><?= (int)$stats['winnersYear'] ?></div>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-1"><strong>Pending review:</strong> <?= (int)$stats['pendingReviewYear'] ?> submissions</p>
                    <p class="mb-0"><strong>Average revenue / paid transaction:</strong> <?= $money((float)$stats['avgRevenuePerTransactionYear']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="adminDashboardChartsData" class="d-none" data-charts="<?= esc(json_encode($charts, JSON_UNESCAPED_UNICODE), 'attr') ?>"></div>
<script src="/js/pages/admin-index.js"></script>

<?= $this->endSection() ?>

