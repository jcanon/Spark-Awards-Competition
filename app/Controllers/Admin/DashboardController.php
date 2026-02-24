<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        if (!session('uid')) {
            return redirect()->to('/auth/login');
        }

        $ctx = service('userctx')->current();
        $user = $ctx['user'] ?? null;

        if (!$user || (!$user->isAdmin() && !$user->isEditor())) {
            return redirect()->to('/home');
        }

        if (!session('2fa_pass')) {
            return redirect()->to('/auth/2fa');
        }

        $db = db_connect();
        $yearRows = $db->table('comp_competitions')
            ->select('DISTINCT comp_year', false)
            ->orderBy('comp_year', 'DESC')
            ->get()
            ->getResultArray();
        $yearOptions = array_values(array_map(static fn(array $r): int => (int)($r['comp_year'] ?? 0), $yearRows));
        $yearOptions = array_values(array_filter($yearOptions, static fn(int $y): bool => $y > 0));
        $yearOptions = array_values(array_unique($yearOptions));

        $latestEntryYearRow = $db->table('comp_entries e')
            ->selectMax('c.comp_year', 'latest_year')
            ->join('comp_competitions c', 'e.comp_id = c.comp_id')
            ->get()
            ->getRowArray();
        $latestEntryYear = (int)($latestEntryYearRow['latest_year'] ?? 0);

        $selectedYear = $latestEntryYear > 0
            ? $latestEntryYear
            : ($yearOptions[0] ?? (int)date('Y'));
        $requestedYear = (int)($this->request->getGet('year') ?? 0);
        if ($requestedYear > 0 && in_array($requestedYear, $yearOptions, true)) {
            $selectedYear = $requestedYear;
        }
        if ($yearOptions === []) {
            $yearOptions = [$selectedYear];
        }

        $entriesByCompetition = [
            'labels' => [],
            'values' => [],
        ];
        $statusBreakdown = [
            'labels' => ['Draft', 'Entrant', 'Finalist', 'Winner'],
            'values' => [0, 0, 0, 0],
        ];
        $entriesByMonth = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'values' => array_fill(0, 12, 0),
        ];

        if ($selectedYear > 0) {
            $byCompRows = $db->table('comp_entries e')
                ->select('t.comp_type_name, COUNT(*) AS total', false)
                ->join('comp_competitions c', 'e.comp_id = c.comp_id')
                ->join('comp_type t', 'c.comp_type_id = t.comp_type_id')
                ->where('c.comp_year', $selectedYear)
                ->groupBy('t.comp_type_name')
                ->orderBy('total', 'DESC')
                ->orderBy('t.comp_type_name', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($byCompRows as $row) {
                $entriesByCompetition['labels'][] = (string)$row['comp_type_name'];
                $entriesByCompetition['values'][] = (int)$row['total'];
            }

            $statusRows = $db->table('comp_entries e')
                ->select('e.entry_status, COUNT(*) AS total', false)
                ->join('comp_competitions c', 'e.comp_id = c.comp_id')
                ->where('c.comp_year', $selectedYear)
                ->groupBy('e.entry_status')
                ->get()
                ->getResultArray();

            $statusMap = [
                'Draft' => 0,
                'Entrant' => 0,
                'Finalist' => 0,
                'Winner' => 0,
            ];
            foreach ($statusRows as $row) {
                $key = ucfirst(strtolower(trim((string)($row['entry_status'] ?? ''))));
                if ($key !== '' && array_key_exists($key, $statusMap)) {
                    $statusMap[$key] = (int)$row['total'];
                }
            }
            $statusBreakdown['values'] = [
                $statusMap['Draft'],
                $statusMap['Entrant'],
                $statusMap['Finalist'],
                $statusMap['Winner'],
            ];
            $monthRows = $db->table('comp_entries e')
                ->select('MONTH(e.date_created) AS m, COUNT(*) AS total', false)
                ->join('comp_competitions c', 'e.comp_id = c.comp_id')
                ->where('c.comp_year', $selectedYear)
                ->where('e.date_created IS NOT NULL', null, false)
                ->groupBy('MONTH(e.date_created)')
                ->get()
                ->getResultArray();
            foreach ($monthRows as $row) {
                $month = (int)($row['m'] ?? 0);
                if ($month >= 1 && $month <= 12) {
                    $entriesByMonth['values'][$month - 1] = (int)$row['total'];
                }
            }
        } else {
            $statusMap = [
                'Draft' => 0,
                'Entrant' => 0,
                'Finalist' => 0,
                'Winner' => 0,
            ];
        }

        $trendYears = 5;
        $startYear = $selectedYear - $trendYears + 1;
        $signupMap = [];
        $revenueMap = [];
        for ($y = $startYear; $y <= $selectedYear; $y++) {
            $signupMap[(string)$y] = 0;
            $revenueMap[(string)$y] = 0.0;
        }
        $signupRows = $db->query(
            "SELECT YEAR(COALESCE(account_created, last_updated)) AS y, COUNT(*) AS total
               FROM comp_users
              WHERE YEAR(COALESCE(account_created, last_updated)) BETWEEN ? AND ?
              GROUP BY YEAR(COALESCE(account_created, last_updated))
              ORDER BY y ASC",
            [$startYear, $selectedYear]
        )->getResultArray();

        foreach ($signupRows as $row) {
            $year = (string)($row['y'] ?? '');
            if (array_key_exists($year, $signupMap)) {
                $signupMap[$year] = (int)($row['total'] ?? 0);
            }
        }

        $revenueRows = $db->query(
            "SELECT COALESCE(YEAR(p.payment_date), c.comp_year) AS y,
                    SUM(CAST(NULLIF(p.payment_total, '') AS DECIMAL(12,2))) AS total
               FROM comp_entry_payments p
               INNER JOIN comp_entries e ON p.entry_id = e.entry_id
               INNER JOIN comp_competitions c ON e.comp_id = c.comp_id
              WHERE p.payment_receipt != ''
                AND COALESCE(YEAR(p.payment_date), c.comp_year) BETWEEN ? AND ?
              GROUP BY COALESCE(YEAR(p.payment_date), c.comp_year)
              ORDER BY y ASC",
            [$startYear, $selectedYear]
        )->getResultArray();

        foreach ($revenueRows as $row) {
            $year = (string)($row['y'] ?? '');
            if (array_key_exists($year, $revenueMap)) {
                $revenueMap[$year] = (float)($row['total'] ?? 0);
            }
        }

        $signupsByYear = [
            'labels' => array_keys($signupMap),
            'values' => array_values($signupMap),
        ];
        $revenueByYear = [
            'labels' => array_keys($revenueMap),
            'values' => array_values($revenueMap),
        ];

        $entriesYear = (int)$db->table('comp_entries e')
            ->join('comp_competitions c', 'e.comp_id = c.comp_id')
            ->where('c.comp_year', $selectedYear)
            ->countAllResults();
        $competitionsYear = (int)$db->table('comp_competitions')
            ->where('comp_year', $selectedYear)
            ->countAllResults();
        $activeUsers = (int)$db->table('comp_users')
            ->where('account_active', 'Yes')
            ->countAllResults();
        $activeCategories = (int)$db->table('comp_type')
            ->where('archived', 0)
            ->countAllResults();
        $newUsersYearRow = $db->query(
            "SELECT COUNT(*) AS total
               FROM comp_users
              WHERE YEAR(COALESCE(account_created, last_updated)) = ?",
            [$selectedYear]
        )->getRowArray();
        $newUsersYear = (int)($newUsersYearRow['total'] ?? 0);

        $paidTransactionsYearRow = $db->query(
            "SELECT COUNT(*) AS total
               FROM comp_entry_payments p
               INNER JOIN comp_entries e ON p.entry_id = e.entry_id
               INNER JOIN comp_competitions c ON e.comp_id = c.comp_id
              WHERE p.payment_receipt != ''
                AND COALESCE(YEAR(p.payment_date), c.comp_year) = ?",
            [$selectedYear]
        )->getRowArray();
        $paidTransactionsYear = (int)($paidTransactionsYearRow['total'] ?? 0);

        $revenueYear = (float)($db->query(
            "SELECT SUM(CAST(NULLIF(p.payment_total, '') AS DECIMAL(12,2))) AS total
               FROM comp_entry_payments p
               INNER JOIN comp_entries e ON p.entry_id = e.entry_id
               INNER JOIN comp_competitions c ON e.comp_id = c.comp_id
              WHERE p.payment_receipt != ''
                AND COALESCE(YEAR(p.payment_date), c.comp_year) = ?",
            [$selectedYear]
        )->getRowArray()['total'] ?? 0);

        $lifetimeRevenue = (float)($db->query(
            "SELECT SUM(CAST(NULLIF(payment_total, '') AS DECIMAL(12,2))) AS total
               FROM comp_entry_payments
              WHERE payment_receipt != ''"
        )->getRowArray()['total'] ?? 0);

        $paidEntriesYearRow = $db->query(
            "SELECT COUNT(DISTINCT e.entry_id) AS total
               FROM comp_entries e
               INNER JOIN comp_competitions c ON e.comp_id = c.comp_id
               INNER JOIN comp_entry_payments p ON e.entry_id = p.entry_id
              WHERE c.comp_year = ?
                AND p.payment_receipt != ''",
            [$selectedYear]
        )->getRowArray();
        $paidEntriesYear = (int)($paidEntriesYearRow['total'] ?? 0);

        $stats = [
            'totalUsers' => (int)$db->table('comp_users')->countAllResults(),
            'totalEntries' => (int)$db->table('comp_entries')->countAllResults(),
            'totalCompetitions' => (int)$db->table('comp_competitions')->countAllResults(),
            'totalPaidTransactions' => (int)$db->table('comp_entry_payments')->where('payment_receipt !=', '')->countAllResults(),
            'activeUsers' => $activeUsers,
            'newUsersYear' => $newUsersYear,
            'entriesYear' => $entriesYear,
            'competitionsYear' => $competitionsYear,
            'activeCategories' => $activeCategories,
            'paidTransactionsYear' => $paidTransactionsYear,
            'revenueYear' => $revenueYear,
            'lifetimeRevenue' => $lifetimeRevenue,
            'avgEntriesPerCompetitionYear' => $competitionsYear > 0 ? ($entriesYear / $competitionsYear) : 0.0,
            'avgRevenuePerTransactionYear' => $paidTransactionsYear > 0 ? ($revenueYear / $paidTransactionsYear) : 0.0,
            'paidEntryRateYear' => $entriesYear > 0 ? (($paidEntriesYear / $entriesYear) * 100) : 0.0,
            'draftYear' => $statusMap['Draft'],
            'entrantYear' => $statusMap['Entrant'],
            'finalistsYear' => $statusMap['Finalist'],
            'winnersYear' => $statusMap['Winner'],
            'pendingReviewYear' => $statusMap['Draft'] + $statusMap['Entrant'],
        ];

        return view('admin/index', [
            'user' => $user,
            'selectedYear' => $selectedYear,
            'yearOptions' => $yearOptions,
            'trendYears' => $trendYears,
            'stats' => $stats,
            'charts' => [
                'entriesByCompetition' => $entriesByCompetition,
                'signupsByYear' => $signupsByYear,
                'statusBreakdown' => $statusBreakdown,
                'revenueByYear' => $revenueByYear,
                'entriesByMonth' => $entriesByMonth,
            ],
        ]);
    }
}
