<?php

namespace App\Services\Admin;

use App\Models\Admin\CompetitionModel;
use App\Models\Admin\CompetitionTypeModel;
use App\Entities\Admin\Competition;
use App\Entities\Admin\CompetitionType;
use Config\Database;

/**
 * Entity-first CompetitionService.
 * - Returns Competition/CompetitionType entities where applicable.
 * - Keeps array outputs only if specifically used for listings.
 */
class CompetitionService
{
    private string $lastError = '';

    // ---------- create / update / delete ----------

    public function createCompetition(array $form): int
    {
        $typeId = (int)($form['comp_type_id'] ?? 0);
        $year = (int)($form['comp_year'] ?? 0);

        // prevent duplicate (type, year)
        $existing = (new CompetitionModel())
            ->where('comp_type_id', $typeId)
            ->where('comp_year', $year)
            ->first();

        if ($existing instanceof Competition) {
            return 0;
        }

        $payload = $this->buildCompetitionPayload($form);

        $model = new CompetitionModel();
        $model->insert($payload, false);
        $compId = (int)$model->getInsertID();

        // Prepare uploads directory: writable/uploads/{year}/{type-slug}
        $ctype = (new CompetitionTypeModel())->find($typeId);
        $typeNameRaw = $ctype instanceof CompetitionType ? (string)$ctype->comp_type_name : '';
        $typeSlug = $typeNameRaw !== '' ? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $typeNameRaw)) : 'unknown';
        $dir = rtrim(
                WRITEPATH . 'uploads',
                DIRECTORY_SEPARATOR
            ) . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $typeSlug;

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $compId;
    }

    public function updateCompetition(int $compId, array $form): bool
    {
        $payload = $this->buildCompetitionPayload($form);

        return (new CompetitionModel())->update($compId, $payload);
    }

    public function deleteCompetition(int $compId): bool
    {
        return (bool)(new CompetitionModel())->delete($compId);
    }

    // ---------- competition types ----------

    public function createCompetitionType(array $form): int
    {
        $name = trim((string)($form['comp_type_name'] ?? ''));
        $isStudent = (int)($form['is_student_comp'] ?? 0);
        if ($name === '') {
            return 0;
        }

        $m = new CompetitionTypeModel();
        $ok = $m->insert([
            'comp_type_name' => $name,
            'is_student_comp' => $isStudent === 1 ? 1 : 0,
        ], false);

        if ($ok === false) {
            return 0;
        }

        return (int)$m->getInsertID();
    }

    public function checkCompetitionType(array $form): bool
    {
        $name = trim((string)($form['comp_type_name'] ?? ''));
        $isStudent = array_key_exists('is_student_comp', $form) ? (int)$form['is_student_comp'] : null;
        $excludeId = (int)($form['exclude_comp_type_id'] ?? 0);
        if ($name === '') {
            return false;
        }

        $q = (new CompetitionTypeModel())->where('comp_type_name', $name);
        if ($isStudent !== null) {
            $q->where('is_student_comp', $isStudent === 1 ? 1 : 0);
        }
        if ($excludeId > 0) {
            $q->where('comp_type_id !=', $excludeId);
        }

        return (bool)$q->first();
    }

    public function getCompetitionTypeByID(int $compTypeId)
    {
        if ($compTypeId <= 0) {
            return null;
        }

        return (new CompetitionTypeModel())->find($compTypeId);
    }

    public function updateCompetitionType(int $compTypeId, array $form): bool
    {
        $this->lastError = '';

        if ($compTypeId <= 0) {
            $this->lastError = 'Invalid competition category.';
            return false;
        }

        $current = (new CompetitionTypeModel())->find($compTypeId);
        if (! $current instanceof CompetitionType) {
            $this->lastError = 'Competition category not found.';
            return false;
        }

        $name = trim((string)($form['comp_type_name'] ?? ''));
        $isStudent = (int)($form['is_student_comp'] ?? 0);
        if ($name === '') {
            $this->lastError = 'Competition category name is required.';
            return false;
        }

        $oldTypeDir = $this->legacyTypeDir((string) $current->comp_type_name);
        $newTypeDir = $this->legacyTypeDir($name);

        if ($oldTypeDir !== $newTypeDir) {
            $migration = $this->migrateTypeDirectoryAndPhotoPaths($compTypeId, $oldTypeDir, $newTypeDir);
            if (! $migration['ok']) {
                $this->lastError = (string) ($migration['error'] ?? 'Competition category rename migration failed.');
                return false;
            }
        }

        $ok = (bool)(new CompetitionTypeModel())->update($compTypeId, [
            'comp_type_name' => $name,
            'is_student_comp' => $isStudent === 1 ? 1 : 0,
        ]);
        if (! $ok) {
            $this->lastError = 'Competition category could not be updated.';
        }

        return $ok;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getAllCompetitionTypes(): array
    {
        return (new CompetitionTypeModel())->orderBy('comp_type_name', 'ASC')->findAll(); // array<CompetitionType>
    }

    public function getActiveCompetitionTypes(): array
    {
        return (new CompetitionTypeModel())
            ->where('archived', 0)
            ->orderBy('comp_type_name', 'ASC')
            ->findAll();
    }

    public function archiveCompetitionType(int $compTypeId): bool
    {
        if ($compTypeId <= 0) {
            return false;
        }

        return (bool)(new CompetitionTypeModel())->update($compTypeId, ['archived' => 1]);
    }

    // ---------- reads (Entities) ----------

    public function getAllCompetitions(): array
    {
        return (new CompetitionModel())
            ->select('comp_competitions.*, b.comp_type_name, (SELECT COUNT(*) FROM comp_entries e WHERE e.comp_id = comp_competitions.comp_id) AS submissions_count')
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->orderBy('comp_competitions.comp_year', 'DESC')
            ->orderBy('b.comp_type_name', 'ASC')
            ->findAll(); // array<Competition> with joined fields accessible
    }

    public function getCompetitionByID(int $compId)
    {
        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first(); // Competition
    }

    /**
     * @return array<int, list<int>> map: comp_year => [comp_type_id, ...]
     */
    public function getExistingCompetitionTypeIdsByYear(): array
    {
        $rows = (new CompetitionModel())
            ->select('comp_year, comp_type_id')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $year = (int)($row->comp_year ?? 0);
            $typeId = (int)($row->comp_type_id ?? 0);
            if ($year <= 0 || $typeId <= 0) {
                continue;
            }
            if (!isset($map[$year])) {
                $map[$year] = [];
            }
            $map[$year][] = $typeId;
        }

        foreach ($map as $year => $ids) {
            $map[$year] = array_values(array_unique($ids));
        }

        return $map;
    }

    // ---------- helpers ----------

    private function dt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }

    private function money($val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }
        $normalized = preg_replace('/[^0-9.\-]/', '', (string)$val);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return number_format((float)$normalized, 2, '.', '');
    }

    private function buildCompetitionPayload(array $form): array
    {
        $payload = [
            'comp_type_id' => (int)($form['comp_type_id'] ?? 0),
            'comp_year' => (int)($form['comp_year'] ?? 0),

            'comp_phase_1_open' => $this->dt($form['comp_phase_1_open'] ?? null),
            'comp_regular_reg_open' => $this->dt($form['comp_regular_reg_open'] ?? null),
            'comp_late_reg_open' => $this->dt($form['comp_late_reg_open'] ?? null),
            'comp_phase_1_close' => $this->dt($form['comp_phase_1_close'] ?? null),
            'jury_phase_1_open' => $this->dt($form['jury_phase_1_open'] ?? null),
            'jury_phase_1_close' => $this->dt($form['jury_phase_1_close'] ?? null),
            'comp_phase_2_open' => $this->dt($form['comp_phase_2_open'] ?? null),
            'comp_phase_2_close' => $this->dt($form['comp_phase_2_close'] ?? null),
            'jury_phase_2_open' => $this->dt($form['jury_phase_2_open'] ?? null),
            'jury_phase_2_close' => $this->dt($form['jury_phase_2_close'] ?? null),

            'pro_early_reg_price' => $this->money($form['pro_early_reg_price'] ?? null),
            'pro_regular_reg_price' => $this->money($form['pro_regular_reg_price'] ?? null),
            'pro_late_reg_price' => $this->money($form['pro_late_reg_price'] ?? null),
            'pro_finalist_price' => $this->money($form['pro_finalist_price'] ?? null),
            'pro_winner_price' => $this->money($form['pro_winner_price'] ?? null),
            'pro_series_price' => $this->money($form['pro_series_price'] ?? null),
            'student_early_reg_price' => $this->money($form['student_early_reg_price'] ?? null),
            'student_regular_reg_price' => $this->money($form['student_regular_reg_price'] ?? null),
            'student_late_reg_price' => $this->money($form['student_late_reg_price'] ?? null),
            'student_finalist_price' => $this->money($form['student_finalist_price'] ?? null),
            'student_winner_price' => $this->money($form['student_winner_price'] ?? null),
            'student_series_price' => $this->money($form['student_series_price'] ?? null),
            'trophy_price' => $this->money($form['trophy_price'] ?? null),
            'additional_trophy_price' => $this->money($form['additional_trophy_price'] ?? null),
        ];

        // Skip early phase fields for special type 9
        if ((int)($form['comp_type_id'] ?? 0) === 9) {
            unset(
                $payload['comp_phase_1_open'],
                $payload['comp_regular_reg_open'],
                $payload['comp_late_reg_open'],
                $payload['comp_phase_1_close'],
                $payload['jury_phase_1_open'],
                $payload['jury_phase_1_close'],
                $payload['comp_phase_2_open'],
                $payload['comp_phase_2_close']
            );
        }

        return $payload;
    }

    /**
     * Safely migrate upload directory names and DB photo URLs when category folder segment changes.
     */
    private function migrateTypeDirectoryAndPhotoPaths(int $compTypeId, string $oldTypeDir, string $newTypeDir): array
    {
        $db = Database::connect();
        $years = $db->table('comp_competitions')
            ->select('comp_year')
            ->where('comp_type_id', $compTypeId)
            ->groupBy('comp_year')
            ->get()
            ->getResultArray();

        if ($years === []) {
            return ['ok' => true];
        }

        $renameOps = [];
        foreach ($years as $row) {
            $year = (int) ($row['comp_year'] ?? 0);
            if ($year <= 0) {
                continue;
            }

            $base = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $year;
            $oldAbs = $base . DIRECTORY_SEPARATOR . $oldTypeDir;
            $newAbs = $base . DIRECTORY_SEPARATOR . $newTypeDir;
            if (! is_dir($oldAbs)) {
                continue;
            }

            if (is_dir($newAbs) && ! $this->isDirectorySemanticallyEmpty($newAbs)) {
                return ['ok' => false, 'error' => 'Target directory already exists and is not empty: ' . $newAbs];
            }

            if (! is_dir(dirname($newAbs))) {
                @mkdir(dirname($newAbs), 0775, true);
            }
            if (! @rename($oldAbs, $newAbs)) {
                return ['ok' => false, 'error' => 'Failed renaming directory: ' . $oldAbs . ' -> ' . $newAbs];
            }
            $renameOps[] = ['from' => $oldAbs, 'to' => $newAbs];
        }

        $db->transBegin();
        try {
            foreach ($years as $row) {
                $year = (int) ($row['comp_year'] ?? 0);
                if ($year <= 0) {
                    continue;
                }
                $oldPrefix = '/uploads/' . $year . '/' . $oldTypeDir . '/';
                $newPrefix = '/uploads/' . $year . '/' . $newTypeDir . '/';

                foreach (['entry_photo', 'entry_certificate'] as $field) {
                    $sql = "UPDATE comp_entry_photos p
                        INNER JOIN comp_entries e ON e.entry_id = p.entry_id
                        INNER JOIN comp_competitions c ON c.comp_id = e.comp_id
                        SET p.{$field} = REPLACE(p.{$field}, ?, ?)
                        WHERE c.comp_type_id = ?
                          AND p.{$field} LIKE ?";
                    $db->query($sql, [$oldPrefix, $newPrefix, $compTypeId, '%' . $oldPrefix . '%']);
                }
            }

            // Legacy-safety sweep: rows not reachable through joins (or with absolute URLs)
            // still need path segment migration.
            $this->sweepPhotoPathSegmentUpdates($db, $oldTypeDir, $newTypeDir);
        } catch (\Throwable $e) {
            $db->transRollback();
            $this->rollbackDirectoryRenames($renameOps);
            return ['ok' => false, 'error' => 'Failed updating photo URL paths: ' . $e->getMessage()];
        }

        if (! $db->transStatus()) {
            $db->transRollback();
            $this->rollbackDirectoryRenames($renameOps);
            return ['ok' => false, 'error' => 'Failed updating photo URL paths in database.'];
        }
        $db->transCommit();

        return ['ok' => true];
    }

    private function rollbackDirectoryRenames(array $renameOps): void
    {
        for ($i = count($renameOps) - 1; $i >= 0; $i--) {
            $op = $renameOps[$i];
            $from = (string) ($op['from'] ?? '');
            $to = (string) ($op['to'] ?? '');
            if ($from === '' || $to === '') {
                continue;
            }
            if (is_dir($to) && ! is_dir($from)) {
                @rename($to, $from);
            }
        }
    }

    private function isDirectorySemanticallyEmpty(string $dir): bool
    {
        $items = @scandir($dir);
        if (! is_array($items)) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (in_array($item, ['index.html', '.htaccess'], true)) {
                continue;
            }
            return false;
        }
        return true;
    }

    private function legacyTypeDir(string $compTypeName): string
    {
        $lower = strtolower(trim($compTypeName));
        if ($lower === '') {
            $lower = 'unknown';
        }
        return str_replace(['/', '\\'], '-', $lower);
    }

    private function sweepPhotoPathSegmentUpdates($db, string $oldTypeDir, string $newTypeDir): void
    {
        $rows = $db->table('comp_entry_photos')
            ->select('entry_photo_id, entry_photo, entry_certificate')
            ->groupStart()
                ->like('entry_photo', '/uploads/%/' . $oldTypeDir . '/')
                ->orLike('entry_certificate', '/uploads/%/' . $oldTypeDir . '/')
            ->groupEnd()
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $id = (int) ($row['entry_photo_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $updates = [];
            foreach (['entry_photo', 'entry_certificate'] as $field) {
                $raw = (string) ($row[$field] ?? '');
                if ($raw === '') {
                    continue;
                }
                $updated = $this->replaceUploadsTypeSegment($raw, $oldTypeDir, $newTypeDir);
                if ($updated !== $raw) {
                    $updates[$field] = $updated;
                }
            }
            if ($updates !== []) {
                $db->table('comp_entry_photos')->where('entry_photo_id', $id)->update($updates);
            }
        }
    }

    private function replaceUploadsTypeSegment(string $value, string $oldTypeDir, string $newTypeDir): string
    {
        $quotedOld = preg_quote($oldTypeDir, '#');
        return (string) preg_replace(
            '#(/uploads/\d{4}/)' . $quotedOld . '(/)#i',
            '$1' . $newTypeDir . '$2',
            $value
        );
    }
}
