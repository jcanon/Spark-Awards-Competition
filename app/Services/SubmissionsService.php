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
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function youtubeIdFromUrl(?string $url): string
    {
        if (!$url) {
            return '';
        }
        $url = trim($url);
        $patterns = [
            '#https?://youtu\.be/([\w\-]{6,25}).*$#i',
            '#https?://(?:www\.)?youtube\.com/watch\?v=([\w\-]{6,25}).*$#i',
            '#https?://(?:www\.)?youtube\.com/embed/([\w\-]{6,25}).*$#i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $url, $m)) {
                return $m[1] ?? '';
            }
        }
        return strlen($url) <= 25 ? $url : '';
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
            ->where('comp_entries.user_id', $uid)
            ->groupStart()
            ->where('b.comp_phase_1_open <=', $now)
            ->where('b.comp_phase_1_close >', $now)
            ->groupEnd()
            ->groupStart()
            ->where('comp_entries.entry_status', 'Draft')
            ->orWhere('comp_entries.entry_status', 'Entrant')
            ->groupEnd()
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    public function getMyPhase2Subs(): array
    {
        $uid = $this->uid();
        return (new UserEntriesModel())
            ->join('comp_competitions b', 'b.comp_id = comp_entries.comp_id')
            ->join('comp_type c', 'c.comp_type_id = b.comp_type_id')
            ->where('comp_entries.user_id', $uid)
            ->where('comp_entries.entry_status', 'Finalist')
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    public function getMyPhase3Subs(): array
    {
        $uid = $this->uid();
        return (new UserEntriesModel())
            ->join('comp_competitions b', 'b.comp_id = comp_entries.comp_id')
            ->join('comp_type c', 'c.comp_type_id = b.comp_type_id')
            ->where('comp_entries.user_id', $uid)
            ->where('comp_entries.entry_status', 'Winner')
            ->orderBy('c.comp_type_name ASC, comp_entries.design_name ASC')
            ->findAll();
    }

    public function getMyPastSubs(): array
    {
        $uid = $this->uid();
        $now = date('Y-m-d H:i:s');

        $rows = $this->db->query(
            "SELECT comp_entries.*
             FROM comp_entries
             INNER JOIN comp_competitions b ON comp_entries.comp_id = b.comp_id
             INNER JOIN comp_type c ON b.comp_type_id = c.comp_type_id
             WHERE comp_entries.user_id = ?
               AND b.comp_id NOT IN (
                    SELECT comp_id FROM comp_competitions
                    WHERE comp_phase_1_open <= ? AND comp_phase_1_close > ?
               )
               AND b.comp_id NOT IN (
                    SELECT comp_id FROM comp_competitions
                    WHERE comp_phase_2_open <= ? AND comp_phase_2_close > ?
               )
               AND b.comp_id NOT IN (
                    SELECT b2.comp_id
                      FROM comp_entries a2
                      INNER JOIN comp_competitions b2 ON a2.comp_id = b2.comp_id
                     WHERE a2.entry_status = 'Winner' OR a2.entry_status = 'Finalist'
               )
             ORDER BY c.comp_type_name ASC, comp_entries.design_name ASC",
            [$uid, $now, $now, $now, $now]
        )->getResultArray();

        $model = new UserEntriesModel();
        $out = [];
        foreach ($rows as $r) {
            $out[] = $model->where('entry_id', $r['entry_id'])->first();
        }
        return array_values(array_filter($out));
    }

    // ---------- create / update ----------

    public function createSubmission(array $form, int $compId): string
    {
        $uid = $this->uid();
        $entryId = $this->uuidV4();
        $photoId = random_int(111111111, 999999999);
        $youtube = isset($form['youtube_url']) ? $this->youtubeIdFromUrl($form['youtube_url']) : '';

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
            'youtube_url' => $youtube,
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
        $youtube = array_key_exists('youtube_url', $form)
            ? $this->youtubeIdFromUrl($form['youtube_url'])
            : null;

        $whitelist = [
            'comp_id','entry_status','winner_level','phase_1_payment','phase_2_payment',
            'internal_notes','judges_comments','gallery_hide','design_name','design_type_list','entry_non_finalist',
            'shortlist','design_type','design_stage','launch_year','company_name','short_description',
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
        if ($youtube !== null) {
            $data['youtube_url'] = $youtube;
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
