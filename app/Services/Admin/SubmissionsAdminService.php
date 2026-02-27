<?php

declare(strict_types=1);

namespace App\Services\Admin;

use CodeIgniter\Database\BaseBuilder;
use App\Support\EntryBulkActions;
use App\Models\Entries\UserEntriesModel;
use App\Models\Entries\EntryQuestionModel;
use App\Models\Judging\EntryPhotoModel;
use App\Services\SubmissionsService;

class SubmissionsAdminService
{
    private SubmissionsService $submissions;

    public function __construct()
    {
        $this->submissions = new SubmissionsService();
    }

    public function getCompetitionYears(): array
    {
        return db_connect()->query(
            'SELECT DISTINCT comp_year FROM comp_competitions ORDER BY comp_year DESC'
        )->getResultArray();
    }

    public function getCompetitionTypesByYear(int $year): array
    {
        return db_connect()->query(
            'SELECT a.comp_id, b.comp_type_id, b.comp_type_name
               FROM comp_competitions a
               INNER JOIN comp_type b ON a.comp_type_id = b.comp_type_id
              WHERE a.comp_year = ?
              GROUP BY b.comp_type_id, b.comp_type_name, a.comp_id
              ORDER BY b.comp_type_name ASC',
            [$year]
        )->getResultArray();
    }

    public function getWinnerLevels(): array
    {
        return db_connect()->query(
            'SELECT winner_level_id, winner_level_name
               FROM comp_winner_levels
              ORDER BY winner_level_id ASC'
        )->getResultArray();
    }

    public function list(array $filters): array
    {
        $filterBy = trim((string)($filters['filterBy'] ?? ''));
        $compYear = (int)($filters['compYear'] ?? date('Y'));
        $compType = (string)($filters['compType'] ?? 'ALL');
        $excludeNonFinalists = (string)($filters['excludeNonFinalists'] ?? 'No');

        $db = db_connect();
        $builder = $db->table('comp_entries a')
            ->select('a.*, b.comp_year, b.comp_type_id, c.comp_type_name, d.first_name, d.last_name, d.email_address, d.company_name, f.winner_level_name')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->join('comp_users d', 'a.user_id = d.user_id')
            ->join('comp_winner_levels f', 'a.winner_level = f.winner_level_id', 'left')
            ->where('b.comp_year', $compYear);

        if ($compType !== 'ALL') {
            $builder->where('b.comp_type_id', (int)$compType);
        }
        if ($excludeNonFinalists === 'Yes') {
            // Legacy intent for admin submissions view: exclude non-finalist pool entirely.
            // Keep only Finalist/Winner statuses when this toggle is enabled.
            $builder->where("UPPER(TRIM(a.entry_status)) IN ('FINALIST','WINNER')", null, false);
            $this->applyExcludeNonFinalists($builder);
        }
        if ($filterBy !== '') {
            $builder->groupStart()
                ->like('a.design_name', $filterBy)
                ->orLike('d.last_name', $filterBy)
                ->orLike('d.company_name', $filterBy)
                ->groupEnd();
        }

        return $builder
            ->orderBy('a.design_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function find(string $entryId): ?array
    {
        $row = db_connect()->table('comp_entries a')
            ->select('a.*, b.comp_year, b.comp_type_id, c.comp_type_name, d.first_name, d.last_name, d.email_address, d.company_name, f.winner_level_name AS selected_winner_level_name')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->join('comp_users d', 'a.user_id = d.user_id')
            ->join('comp_winner_levels f', 'a.winner_level = f.winner_level_id', 'left')
            ->where('a.entry_id', $entryId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function create(array $form): string
    {
        $entryId = $this->uuidV4();
        $photoId = random_int(111111111, 999999999);

        db_connect()->table('comp_entries')->insert([
            'entry_id' => $entryId,
            'user_id' => (string)$form['user_id'],
            'comp_id' => (int)$form['comp_id'],
            'photo_id' => $photoId,
            'design_name' => trim((string)($form['design_name'] ?? '')),
            'referred_by' => trim((string)($form['referred_by'] ?? '')),
            'entry_status' => 'Draft',
            'date_created' => date('Y-m-d H:i:s'),
        ]);

        db_connect()->table('comp_entry_payments')->insert([
            'entry_id' => $entryId,
            'payment_phase' => 1,
            'payment_total' => 0,
            'coupon_code' => '',
        ]);

        return $entryId;
    }

    public function copyToCompetition(string $entryId, int $newCompId): ?string
    {
        $db = db_connect();
        $source = (new UserEntriesModel())->asArray()->where('entry_id', $entryId)->first();
        if (!$source) {
            return null;
        }

        $competitionExists = $db->table('comp_competitions a')
            ->select('a.comp_id, a.comp_year, b.comp_type_name')
            ->join('comp_type b', 'a.comp_type_id = b.comp_type_id')
            ->where('comp_id', $newCompId)
            ->get()
            ->getRowArray();
        if (!$competitionExists) {
            return null;
        }

        $newEntryId = $this->uuidV4();
        $newPhotoId = random_int(111111111, 999999999);
        $typeSlug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', (string)$competitionExists['comp_type_name']));
        $destPhotoDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . (int)$competitionExists['comp_year']
            . DIRECTORY_SEPARATOR . trim($typeSlug, '-')
            . DIRECTORY_SEPARATOR . 'entry_' . $newEntryId;

        $db->transStart();

        $payload = $source;
        unset($payload['entry_id']);
        $payload['entry_id'] = $newEntryId;
        $payload['comp_id'] = $newCompId;
        $payload['photo_id'] = $newPhotoId;
        $payload['date_created'] = date('Y-m-d H:i:s');
        $db->table('comp_entries')->insert($payload);

        $payments = $db->table('comp_entry_payments')
            ->where('entry_id', $entryId)
            ->get()
            ->getResultArray();

        if ($payments === []) {
            $db->table('comp_entry_payments')->insert([
                'entry_id' => $newEntryId,
                'payment_phase' => 1,
                'payment_total' => 0,
                'coupon_code' => '',
            ]);
        } else {
            foreach ($payments as $payment) {
                unset($payment['payment_id']);
                $payment['entry_id'] = $newEntryId;
                if (isset($payment['payment_date'])) {
                    $payment['payment_date'] = date('Y-m-d H:i:s');
                }
                $db->table('comp_entry_payments')->insert($payment);
            }
        }

        $answers = $db->table('comp_entry_answers')
            ->where('entry_id', $entryId)
            ->get()
            ->getResultArray();
        foreach ($answers as $answer) {
            $db->table('comp_entry_answers')->insert([
                'entry_id' => $newEntryId,
                'entry_question_id' => $answer['entry_question_id'],
                'entry_answer' => $answer['entry_answer'],
            ]);
        }

        $addons = $db->table('comp_retail_item_user')
            ->where('entry_id', $entryId)
            ->get()
            ->getResultArray();
        foreach ($addons as $addon) {
            unset($addon['retail_item_user_id']);
            $addon['entry_id'] = $newEntryId;
            $db->table('comp_retail_item_user')->insert($addon);
        }

        $photos = (new EntryPhotoModel())->asArray()->where('entry_id', $entryId)->findAll();
        foreach ($photos as $photo) {
            unset($photo['entry_photo_id']);
            $photo['entry_id'] = $newEntryId;
            $photo['entry_photo'] = $this->copyPhotoPath((string)($photo['entry_photo'] ?? ''), $destPhotoDir, $newEntryId);
            $db->table('comp_entry_photos')->insert($photo);
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return null;
        }

        return $newEntryId;
    }

    public function update(string $entryId, array $form): bool
    {
        $form['entry_id'] = $entryId;
        return $this->submissions->updateSubmission($form);
    }

    public function delete(string $entryId): bool
    {
        $entry = (new UserEntriesModel())->asArray()->where('entry_id', $entryId)->first();
        if (!$entry) {
            return false;
        }

        return $this->submissions->deleteSubmission($entryId, (string)$entry['user_id']);
    }

    public function bulkAction(string $action, array $entryIds): int
    {
        $ids = array_values(array_filter(array_map(static fn ($v): string => trim((string)$v), $entryIds)));
        if ($ids === []) {
            return 0;
        }

        $updatePayload = $this->bulkActionUpdatePayload($action);
        if ($updatePayload === null) {
            return 0;
        }

        $affected = 0;

        foreach ($ids as $entryId) {
            if (db_connect()->table('comp_entries')->where('entry_id', $entryId)->update($updatePayload)) {
                $affected++;
            }
        }

        return $affected;
    }

    private function bulkActionUpdatePayload(string $action): ?array
    {
        return EntryBulkActions::submissionPayload($action);
    }

    public function export(array $filters): array
    {
        $rows = $this->list($filters);
        if ($rows === []) {
            return [];
        }

        $entryIds = array_values(array_unique(array_map(static fn (array $r): string => (string)($r['entry_id'] ?? ''), $rows)));
        $paymentDates = [];
        if ($entryIds !== []) {
            $payments = db_connect()->table('comp_entry_payments')
                ->select('entry_id, payment_phase, payment_date')
                ->whereIn('entry_id', $entryIds)
                ->whereIn('payment_phase', [1, 2])
                ->where('payment_date IS NOT NULL', null, false)
                ->where('payment_date !=', '')
                ->get()
                ->getResultArray();

            foreach ($payments as $payment) {
                $entryId = (string)($payment['entry_id'] ?? '');
                $phase = (int)($payment['payment_phase'] ?? 0);
                $date = (string)($payment['payment_date'] ?? '');
                if ($entryId === '' || !in_array($phase, [1, 2], true) || $date === '') {
                    continue;
                }

                if (!isset($paymentDates[$entryId])) {
                    $paymentDates[$entryId] = [
                        1 => '',
                        2 => '',
                    ];
                }

                // Keep latest datetime if multiple rows exist for a phase.
                $current = (string)$paymentDates[$entryId][$phase];
                if ($current === '' || strtotime($date) > strtotime($current)) {
                    $paymentDates[$entryId][$phase] = $date;
                }
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $entryId = (string)$row['entry_id'];
            $p1Date = (string)($paymentDates[$entryId][1] ?? '');
            $p2Date = (string)($paymentDates[$entryId][2] ?? '');
            $out[] = [
                'entry_id' => $entryId,
                'design_name' => $row['design_name'],
                'competition_year' => $row['comp_year'],
                'competition_type' => $row['comp_type_name'],
                'status' => $row['entry_status'],
                'winner_level' => $row['winner_level_name'] ?? '',
                'submitted_by' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'company_name' => $row['company_name'] ?? '',
                'email_address' => $row['email_address'] ?? '',
                'phase_1_payment' => $row['phase_1_payment'] ?? '',
                'phase_1_pay_date' => $p1Date,
                'phase_2_payment' => $row['phase_2_payment'] ?? '',
                'phase_2_pay_date' => $p2Date,
                'short_description' => $row['short_description'] ?? '',
                'full_description' => $row['full_description'] ?? '',
                'video_embed_url' => $this->submissions->normalizeVideoEmbedUrl((string)($row['youtube_url'] ?? '')),
            ];
        }

        return $out;
    }

    public function judgingCards(array $filters): array
    {
        $year = (int)($filters['compYear'] ?? date('Y'));
        $type = (string)($filters['compType'] ?? 'ALL');
        $phase = (int)($filters['compPhase'] ?? 1);
        $excludeNonFinalists = (string)($filters['excludeNonFinalists'] ?? 'No');

        $builder = db_connect()->table('comp_entries a')
            ->select('a.entry_id, a.design_name, a.short_description, a.full_description, a.designer_first_name, a.designer_last_name, b.comp_year, c.comp_type_name')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->where('b.comp_year', $year);

        if ($phase === 2) {
            $builder->where('a.phase_2_payment', 'Paid');
        } else {
            $builder->where('a.phase_1_payment', 'Paid');
        }
        if ($excludeNonFinalists === 'Yes') {
            $this->applyExcludeNonFinalists($builder);
        }
        if ($type !== 'ALL') {
            $builder->where('b.comp_type_id', (int)$type);
        }

        $rows = $builder->orderBy('a.design_name', 'ASC')->get()->getResultArray();

        $photos = (new EntryPhotoModel())
            ->asArray()
            ->where('entry_photo_res', 'Low')
            ->where('entry_photo_order', 1)
            ->findAll();
        $photosByEntry = [];
        foreach ($photos as $photo) {
            $photosByEntry[$photo['entry_id']] = $photo['entry_photo'];
        }

        foreach ($rows as &$row) {
            $row['photo'] = $photosByEntry[$row['entry_id']] ?? '';
        }
        unset($row);

        return $rows;
    }

    public function questions(): array
    {
        return (new EntryQuestionModel())->asArray()->orderBy('entry_question_order', 'ASC')->findAll();
    }

    private function whitelistOrderBy(string $orderBy): string
    {
        $allowed = ['design_name', 'entry_status', 'date_created', 'comp_year', 'last_name', 'company_name'];
        return in_array($orderBy, $allowed, true) ? $orderBy : 'design_name';
    }

    private function applyExcludeNonFinalists(BaseBuilder $builder): void
    {
        $builder->where(
            "COALESCE(NULLIF(TRIM(LOWER(a.entry_non_finalist)), ''), 'no') NOT IN ('yes','1','true','y')",
            null,
            false
        );
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return substr(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)), 0, 35);
    }

    private function copyPhotoPath(string $rawPath, string $destDir, string $newEntryId): string
    {
        if ($rawPath === '' || preg_match('#^https?://#i', $rawPath) === 1) {
            return $rawPath;
        }

        $sourceAbs = $this->resolveAbsolutePath($rawPath);
        if (!is_file($sourceAbs)) {
            return $rawPath;
        }

        $sourceName = pathinfo($sourceAbs, PATHINFO_FILENAME);
        $sourceExt = strtolower((string)pathinfo($sourceAbs, PATHINFO_EXTENSION));
        $safeExt = in_array($sourceExt, ['jpg', 'jpeg', 'pdf'], true) ? $sourceExt : 'jpg';
        $destName = $sourceName . '_copy_' . substr($newEntryId, 0, 8) . '.' . $safeExt;

        if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            return $rawPath;
        }
        @file_put_contents(rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html', '');
        @file_put_contents(
            rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess',
            "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx)$\">\nRequire all denied\n</FilesMatch>\n"
        );

        $destAbs = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $destName;

        if (!@copy($sourceAbs, $destAbs)) {
            return $rawPath;
        }

        return $this->urlFromAbsolutePath($destAbs, $rawPath);
    }

    private function resolveAbsolutePath(string $rawPath): string
    {
        $path = ltrim((string)(parse_url($rawPath, PHP_URL_PATH) ?: $rawPath), '/\\');
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $public = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalized;
        if (is_file($public)) {
            return $public;
        }

        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalized;
    }

    private function urlFromAbsolutePath(string $absolutePath, string $fallbackRawPath): string
    {
        $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $absolutePath);

        $publicRoot = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($normalized, $publicRoot)) {
            $rel = substr($normalized, strlen($publicRoot));
            return '/' . str_replace(DIRECTORY_SEPARATOR, '/', ltrim((string)$rel, DIRECTORY_SEPARATOR));
        }

        $writeRoot = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($normalized, $writeRoot)) {
            $rel = substr($normalized, strlen($writeRoot));
            return '/' . str_replace(DIRECTORY_SEPARATOR, '/', ltrim((string)$rel, DIRECTORY_SEPARATOR));
        }

        return $fallbackRawPath;
    }
}
