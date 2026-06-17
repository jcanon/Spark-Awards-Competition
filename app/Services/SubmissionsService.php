<?php

namespace App\Services;

use App\Models\Entries\UserEntriesModel;
use App\Models\Entries\EntryQuestionModel;
use App\Models\Payments\EntryPaymentModel;
use App\Models\Entries\EntryAnswersModel;
use App\Services\EntryPhotoService;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class SubmissionsService
{
    private BaseConnection $db;
    private EntryPhotoService $photos;
    private EntryAnswersModel $answersModel;

    public function __construct()
    {
        $this->db = db_connect();
        $this->photos = new EntryPhotoService();
        $this->answersModel = new EntryAnswersModel();
    }

    // ---------- helpers ----------

    private function uid(): string
    {
        $uid = (string) session('uid');
        if ($uid === '') {
            throw new RuntimeException('Not authenticated');
        }
        return $uid;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return substr(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)), 0, 35);
    }

    private function parseHost(string $url): string
    {
        return strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));
    }

    private function parsePath(string $url): string
    {
        return (string)(parse_url($url, PHP_URL_PATH) ?? '');
    }

    private function extractYoutubeId(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Legacy support: historically we stored plain YouTube IDs.
        if (preg_match('/^[A-Za-z0-9_-]{6,25}$/', $raw) === 1 && !ctype_digit($raw)) {
            return $raw;
        }

        $host = $this->parseHost($raw);
        $path = trim($this->parsePath($raw), '/');
        $id = null;

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            $id = $path !== '' ? explode('/', $path)[0] : null;
        } elseif (
            str_ends_with($host, 'youtube.com') ||
            str_ends_with($host, 'youtube-nocookie.com')
        ) {
            if (str_starts_with($path, 'watch')) {
                parse_str((string)(parse_url($raw, PHP_URL_QUERY) ?? ''), $query);
                $id = isset($query['v']) ? (string)$query['v'] : null;
            } elseif (preg_match('~^(?:embed|shorts|live)/([^/?#]+)~i', $path, $m) === 1) {
                $id = (string)$m[1];
            }
        }

        if ($id === null) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9_-]{6,25}$/', $id) === 1 ? $id : null;
    }

    private function extractVimeoId(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $host = $this->parseHost($raw);
        if ($host === '' || strpos($host, 'vimeo.com') === false) {
            return null;
        }

        $path = $this->parsePath($raw);
        if ($path === '') {
            return null;
        }

        if (preg_match('~(?:^|/)(?:video/)?(\d+)(?:$|[/?#])~i', $path, $m) !== 1) {
            return null;
        }

        return (string)$m[1];
    }

    public function getVideoEmbedData(?string $value): ?array
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(?:youtube|yt):([A-Za-z0-9_-]{6,25})$/i', $raw, $m) === 1) {
            $youtubeId = (string)$m[1];
            return [
                'provider' => 'youtube',
                'id' => $youtubeId,
                'embed_url' => 'https://www.youtube.com/embed/' . $youtubeId,
            ];
        }

        if (preg_match('/^(?:vimeo|vm):(\d{3,20})$/i', $raw, $m) === 1) {
            $vimeoId = (string)$m[1];
            return [
                'provider' => 'vimeo',
                'id' => $vimeoId,
                'embed_url' => 'https://player.vimeo.com/video/' . $vimeoId,
            ];
        }

        $youtubeId = $this->extractYoutubeId($raw);
        if ($youtubeId !== null) {
            return [
                'provider' => 'youtube',
                'id' => $youtubeId,
                'embed_url' => 'https://www.youtube.com/embed/' . $youtubeId,
            ];
        }

        $vimeoId = $this->extractVimeoId($raw);
        if ($vimeoId !== null) {
            return [
                'provider' => 'vimeo',
                'id' => $vimeoId,
                'embed_url' => 'https://player.vimeo.com/video/' . $vimeoId,
            ];
        }

        return null;
    }

    public function isValidVideoEmbedUrl(?string $value): bool
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return true;
        }

        return $this->getVideoEmbedData($raw) !== null;
    }

    public function normalizeVideoEmbedUrl(?string $value): string
    {
        $data = $this->getVideoEmbedData($value);
        return $data['embed_url'] ?? '';
    }

    public function normalizeVideoStorageValue(?string $value): string
    {
        $data = $this->getVideoEmbedData($value);
        if ($data === null) {
            return '';
        }

        if (($data['provider'] ?? '') === 'youtube') {
            return 'yt:' . (string)($data['id'] ?? '');
        }
        if (($data['provider'] ?? '') === 'vimeo') {
            return 'vm:' . (string)($data['id'] ?? '');
        }

        return '';
    }

    // ---------- answers helpers ----------

    private function listQuestions(): array
    {
        return (new EntryQuestionModel())
            ->orderBy('entry_question_order', 'ASC')
            ->findAll();
    }

    // Insert answers only on create (no duplicates expected)
    private function createAnswersInsertOnly(string $entryId, array $form): void
    {
        $sql = 'INSERT IGNORE INTO comp_entry_answers (entry_id, entry_question_id, entry_answer) VALUES (?, ?, ?)';
        foreach ($this->listQuestions() as $q) {
            $qid = (int) $q->entry_question_id;
            $key = 'entry_question_' . $qid;
            $ans = (string) ($form[$key] ?? '');
            $this->db->query($sql, [$entryId, $qid, $ans]);
        }
    }

    // Update answers only on update (no inserts here)
    private function updateAnswersUpdateOnly(string $entryId, array $form): void
    {
        $sql = 'INSERT INTO comp_entry_answers (entry_id, entry_question_id, entry_answer) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE entry_answer = VALUES(entry_answer)';
        foreach ($this->listQuestions() as $q) {
            $qid = (int) $q->entry_question_id;
            $key = 'entry_question_' . $qid;
            if (array_key_exists($key, $form)) {
                $ans = (string) ($form[$key] ?? '');
                $this->db->query($sql, [$entryId, $qid, $ans]);
            }
        }
    }

    // ---------- reads used by controllers ----------

    public function getDesignTypes(int $compTypeId): array
    {
        return (new \App\Models\Admin\DesignTypeModel())
            ->select('design_type_id, design_type_name')
            ->where('comp_type_id', $compTypeId)
            ->orderBy('design_type_name', 'ASC')
            ->asArray()
            ->findAll();
    }

    public function getEntryByID(string $entryId, ?string $userId = null)
    {
        $userId = $userId ?: $this->uid();
        return (new UserEntriesModel())
            ->where('user_id', $userId)
            ->where('entry_id', $entryId)
            ->first();
    }

    public function getMyPhase1Subs(): array
    {
        $uid = $this->uid();
        $now = date('Y-m-d H:i:s');
        return (new UserEntriesModel())
            ->join('comp_competitions b', 'b.comp_id = comp_entries.comp_id')
            ->join('comp_type c', 'c.comp_type_id = b.comp_type_id')
            ->join('comp_entry_payments p1', 'p1.entry_id = comp_entries.entry_id AND p1.payment_phase = 1', 'left')
            ->where('comp_entries.user_id', $uid)
            ->groupStart()
            ->where('b.comp_phase_1_open <=', $now)
            ->where('b.comp_phase_1_close >', $now)
            ->groupEnd()
            ->groupStart()
            ->where('comp_entries.entry_status', 'Draft')
            ->orWhere('comp_entries.entry_status', 'Entrant')
            ->groupEnd()
            ->select('comp_entries.*, b.*, c.*, p1.payment_status AS phase_1_payment_status, p1.payment_status_message AS phase_1_payment_status_message, p1.payment_transaction_id AS phase_1_payment_transaction_id')
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    public function getMyPhase2Subs(): array
    {
        $uid = $this->uid();
        $now = date('Y-m-d H:i:s');
        return (new UserEntriesModel())
            ->join('comp_competitions b', 'b.comp_id = comp_entries.comp_id')
            ->join('comp_type c', 'c.comp_type_id = b.comp_type_id')
            ->join('comp_entry_payments p2', 'p2.entry_id = comp_entries.entry_id AND p2.payment_phase = 2', 'left')
            ->where('comp_entries.user_id', $uid)
            ->where('comp_entries.entry_status', 'Finalist')
            ->where('b.comp_phase_2_close >', $now)
            ->select('comp_entries.*, b.*, c.*, p2.payment_status AS phase_2_payment_status, p2.payment_status_message AS phase_2_payment_status_message, p2.payment_transaction_id AS phase_2_payment_transaction_id')
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    public function getMyPhase3Subs(): array
    {
        $uid = $this->uid();
        return (new UserEntriesModel())
            ->join('comp_competitions b', 'b.comp_id = comp_entries.comp_id')
            ->join('comp_type c', 'c.comp_type_id = b.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = comp_entries.winner_level', 'left')
            ->join('comp_entry_payments p1', 'p1.entry_id = comp_entries.entry_id AND p1.payment_phase = 1', 'left')
            ->join('comp_entry_payments p2', 'p2.entry_id = comp_entries.entry_id AND p2.payment_phase = 2', 'left')
            ->where('comp_entries.user_id', $uid)
            ->where('comp_entries.entry_status', 'Winner')
            ->select('comp_entries.*, b.*, c.*, w.*, p1.payment_status AS phase_1_payment_status, p2.payment_status AS phase_2_payment_status')
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    public function getMyPastSubs(): array
    {
        $uid = $this->uid();
        $now = date('Y-m-d H:i:s');
        return (new UserEntriesModel())
            ->join('comp_competitions b', 'b.comp_id = comp_entries.comp_id')
            ->join('comp_type c', 'c.comp_type_id = b.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = comp_entries.winner_level', 'left')
            ->join('comp_entry_payments p1', 'p1.entry_id = comp_entries.entry_id AND p1.payment_phase = 1', 'left')
            ->join('comp_entry_payments p2', 'p2.entry_id = comp_entries.entry_id AND p2.payment_phase = 2', 'left')
            ->where('comp_entries.user_id', $uid)
            ->groupStart()
                ->groupStart()
                    ->whereIn('comp_entries.entry_status', ['Draft', 'Entrant'])
                    ->where('b.comp_phase_1_close <=', $now)
                ->groupEnd()
                ->orGroupStart()
                    ->where('comp_entries.entry_status', 'Finalist')
                    ->where('b.comp_phase_2_close <=', $now)
                ->groupEnd()
            ->groupEnd()
            ->select('comp_entries.*, b.*, c.*, w.*, p1.payment_status AS phase_1_payment_status, p2.payment_status AS phase_2_payment_status')
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    // ---------- create / update ----------

    public function createSubmission(array $form, int $compId): string
    {
        $uid = $this->uid();
        $entryId = $this->uuidV4();
        $photoId = random_int(111111111, 999999999);
        $videoEmbedUrl = isset($form['youtube_url']) ? $this->normalizeVideoStorageValue((string)$form['youtube_url']) : '';

        $this->db->transStart();

        $entryPayload = [
            'entry_id' => $entryId,
            'user_id' => $uid,
            'comp_id' => $compId,
            'photo_id' => $photoId,
            'design_name' => $form['design_name'] ?? '',
            // encode array to avoid model casting issues if column is TEXT/JSON
            'design_type_list' => is_array($form['design_type_list'] ?? null) ? json_encode($form['design_type_list']) : ($form['design_type_list'] ?? null),
            'design_type' => $form['design_type'] ?? '',
            'design_stage' => $form['design_stage'] ?? '',
            'launch_year' => $form['launch_year'] ?? '',
            'company_name' => $form['company_name'] ?? '',
            'short_description' => $form['short_description'] ?? '',
            'full_description' => $form['full_description'] ?? '',
            'designer_first_name' => $form['designer_first_name'] ?? '',
            'designer_last_name' => $form['designer_last_name'] ?? '',
            'designer_title' => $form['designer_title'] ?? '',
            'designer_phone' => $form['designer_phone'] ?? '',
            'designer_email_address' => $form['designer_email_address'] ?? '',
            'additional_team_members' => $form['additional_team_members'] ?? '',
            'series' => $form['series'] ?? '',
            'return_design' => $form['return_design'] ?? '',
            'return_number' => $form['return_number'] ?? '',
            'postal_carrier' => $form['postal_carrier'] ?? '',
            'youtube_url' => $videoEmbedUrl,
            'referred_by' => $form['referred_by'] ?? '',
            'date_created' => date('Y-m-d H:i:s'),
            'entry_status' => 'Draft',
        ];

        // Insert via builder (bypass model constraints) and verify
        $ok = $this->db->table('comp_entries')->insert($entryPayload);
        if (! $ok) {
            $this->db->transRollback();
            $dbErr = $this->db->error();
            throw new RuntimeException('Entry insert DB error: '.($dbErr['code'] ?? '0').' '.($dbErr['message'] ?? 'unknown'));
        }
        // Seed payment row
        (new EntryPaymentModel())->insert([
            'entry_id' => $entryId,
            'payment_phase' => 1,
        ], false);

        // Insert all answers on create
        $this->createAnswersInsertOnly($entryId, $form);

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            $dbErr = $this->db->error();
            throw new RuntimeException('Failed to create submission: '.($dbErr['code'] ?? '0').' '.($dbErr['message'] ?? 'unknown'));
        }

        return $entryId;
    }

    public function updateSubmission(array $form): bool
    {
        if (empty($form['entry_id'])) {
            throw new RuntimeException('entry_id required');
        }
        $entryId = (string) $form['entry_id'];
        $videoEmbedUrl = array_key_exists('youtube_url', $form)
            ? $this->normalizeVideoStorageValue((string)$form['youtube_url'])
            : null;

        $whitelist = [
            'comp_id','entry_status','winner_level','phase_1_payment','phase_2_payment',
            'internal_notes','judges_comments','gallery_hide','design_name','design_type_list','entry_non_finalist',
            'design_type','design_stage','launch_year','company_name','short_description',
            'full_description','designer_first_name','designer_last_name','designer_title','designer_phone',
            'designer_email_address','additional_team_members','series','return_design','return_number',
            'postal_carrier','referred_by','client_brandname',
        ];

        $data = [];
        foreach ($whitelist as $f) {
            if (array_key_exists($f, $form)) {
                // keep consistent with create: encode array if needed
                if ($f === 'design_type_list' && is_array($form[$f])) {
                    $data[$f] = json_encode($form[$f]);
                } else {
                    $data[$f] = $form[$f];
                }
            }
        }
        if ($videoEmbedUrl !== null) {
            $data['youtube_url'] = $videoEmbedUrl;
        }

        $this->db->transStart();

        if ($data !== []) {
            $entriesModel = new UserEntriesModel();
            $updated = $entriesModel->update($entryId, $data);
            if ($updated === false) {
                $errors = $entriesModel->errors();
                if ($errors !== []) {
                    $parts = [];
                    foreach ($errors as $field => $message) {
                        $parts[] = $field . ': ' . $message;
                    }
                    throw new RuntimeException('Entry update validation failed. ' . implode(' | ', $parts));
                }
                $dbErr = $this->db->error();
                throw new RuntimeException('Entry update failed: ' . ($dbErr['message'] ?? 'Unknown database error.'));
            }
        }

        // Update only existing answers
        $this->updateAnswersUpdateOnly($entryId, $form);

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            $dbErr = $this->db->error();
            throw new RuntimeException('Failed to update submission: '.($dbErr['code'] ?? '0').' '.($dbErr['message'] ?? 'unknown'));
        }
        return true;
    }

    public function deletePhoto(string $entryPhotoId): bool
    {
        return $this->photos->deletePhotoWithFile((int)$entryPhotoId);
    }

    public function deleteSubmission(string $entryId, ?string $userId = null): bool
    {
        $ownerId = $userId ?: $this->uid();

        $entry = (new UserEntriesModel())
            ->where('entry_id', $entryId)
            ->where('user_id', $ownerId)
            ->first();

        if (!$entry) {
            return false;
        }

        $this->db->transStart();

        $photoRows = $this->db->table('comp_entry_photos')
            ->select('entry_photo_id')
            ->where('entry_id', $entryId)
            ->get()
            ->getResultArray();

        foreach ($photoRows as $row) {
            $this->deletePhoto((string)$row['entry_photo_id']);
        }

        $this->db->table('comp_retail_item_user')->where('entry_id', $entryId)->delete();
        $this->db->table('comp_entry_answers')->where('entry_id', $entryId)->delete();
        $this->db->table('comp_entry_payments')->where('entry_id', $entryId)->delete();
        $this->db->table('comp_entries')->where('entry_id', $entryId)->delete();

        $this->db->transComplete();

        return $this->db->transStatus() !== false;
    }
}
