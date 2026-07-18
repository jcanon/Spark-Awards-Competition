<?php

namespace App\Services;

use App\Models\Admin\CompetitionModel;
use App\Models\Judging\EntryModel;
use App\Models\Judging\JudgingModel;
use App\Models\Judging\EntryPhotoModel;
use RuntimeException;

/**
 * Entity-first JudgingService.
 * - Uses models to return entities where appropriate.
 * - Keeps array outputs for reporting/exports as needed.
 */
class JudgingService
{
    // -------- open / upcoming lists for judges (arrays for listings) --------

    public function getOpenPhase1Judging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('jury_phase_1_open <=', $now)
            ->where('jury_phase_1_close >', $now)
            ->where('b.comp_type_id <>', 9)
            ->orderBy('comp_phase_1_close ASC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    public function getOpenPhase2Judging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('jury_phase_2_open <=', $now)
            ->where('jury_phase_2_close >', $now)
            ->where('b.comp_type_id <>', 9)
            ->orderBy('comp_phase_2_close ASC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    public function getUpcomingPhase1Judging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('jury_phase_1_open >', $now)
            ->where('b.comp_type_id <>', 9)
            ->orderBy('jury_phase_1_open ASC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    public function getUpcomingPhase2Judging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('jury_phase_2_open >', $now)
            ->where('b.comp_type_id <>', 9)
            ->orderBy('jury_phase_2_open ASC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    public function getClosedPhase1Judging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_competitions.comp_year', (int)date('Y'))
            ->where('jury_phase_1_close <=', $now)
            ->where('b.comp_type_id <>', 9)
            ->orderBy('jury_phase_1_close DESC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    public function getClosedPhase2Judging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_competitions.comp_year', (int)date('Y'))
            ->where('jury_phase_2_close <=', $now)
            ->where('b.comp_type_id <>', 9)
            ->orderBy('jury_phase_2_close DESC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    public function getAllSparkJudging(): array
    {
        $now = date('Y-m-d H:i:s');

        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('jury_phase_2_open <=', $now)
            ->where('jury_phase_2_close >', $now)
            ->where('b.comp_type_id', 9)
            ->orderBy('comp_phase_2_close ASC, b.comp_type_name ASC')
            ->asArray()
            ->findAll();
    }

    // -------- entries and scoring --------

    public function judgingEntries(int $compId, string $entryStatus): array
    {
        $builder = db_connect()->table('comp_entries a')
            ->select('a.*')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->join('comp_users d', 'a.user_id = d.user_id')
            ->where('a.comp_id', $compId)
            ->where('a.entry_status', $entryStatus);

        $builder->orderBy('a.design_name', 'ASC');

        return $builder->get()->getResultArray();
    }

    public function allSparkJudgingEntries(int $compYear, string $entryStatus): array
    {
        $builder = db_connect()->table('comp_entries a')
            ->select('a.*')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->join('comp_users d', 'a.user_id = d.user_id')
            ->where('b.comp_year', $compYear)
            ->where('a.entry_status', $entryStatus)
            ->groupStart()
            ->where('a.winner_level', 1)
            ->orWhere('a.winner_level', 2)
            ->orWhere('a.winner_level', 3)
            ->groupEnd()
            ->orderBy('a.design_name', 'ASC');

        return $builder->get()->getResultArray();
    }

    public function judgingEntryById(string $entryId, string $entryStatus): ?array
    {
        $builder = db_connect()->table('comp_entries a')
            ->select('a.*')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->join('comp_users d', 'a.user_id = d.user_id')
            ->where('a.entry_id', $entryId)
            ->where('a.entry_status', $entryStatus)
            ->orderBy('a.design_name', 'ASC');

        $row = $builder->get()->getRowArray();
        return $row ?: null;
    }

    public function allSparkJudgingOpen(int $compId): ?array
    {
        $row = (new CompetitionModel())
            ->select('jury_phase_2_open, jury_phase_2_close')
            ->where('comp_id', $compId)
            ->asArray()
            ->first();

        return $row ?: null;
    }

    public function judgingScoreById(string $entryId, string $entryPhase): ?array
    {
        $uid = (string)(session('uid') ?? '');
        if ($uid === '') {
            return null;
        }

        $row = (new JudgingModel())
            ->where('entry_id', $entryId)
            ->where('entry_phase', $entryPhase)
            ->where('user_id', $uid)
            ->asArray()
            ->first();

        return $row ?: null;
    }

    public function judgingScoresByEntryIds(array $entryIds, string $entryPhase): array
    {
        $uid = (string)(session('uid') ?? '');
        if ($uid === '') {
            return [];
        }

        $ids = array_values(array_filter(array_map(static fn ($id): string => trim((string)$id), $entryIds)));
        if ($ids === []) {
            return [];
        }

        $rows = (new JudgingModel())
            ->select('entry_id, entry_score')
            ->where('entry_phase', $entryPhase)
            ->where('user_id', $uid)
            ->whereIn('entry_id', $ids)
            ->asArray()
            ->findAll();

        $scores = [];
        foreach ($rows as $row) {
            $entryId = (string)($row['entry_id'] ?? '');
            if ($entryId === '') {
                continue;
            }
            $scores[$entryId] = isset($row['entry_score']) ? (int)$row['entry_score'] : null;
        }

        return $scores;
    }

    public function judgingPhotos(string $entryId, string $resType): array
    {
        return (new EntryPhotoModel())
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', $resType)
            ->orderBy('entry_photo_order', 'ASC')
            ->asArray()
            ->findAll();
    }

    public function setEntryScore(string $entryId, string $entryPhase, int $entryScore, string $entryComments): void
    {
        $uid = (string)(session('uid') ?? '');
        if ($uid === '') {
            throw new RuntimeException('Not authenticated');
        }

        $table = db_connect()->table('comp_judging');

        $existing = $table
            ->select('user_id')
            ->where('user_id', $uid)
            ->where('entry_id', $entryId)
            ->where('entry_phase', $entryPhase)
            ->get()
            ->getRowArray();

        $payload = [
            'entry_score' => $entryScore,
            'entry_comments' => $entryComments,
            'date_judged' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $table->where('user_id', $uid)
                ->where('entry_id', $entryId)
                ->where('entry_phase', $entryPhase)
                ->set($payload)
                ->update();
        } else {
            $table->insert(array_merge($payload, [
                'user_id' => $uid,
                'entry_id' => $entryId,
                'entry_phase' => $entryPhase,
            ]));
        }
    }

    public function getScoreResults(
        int $compYear,
        int $compType,
        string $compPhase,
        string $compUserType,
        string $compStatus
    ): array {
        $params = [];
        $sql = "
            SELECT DISTINCT
                b.entry_id,
                b.design_name,
                b.entry_status,
                b.winner_level,
                b.designer_first_name,
                b.designer_last_name,
                d.user_id,
                d.user_type_id,
                d.first_name,
                d.last_name,
                d.company_name,
                d.email_address,
                d.phone,
                f.comp_type_name,
                g.winner_level_name
            FROM comp_judging a
            INNER JOIN comp_entries b ON a.entry_id = b.entry_id
            INNER JOIN comp_competitions c ON b.comp_id = c.comp_id
            INNER JOIN comp_users d ON b.user_id = d.user_id
            INNER JOIN comp_user_type e ON d.user_type_id = e.user_type_id
            INNER JOIN comp_type f ON c.comp_type_id = f.comp_type_id
            LEFT JOIN comp_winner_levels g ON b.winner_level = g.winner_level_id
            WHERE a.entry_phase = ?
              AND c.comp_year = ?";

        $params[] = $compPhase;
        $params[] = $compYear;

        if ($compType !== 9 && $compType !== 0) {
            $sql .= " AND c.comp_type_id = ?";
            $params[] = $compType;
        }
        if (strcasecmp($compUserType, 'all') !== 0) {
            $sql .= " AND e.user_type_pricing = ?";
            $params[] = $compUserType;
        }
        if (strcasecmp($compStatus, 'all') !== 0) {
            $sql .= " AND b.entry_status = ?";
            $params[] = $compStatus;
        }

        return db_connect()->query($sql, $params)->getResultArray();
    }

    public function updateStatus(string $entryId, string $entryStatus): void
    {
        (new EntryModel())->update($entryId, ['entry_status' => $entryStatus]);
    }

    public function tallyScores(string $entryId, string $compPhase): array
    {
        return (new JudgingModel())
            ->select('entry_score, entry_comments')
            ->where('entry_id', $entryId)
            ->where('entry_phase', $compPhase)
            ->asArray()
            ->findAll();
    }

    public function tallyScoresByEntryIds(array $entryIds, string $compPhase): array
    {
        $ids = array_values(array_filter(array_map(static fn ($id): string => trim((string)$id), $entryIds)));
        if ($ids === []) {
            return [];
        }

        $rows = (new JudgingModel())
            ->select('entry_id, COUNT(*) AS total_judges, COALESCE(SUM(entry_score),0) AS total_score')
            ->where('entry_phase', $compPhase)
            ->whereIn('entry_id', $ids)
            ->groupBy('entry_id')
            ->asArray()
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            $entryId = (string)($row['entry_id'] ?? '');
            if ($entryId === '') {
                continue;
            }
            $out[$entryId] = [
                'total_score' => (int)($row['total_score'] ?? 0),
                'total_judges' => (int)($row['total_judges'] ?? 0),
            ];
        }

        return $out;
    }
}
