<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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
        $statusMap = [
            'non_finalist' => 'Entrant',
            'finalist' => 'Finalist',
            'winner_platinum' => 'Winner',
            'winner_gold' => 'Winner',
            'winner_silver' => 'Winner',
            'winner_bronze' => 'Winner',
        ];
        $entryPatchMap = [
            'non_finalist' => ['entry_non_finalist' => 'Yes'],
            'finalist' => ['entry_non_finalist' => 'No'],
            'winner_platinum' => ['winner_level' => 1],
            'winner_gold' => ['winner_level' => 2],
            'winner_silver' => ['winner_level' => 3],
            'winner_bronze' => ['winner_level' => 4],
        ];

        if (!isset($statusMap[$action], $entryPatchMap[$action])) {
            return false;
        }

        service('judging')->updateStatus($entryId, $statusMap[$action]);
        return db_connect()->table('comp_entries')->where('entry_id', $entryId)->update($entryPatchMap[$action]);
    }
}
