(function () {
    var initialized = false;

    var init = function () {
        if (initialized || typeof window.Chart === 'undefined') {
            return;
        }

        var chartsDataEl = document.getElementById('adminDashboardChartsData');
        if (!chartsDataEl) {
            return;
        }

        var charts = {};
        try {
            charts = JSON.parse(chartsDataEl.getAttribute('data-charts') || '{}');
        } catch (error) {
            charts = {};
        }
        var hasData = function (values) {
            return Array.isArray(values) && values.some(function (v) { return Number(v) > 0; });
        };
        var showEmpty = function (id) {
            var el = document.getElementById(id);
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
                    options: { maintainAspectRatio: false, legend: { position: 'bottom' } }
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
                        datasets: [{ data: charts.statusBreakdown.values, backgroundColor: ['#858796', '#008080', '#f6c23e', '#1cc88a'] }]
                    },
                    options: { maintainAspectRatio: false, legend: { position: 'bottom' } }
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
                        datasets: [{ label: 'Revenue', data: charts.revenueByYear.values, backgroundColor: '#1cc88a', borderColor: '#17a673', borderWidth: 1 }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: { display: false },
                        scales: { yAxes: [{ ticks: { beginAtZero: true, callback: function (v) { return '$' + Number(v).toLocaleString(); } } }] },
                        tooltips: { callbacks: { label: function (t) { return 'Revenue: $' + Number(t.yLabel).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } } }
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
                        datasets: [{ label: 'New Users', data: charts.signupsByYear.values, borderColor: '#4e73df', backgroundColor: 'rgba(78,115,223,0.12)', fill: true, lineTension: 0.25 }]
                    },
                    options: { maintainAspectRatio: false, scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] } }
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
                        datasets: [{ label: 'Entries', data: charts.entriesByMonth.values, borderColor: '#4e73df', backgroundColor: 'rgba(78,115,223,0.12)', fill: true, lineTension: 0.25 }]
                    },
                    options: { maintainAspectRatio: false, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] } }
                });
            } else {
                showEmpty('entriesByMonthEmpty');
            }
        }

        initialized = true;
    };

    if (document.readyState === 'complete') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
        window.addEventListener('load', init);
        setTimeout(init, 0);
    }
})();
