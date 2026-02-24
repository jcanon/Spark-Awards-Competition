<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\SubmissionsAdminService;

class ScoreResultsController extends BaseController
{
    public function index()
    {
        $filters = $this->filters();
        $rows = service('judging')->getScoreResults(
            $filters['year'],
            $filters['type'],
            $filters['phase'],
            $filters['userType'],
            $filters['status'],
            $filters['shortlist']
        );

        $entryIds = array_map(static fn (array $row): string => (string)($row['entry_id'] ?? ''), $rows);
        $tallies = service('judging')->tallyScoresByEntryIds($entryIds, $filters['phase']);
        foreach ($rows as &$row) {
            $entryId = (string)($row['entry_id'] ?? '');
            $row['total_score'] = (int)($tallies[$entryId]['total_score'] ?? 0);
            $row['total_judges'] = (int)($tallies[$entryId]['total_judges'] ?? 0);
        }
        unset($row);

        return view('admin/score_results/index', [
            'rows' => $rows,
            'filters' => $filters,
            'years' => $this->years(),
            'types' => $this->typesByYear($filters['year']),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function bulk()
    {
        $action = (string)$this->request->getPost('action');
        $ids = $this->request->getPost('entry_ids') ?? [];
        if (!is_array($ids) || $ids === []) {
            return redirect()->back()->with('error', 'No entries selected.');
        }
        if ($action === '') {
            return redirect()->back()->with('error', 'Select a bulk action first.');
        }

        if ($action === 'delete' && !$this->canDelete()) {
            return redirect()->back()->with('error', 'Editors are not allowed to delete records.');
        }

        $updated = 0;
        $adminSubs = new SubmissionsAdminService();
        foreach ($ids as $id) {
            $entryId = (string)$id;
            if ($entryId === '') {
                continue;
            }

            if ($action === 'shortlist_add') {
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['shortlist' => 1]);
                $updated++;
            } elseif ($action === 'shortlist_remove') {
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['shortlist' => 0]);
                $updated++;
            } elseif ($action === 'non_finalist') {
                service('judging')->updateStatus($entryId, 'Entrant');
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['entry_non_finalist' => 'Yes']);
                $updated++;
            } elseif ($action === 'finalist') {
                service('judging')->updateStatus($entryId, 'Finalist');
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['entry_non_finalist' => 'No']);
                $updated++;
            } elseif ($action === 'winner_platinum') {
                service('judging')->updateStatus($entryId, 'Winner');
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['winner_level' => 1]);
                $updated++;
            } elseif ($action === 'winner_gold') {
                service('judging')->updateStatus($entryId, 'Winner');
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['winner_level' => 2]);
                $updated++;
            } elseif ($action === 'winner_silver') {
                service('judging')->updateStatus($entryId, 'Winner');
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['winner_level' => 3]);
                $updated++;
            } elseif ($action === 'winner_bronze') {
                service('judging')->updateStatus($entryId, 'Winner');
                db_connect()->table('comp_entries')->where('entry_id', $entryId)->update(['winner_level' => 4]);
                $updated++;
            } elseif ($action === 'delete') {
                if ($adminSubs->delete($entryId)) {
                    $updated++;
                }
            }
        }

        return redirect()->back()->with('success', 'Updated ' . $updated . ' record(s).');
    }

    public function export()
    {
        $filters = $this->filters();
        $rows = service('judging')->getScoreResults(
            $filters['year'],
            $filters['type'],
            $filters['phase'],
            $filters['userType'],
            $filters['status'],
            $filters['shortlist']
        );

        foreach ($rows as &$row) {
            $scores = service('judging')->tallyScores((string)$row['entry_id'], $filters['phase']);
            $sum = 0;
            $count = 0;
            foreach ($scores as $score) {
                $sum += (int)($score['entry_score'] ?? 0);
                $count++;
            }
            $row['average_score'] = $count > 0 ? round($sum / $count, 2) : 0;
        }
        unset($row);

        $csv = $this->toCsv($rows);
        $filename = 'score-results-' . date('Ymd_His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function filters(): array
    {
        return [
            'year' => (int)($this->request->getGet('year') ?? date('Y')),
            'type' => (int)($this->request->getGet('type') ?? 0),
            'phase' => (string)($this->request->getGet('phase') ?? '1'),
            'userType' => (string)($this->request->getGet('userType') ?? 'all'),
            'status' => (string)($this->request->getGet('status') ?? 'All'),
            'shortlist' => (string)($this->request->getGet('shortlist') ?? 'All'),
        ];
    }

    private function years(): array
    {
        return db_connect()->query(
            'SELECT DISTINCT comp_year FROM comp_competitions ORDER BY comp_year DESC'
        )->getResultArray();
    }

    private function typesByYear(int $year): array
    {
        return db_connect()->query(
            'SELECT DISTINCT b.comp_type_id, b.comp_type_name
               FROM comp_competitions a
               INNER JOIN comp_type b ON a.comp_type_id = b.comp_type_id
              WHERE a.comp_year = ?
              ORDER BY b.comp_type_name ASC',
            [$year]
        )->getResultArray();
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }

    private function toCsv(array $rows): string
    {
        if ($rows === []) {
            return "No data\n";
        }

        $headers = array_keys($rows[0]);
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $val = $row[$h] ?? '';
                $line[] = is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            fputcsv($fh, $line);
        }
        rewind($fh);
        $csv = (string)stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
