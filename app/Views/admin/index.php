<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$money = static fn(float $value): string => '$' . number_format($value, 2);
$oneDecimal = static fn(float $value): string => number_format($value, 1);
?>

<style>
    .dashboard-meta {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .dashboard-metric {
        font-size: 0.92rem;
        color: #5a5c69;
    }

    .chart-canvas-xl {
        position: relative;
        height: 360px;
    }

    .chart-canvas-md {
        position: relative;
        height: 320px;
    }
</style>

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

    <?php if ($msg = session('success')): ?>
        <div class="alert alert-success"><?= esc($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = session('error')): ?>
        <div class="alert alert-danger"><?= esc($msg) ?></div>
    <?php endif; ?>

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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        const charts = <?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?>;
        const hasData = function (values) {
            return Array.isArray(values) && values.some(function (v) { return Number(v) > 0; });
        };
        const showEmpty = function (id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('d-none');
            }
        };

        var entriesCtx = document.getElementById('entriesByCompetitionChart');
        if (entriesCtx) {
            if (hasData(charts.entriesByCompetition.values)) {
                new Chart(entriesCtx, {
                    type: 'pie',
                    data: {
                        labels: charts.entriesByCompetition.labels,
                        datasets: [{
                            data: charts.entriesByCompetition.values,
                            backgroundColor: ['#4e73df', '#1cc88a', '#008080', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#2e59d9']
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: { position: 'bottom' }
                    }
                });
            } else {
                showEmpty('entriesByCompetitionEmpty');
            }
        }

        var statusCtx = document.getElementById('statusBreakdownChart');
        if (statusCtx) {
            if (hasData(charts.statusBreakdown.values)) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: charts.statusBreakdown.labels,
                        datasets: [{
                            data: charts.statusBreakdown.values,
                            backgroundColor: ['#858796', '#008080', '#f6c23e', '#1cc88a']
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: { position: 'bottom' }
                    }
                });
            } else {
                showEmpty('statusBreakdownEmpty');
            }
        }

        var revenueCtx = document.getElementById('revenueTrendChart');
        if (revenueCtx) {
            if (hasData(charts.revenueByYear.values)) {
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: charts.revenueByYear.labels,
                        datasets: [{
                            label: 'Revenue',
                            data: charts.revenueByYear.values,
                            backgroundColor: '#1cc88a',
                            borderColor: '#17a673',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: { display: false },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    callback: function (value) {
                                        return '$' + Number(value).toLocaleString();
                                    }
                                }
                            }]
                        },
                        tooltips: {
                            callbacks: {
                                label: function (tooltipItem) {
                                    return 'Revenue: $' + Number(tooltipItem.yLabel).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    }
                });
            } else {
                showEmpty('revenueTrendEmpty');
            }
        }

        var signupCtx = document.getElementById('signupTrendChart');
        if (signupCtx) {
            if (hasData(charts.signupsByYear.values)) {
                new Chart(signupCtx, {
                    type: 'line',
                    data: {
                        labels: charts.signupsByYear.labels,
                        datasets: [{
                            label: 'New Users',
                            data: charts.signupsByYear.values,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78,115,223,0.12)',
                            fill: true,
                            lineTension: 0.25
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }]
                        }
                    }
                });
            } else {
                showEmpty('signupTrendEmpty');
            }
        }

        var monthCtx = document.getElementById('entriesByMonthChart');
        if (monthCtx) {
            if (hasData(charts.entriesByMonth.values)) {
                new Chart(monthCtx, {
                    type: 'line',
                    data: {
                        labels: charts.entriesByMonth.labels,
                        datasets: [{
                            label: 'Entries',
                            data: charts.entriesByMonth.values,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78,115,223,0.12)',
                            fill: true,
                            lineTension: 0.25
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: { display: false },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }]
                        }
                    }
                });
            } else {
                showEmpty('entriesByMonthEmpty');
            }
        }
    });
</script>

<?= $this->endSection() ?>
