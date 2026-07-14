<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Support\EntryBulkActions;
use App\Services\Admin\SubmissionsAdminService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            $filters['status']
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

            if ($action === 'delete') {
                if ($adminSubs->delete($entryId)) {
                    $updated++;
                }
                continue;
            }

            if ($this->applyBulkStatusAction($entryId, $action)) {
                $updated++;
            }
        }

        return redirect()->back()->with('success', 'Updated ' . $updated . ' record(s).');
    }

    public function editScores(string $entryId)
    {
        $phase = $this->normalizePhase((string)($this->request->getGet('phase') ?? '1'));
        $entry = $this->scoreEntry($entryId);
        if ($entry === null) {
            return redirect()->to('/admin/score-results')->with('error', 'Entry not found.');
        }

        return view('admin/score_results/edit_scores', [
            'entry' => $entry,
            'phase' => $phase,
            'scores' => $this->scoreRows($entryId, $phase),
            'filters' => $this->filtersFromRequest(),
        ]);
    }

    public function updateScores(string $entryId)
    {
        $phase = $this->normalizePhase((string)$this->request->getPost('phase'));
        $entry = $this->scoreEntry($entryId);
        if ($entry === null) {
            return redirect()->to('/admin/score-results')->with('error', 'Entry not found.');
        }

        $scores = $this->request->getPost('scores') ?? [];
        if (!is_array($scores) || $scores === []) {
            return redirect()->back()->with('error', 'No scores to update.');
        }

        $db = db_connect();
        $existingRows = $this->scoreRows($entryId, $phase);
        $existingByUserId = [];
        foreach ($existingRows as $row) {
            $existingByUserId[(string)($row['user_id'] ?? '')] = $row;
        }

        $updates = [];
        foreach ($scores as $scoreRow) {
            if (!is_array($scoreRow)) {
                continue;
            }

            $userId = trim((string)($scoreRow['user_id'] ?? ''));
            $entryScoreRaw = trim((string)($scoreRow['entry_score'] ?? ''));
            $entryComments = trim((string)($scoreRow['entry_comments'] ?? ''));

            if ($userId === '' || !isset($existingByUserId[$userId])) {
                return redirect()->back()->withInput()->with('error', 'Invalid judge score row.');
            }

            if (!in_array($entryScoreRaw, ['0', '1', '2'], true)) {
                return redirect()->back()->withInput()->with('error', 'Scores must be 0, 1, or 2.');
            }

            if (strlen($entryComments) > 5000) {
                return redirect()->back()->withInput()->with('error', 'Comments must be 5000 characters or fewer.');
            }

            $existing = $existingByUserId[$userId];
            if ((int)($existing['entry_score'] ?? -1) !== (int)$entryScoreRaw || (string)($existing['entry_comments'] ?? '') !== $entryComments) {
                $updates[] = [
                    'user_id' => $userId,
                    'entry_score' => (int)$entryScoreRaw,
                    'entry_comments' => $entryComments,
                ];
            }
        }

        $updated = 0;
        $db->transStart();
        foreach ($updates as $update) {
            $db->table('comp_judging')
                ->where('entry_id', $entryId)
                ->where('entry_phase', $phase)
                ->where('user_id', $update['user_id'])
                ->update([
                    'entry_score' => $update['entry_score'],
                    'entry_comments' => $update['entry_comments'],
                    'date_judged' => date('Y-m-d H:i:s'),
                ]);

            if ($db->affectedRows() >= 0) {
                $updated++;
            }
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Unable to update scores.');
        }

        return redirect()
            ->to(site_url('admin/score-results/edit/' . rawurlencode($entryId) . '?phase=' . rawurlencode($phase) . '&' . http_build_query($this->filtersFromRequest())))
            ->with('success', 'Updated ' . $updated . ' score(s).');
    }

    public function export()
    {
        $filters = $this->filters();
        $rows = service('judging')->getScoreResults(
            $filters['year'],
            $filters['type'],
            $filters['phase'],
            $filters['userType'],
            $filters['status']
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

        $rows = $this->sortExportRows($rows);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Score Results');

        if ($rows !== []) {
            $headers = array_keys($rows[0]);
            $columnCount = count($headers);

            foreach ($headers as $index => $header) {
                $col = $index + 1;
                $cell = Coordinate::stringFromColumnIndex($col) . '1';
                $sheet->setCellValueExplicit($cell, (string)$header, DataType::TYPE_STRING);
            }

            $lastHeaderCol = Coordinate::stringFromColumnIndex($columnCount);
            $sheet->getStyle('A1:' . $lastHeaderCol . '1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF'],
                ],
            ]);

            $rowNum = 2;
            foreach ($rows as $row) {
                foreach ($headers as $index => $header) {
                    $col = $index + 1;
                    $val = $row[$header] ?? '';
                    $cell = Coordinate::stringFromColumnIndex($col) . (string)$rowNum;
                    $sheet->setCellValueExplicit(
                        $cell,
                        is_scalar($val) ? (string)$val : (string)json_encode($val, JSON_UNESCAPED_UNICODE),
                        DataType::TYPE_STRING
                    );
                }

                $winnerFill = $this->winnerFillColorHex($row);
                if ($winnerFill !== null) {
                    $sheet->getStyle('A' . $rowNum . ':' . $lastHeaderCol . $rowNum)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $winnerFill],
                        ],
                    ]);
                }

                $rowNum++;
            }
        } else {
            $sheet->setCellValueExplicit('A1', 'No data', DataType::TYPE_STRING);
        }

        $filename = 'score-results-' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        ob_start();
        $writer->save('php://output');
        $binary = (string)ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($binary);
    }

    private function filters(): array
    {
        return [
            'year' => (int)($this->request->getGet('year') ?? date('Y')),
            'type' => (int)($this->request->getGet('type') ?? 0),
            'phase' => (string)($this->request->getGet('phase') ?? '1'),
            'userType' => (string)($this->request->getGet('userType') ?? 'all'),
            'status' => (string)($this->request->getGet('status') ?? 'All'),
        ];
    }

    private function filtersFromRequest(): array
    {
        return [
            'year' => (int)($this->request->getGetPost('year') ?? date('Y')),
            'type' => (int)($this->request->getGetPost('type') ?? 0),
            'userType' => (string)($this->request->getGetPost('userType') ?? 'all'),
            'status' => (string)($this->request->getGetPost('status') ?? 'All'),
        ];
    }

    private function normalizePhase(string $phase): string
    {
        return in_array($phase, ['1', '2', 'AllSpark'], true) ? $phase : '1';
    }

    private function scoreEntry(string $entryId): ?array
    {
        $row = db_connect()->table('comp_entries e')
            ->select('e.entry_id, e.design_name, e.entry_status, e.designer_first_name, e.designer_last_name, c.comp_year, t.comp_type_name, w.winner_level_name')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = e.winner_level', 'left')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function scoreRows(string $entryId, string $phase): array
    {
        return db_connect()->table('comp_judging j')
            ->select('j.user_id, j.entry_score, j.entry_comments, j.date_judged, u.first_name, u.last_name, u.email_address, u.company_name')
            ->join('comp_users u', 'u.user_id = j.user_id', 'left')
            ->where('j.entry_id', $entryId)
            ->where('j.entry_phase', $phase)
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('j.user_id', 'ASC')
            ->get()
            ->getResultArray();
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

    private function sortExportRows(array $rows): array
    {
        usort($rows, function (array $a, array $b): int {
            $cmp = $this->statusSortRank($a) <=> $this->statusSortRank($b);
            if ($cmp !== 0) {
                return $cmp;
            }

            $typeCmp = strcasecmp((string)($a['comp_type_name'] ?? ''), (string)($b['comp_type_name'] ?? ''));
            if ($typeCmp !== 0) {
                return $typeCmp;
            }

            return strcasecmp((string)($a['design_name'] ?? ''), (string)($b['design_name'] ?? ''));
        });

        return $rows;
    }

    private function statusSortRank(array $row): int
    {
        $status = strtolower(trim((string)($row['entry_status'] ?? '')));
        $level = strtolower(trim((string)($row['winner_level_name'] ?? '')));

        if ($status === 'winner') {
            return match ($level) {
                'platinum' => 1,
                'gold' => 2,
                'silver' => 3,
                'bronze' => 4,
                default => 5,
            };
        }

        return match ($status) {
            'finalist' => 6,
            'entrant' => 7,
            'draft' => 8,
            default => 9,
        };
    }

    private function winnerFillColorHex(array $row): ?string
    {
        if (strcasecmp((string)($row['entry_status'] ?? ''), 'Winner') !== 0) {
            return null;
        }

        return match (strtolower(trim((string)($row['winner_level_name'] ?? '')))) {
            'platinum' => 'F4F7FB',
            'gold' => 'FFF6DE',
            'silver' => 'F4F6F8',
            'bronze' => 'F8EEE7',
            default => 'F8F4FF',
        };
    }

    private function applyBulkStatusAction(string $entryId, string $action): bool
    {
        $statusAndPatch = EntryBulkActions::scoreResultStatusAndPatch($action);
        if ($statusAndPatch === null) {
            return false;
        }

        [$status, $patch] = $statusAndPatch;
        service('judging')->updateStatus($entryId, $status);
        return db_connect()->table('comp_entries')->where('entry_id', $entryId)->update($patch);
    }
}
