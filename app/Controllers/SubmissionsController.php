<?php

namespace App\Controllers;

use App\Models\Admin\CompetitionModel;
use App\Models\Judging\EntryPhotoModel;
use App\Models\Entries\EntryQuestionModel;
use App\Services\SubmissionsService;
use Config\Services;

class SubmissionsController extends BaseController
{
    private const VIDEO_URL_ERROR = 'Video URL must be a valid YouTube or Vimeo URL (for example: https://www.youtube.com/embed/VIDEO_ID or https://player.vimeo.com/video/VIDEO_ID).';
    private const SHORT_DESCRIPTION_MAX_WORDS = 250;
    private const FULL_DESCRIPTION_MAX_WORDS = 1000;
    private const PHOTO_MAX_SIZE_KB = 10240;

    private function wordCount(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        preg_match_all('/[^\s]+/u', $value, $matches);
        return count($matches[0] ?? []);
    }

    private function validateEntrantContentRules(array $form): array
    {
        $errors = [];

        $short = (string) ($form['short_description'] ?? '');
        if ($this->wordCount($short) > self::SHORT_DESCRIPTION_MAX_WORDS) {
            $errors['short_description'] = 'Short Description must be ' . self::SHORT_DESCRIPTION_MAX_WORDS . ' words or fewer.';
        }

        $full = (string) ($form['full_description'] ?? '');
        if ($this->wordCount($full) > self::FULL_DESCRIPTION_MAX_WORDS) {
            $errors['full_description'] = 'Full Description must be ' . self::FULL_DESCRIPTION_MAX_WORDS . ' words or fewer.';
        }

        $videoUrl = (string) ($form['youtube_url'] ?? '');
        if (!(new SubmissionsService())->isValidVideoEmbedUrl($videoUrl)) {
            $errors['youtube_url'] = self::VIDEO_URL_ERROR;
        }

        foreach ($form as $key => $value) {
            if (strpos((string) $key, 'entry_question_') !== 0) {
                continue;
            }
            if ($this->wordCount((string) $value) > 50) {
                $errors[(string) $key] = 'Each answer must be 50 words or fewer.';
            }
        }

        return $errors;
    }

    private function submissionRules(bool $requireFirstThree): array
    {
        $rules = [
            'design_name'             => 'required|max_length[100]',
            'company_name'            => 'required|max_length[100]',
            'short_description'       => 'required',
            'full_description'        => 'required',
            'designer_first_name'     => 'required|max_length[100]',
            'designer_last_name'      => 'required|max_length[100]',
            'designer_phone'          => 'required|max_length[25]',
            'designer_email_address'  => 'required|valid_email|max_length[100]',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $uploadRule = 'if_exist';
            if ($requireFirstThree && $i <= 3) {
                $uploadRule = 'uploaded[low_photo_' . $i . ']';
            }
            $rules["low_photo_{$i}"] = $uploadRule
                . '|max_size[low_photo_' . $i . ',' . self::PHOTO_MAX_SIZE_KB . ']'
                . '|ext_in[low_photo_' . $i . ',jpg,jpeg]'
                . '|mime_in[low_photo_' . $i . ',image/jpeg]';
        }

        return $rules;
    }

    public function index()
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }

        $ctx  = service('userctx')->current();
        $user = $ctx['user'] ?? null;
        if (! $user || ! $user->isProfileCompleted()) {
            return redirect()->to('/profile');
        }

        $svc = new SubmissionsService();

        return view('submissions/index', [
            'user'   => $user,
            'phase1' => $svc->getMyPhase1Subs(),
            'phase2' => $svc->getMyPhase2Subs(),
            'phase3' => $svc->getMyPhase3Subs(),
            'past'   => $svc->getMyPastSubs(),
        ]);
    }

    public function create(?int $compId = null)
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }

        $ctx  = service('userctx')->current();
        $user = $ctx['user'] ?? null;

        $compId = $compId ?? (int) ($this->request->getGet('comp_id') ?? 0);
        if ($compId <= 0) {
            return redirect()->to('/competitions')->with('error', 'Select a competition first.');
        }

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first();
        if (! $comp) {
            return redirect()->to('/competitions')->with('error', 'Invalid competition.');
        }

        $svc = new SubmissionsService();

        $designTypes = $svc->getDesignTypes((int) $comp->comp_type_id);
        $questions   = (new EntryQuestionModel())
            ->orderBy('entry_question_order', 'ASC')
            ->findAll();

        return view('submissions/form', [
            'isEdit'      => false,
            'user'        => $user,
            'entry'       => null,
            'videoEmbedUrl' => '',
            'competition' => $comp,
            'compId'      => (int) $compId,
            'designTypes' => $designTypes,
            'questions'   => $questions,
            'answersMap'  => [],
            'photosLow'   => [],
        ]);
    }

    public function store()
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }

        $compId = (int) $this->request->getPost('comp_id');
        if ($compId <= 0) {
            return redirect()->back()->withInput()->with('errors', ['general' => 'Invalid competition.']);
        }

        $rules = $this->submissionRules(true);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $svc  = new SubmissionsService();
        $form = $this->request->getPost();
        $contentErrors = $this->validateEntrantContentRules($form);
        if ($contentErrors !== []) {
            return redirect()->back()->withInput()->with('errors', $contentErrors);
        }

        $entryId = $svc->createSubmission($form, $compId);

        $photoErrors = $this->processImages($entryId, $compId, 'Low', 1, 10, true);
        if ($photoErrors !== null) {
            return redirect()->back()->withInput()->with('errors', $photoErrors);
        }

        if ($this->request->getPost('savePreview')) {
            return redirect()->to('/submissions/preview/' . rawurlencode($entryId));
        }

        if ($this->request->getPost('submitPayment')) {
            return redirect()->to('/payments/entry/' . rawurlencode($entryId) . '/phase/1')->with('success', 'Submission saved. Proceed to payment.');
        }

        return redirect()->to('/submissions')->with('success', 'Submission saved as draft.');
    }

    public function update(string $entryId)
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }

        $ctx  = service('userctx')->current();
        $user = $ctx['user'] ?? null;

        $svc   = new SubmissionsService();
        $entry = $svc->getEntryByID($entryId);
        if (! $entry) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', (int) $entry->comp_id)
            ->first();
        $isReadOnly = $comp && $this->isEditLockedForStatus((string)($entry->entry_status ?? ''), $comp);

        $designTypes = $svc->getDesignTypes((int) $comp->comp_type_id);
        $questions   = (new EntryQuestionModel())
            ->orderBy('entry_question_order', 'ASC')
            ->findAll();
        $answersMap = [];
        $answers = (new \App\Models\Entries\EntryAnswersModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->findAll();
        foreach ($answers as $a) {
            $answersMap[(int)$a['entry_question_id']] = (string)$a['entry_answer'];
        }
        $photosLow   = (new EntryPhotoModel())
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', 'Low')
            ->orderBy('entry_photo_order', 'ASC')
            ->findAll();

        return view('submissions/form', [
            'isEdit'      => true,
            'isReadOnly'  => $isReadOnly,
            'user'        => $user,
            'entry'       => $entry,
            'videoEmbedUrl' => $svc->normalizeVideoEmbedUrl((string)($entry->youtube_url ?? '')),
            'competition' => $comp,
            'compId'      => (int) $entry->comp_id,
            'designTypes' => $designTypes,
            'questions'   => $questions,
            'answersMap'  => $answersMap,
            'photosLow'   => $photosLow,
        ]);
    }

    public function updatePost(string $entryId)
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }

        $svc   = new SubmissionsService();
        $entry = $svc->getEntryByID($entryId);
        if (! $entry) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }
        $comp = (new CompetitionModel())
            ->where('comp_id', (int) $entry->comp_id)
            ->first();
        if ($comp && $this->isEditLockedForStatus((string)($entry->entry_status ?? ''), $comp)) {
            return redirect()->to('/submissions')->with('error', 'This submission is locked during judging and cannot be edited right now.');
        }

        $rules = $this->submissionRules(false);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Build form array + entry_id for service
        $form            = $this->request->getPost();
        $contentErrors   = $this->validateEntrantContentRules($form);
        if ($contentErrors !== []) {
            return redirect()->back()->withInput()->with('errors', $contentErrors);
        }

        $existingByOrder = [];
        $existingLow = (new EntryPhotoModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', 'Low')
            ->whereIn('entry_photo_order', [1, 2, 3])
            ->findAll();
        foreach ($existingLow as $row) {
            $existingByOrder[(int) ($row['entry_photo_order'] ?? 0)] = true;
        }
        $requiredPhotoErrors = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = $this->request->getFile('low_photo_' . $i);
            $hasUpload = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;
            if (!isset($existingByOrder[$i]) && !$hasUpload) {
                $requiredPhotoErrors['low_photo_' . $i] = 'Photo ' . $i . ' is required. Please upload a JPG image.';
            }
        }
        if ($requiredPhotoErrors !== []) {
            return redirect()->back()->withInput()->with('errors', $requiredPhotoErrors);
        }
        $form['entry_id'] = $entryId;

        // Update scalar fields + Q&A
        $svc->updateSubmission($form);

        // Process any replaced images
        $photoErrors = $this->processImages($entryId, (int) $entry->comp_id, 'Low', 1, 10, false);
        if ($photoErrors !== null) {
            return redirect()->back()->withInput()->with('errors', $photoErrors);
        }

        if ($this->request->getPost('savePreview')) {
            return redirect()->to('/submissions/preview/' . rawurlencode($entryId));
        }

        $phase1Paid = strtoupper((string)($entry->phase_1_payment ?? 'Unpaid')) === 'PAID';
        if ($this->request->getPost('submitPayment') && ! $phase1Paid) {
            return redirect()->to('/payments/entry/' . rawurlencode($entryId) . '/phase/1')->with('success', 'Submission updated. Proceed to payment.');
        }

        return redirect()->to('/submissions')->with('success', 'Submission updated.');
    }

    public function delete(string $entryId)
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }
        if ((string)session('role') === 'editor') {
            return redirect()->to('/submissions')->with('error', 'Editors are not allowed to delete records.');
        }

        $svc = new SubmissionsService();

        try {
            $ok = $svc->deleteSubmission($entryId);
            if (! $ok) {
                return redirect()->to('/submissions')->with('error', 'Entry not found or not owned by you.');
            }
            return redirect()->to('/submissions')->with('success', 'Entry successfully deleted.');
        } catch (\Throwable $e) {
            return redirect()->to('/submissions')->with('error', 'Could not delete entry at this time.');
        }
    }

    // (Optional) delete a photo by entry_photo_id (called via POST)
    public function deletePhoto(int $entryPhotoId)
    {
        if (! session('uid')) {
            return $this->response->setStatusCode(401);
        }
        if ((string)session('role') === 'editor') {
            return $this->response->setStatusCode(403);
        }
        $photo = (new EntryPhotoModel())->asArray()->find($entryPhotoId);
        if (! $photo) {
            return $this->response->setStatusCode(404);
        }
        if ((string) ($photo['entry_photo_res'] ?? '') === 'Low' && (int) ($photo['entry_photo_order'] ?? 0) <= 3) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'ok' => false,
                    'error' => 'Photos 1-3 are required and cannot be deleted.',
                ]);
            }
            return redirect()->back()->with('error', 'Photos 1-3 are required and cannot be deleted.');
        }
        // Ownership check: ensure photo's entry belongs to current user
        $svc   = new SubmissionsService();
        $entry = $svc->getEntryByID((string) $photo['entry_id']);
        if (! $entry) {
            return $this->response->setStatusCode(403);
        }

        $ok = $svc->deletePhoto((string) $entryPhotoId);
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => (bool) $ok]);
        }

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Photo deleted.' : 'Could not delete photo.');
    }

    // ---------- helpers ----------

    public function preview(string $entryId)
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }

        $svc = new SubmissionsService();
        $entry = $svc->getEntryByID($entryId);
        if (! $entry) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $competition = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', (int) $entry->comp_id)
            ->first();

        $photos = (new EntryPhotoModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', 'Low')
            ->orderBy('entry_photo_order', 'ASC')
            ->findAll();

        return view('submissions/preview', [
            'entry' => $entry,
            'competition' => $competition,
            'photos' => $photos,
            'videoEmbed' => $svc->getVideoEmbedData((string) ($entry->youtube_url ?? '')),
        ]);
    }

    private function processImages(string $entryId, int $compId, string $resLabel, int $from, int $to, bool $requireAtLeastThree): ?array
    {
        $imageService = Services::image();

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first();

        $year     = (int) ($comp->comp_year ?? date('Y'));
        $typeName = strtolower(trim((string) ($comp->comp_type_name ?? 'unknown')));
        $typeDir  = str_replace(['/', '\\'], '-', $typeName);
        $baseDir  = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $year
            . DIRECTORY_SEPARATOR . $typeDir;

        if (! is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'index.html', '');
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . '.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx)$\">\nRequire all denied\n</FilesMatch>\n");
        }

        $photoModel = new EntryPhotoModel();

        // Fetch the photo_id from the submission so we can name files like CompPhotoLow_<n>_<photo_id>.jpg
        $entryRow = (new \App\Models\Entries\UserEntriesModel())->where('entry_id', $entryId)->first();
        $photoId  = $entryRow && isset($entryRow->photo_id) ? (string)$entryRow->photo_id : '';

        $saved = 0;
        for ($i = $from; $i <= $to; $i++) {
            $file = $this->request->getFile('low_photo_' . $i);
            $caption = (string) ($this->request->getPost('low_caption_' . $i) ?? '');

            if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
                // Update caption if present (existing row)
                $existing = $photoModel->where('entry_id', $entryId)
                    ->where('entry_photo_res', $resLabel)
                    ->where('entry_photo_order', $i)
                    ->first();
                if ($existing && $caption !== '') {
                    $photoModel->update((int) $existing->entry_photo_id, ['entry_photo_caption' => $caption]);
                }
                continue;
            }
            if (! $file->isValid()) {
                return ['photo' . $i => $file->getErrorString()];
            }

            $clientExt = strtolower((string) $file->getClientExtension());
            if (! in_array($clientExt, ['jpg', 'jpeg'], true)) {
                return ['photo' . $i => 'Only valid JPG images are allowed.'];
            }

            $imgInfo = @getimagesize($file->getTempName());
            if (!is_array($imgInfo) || ($imgInfo['mime'] ?? '') !== 'image/jpeg') {
                return ['photo' . $i => 'Only valid JPG images are allowed.'];
            }

            // Name format: CompPhotoLow_<order>_<photo_id>.jpg
            $safeName = 'CompPhotoLow_' . $i . '_' . $photoId . '.jpg';
            $target   = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

            try {
                $imageService->withFile($file->getTempName())
                    ->resize(2000, 2000, true, 'auto')
                    ->save($target, 85);

                $saved++;
                $publicUrl = '/uploads/' . $year . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $typeDir) . '/' . $safeName;

                // Upsert row for this order
                $existing = $photoModel->where('entry_id', $entryId)
                    ->where('entry_photo_res', $resLabel)
                    ->where('entry_photo_order', $i)
                    ->first();

                $data = [
                    'entry_id'            => $entryId,
                    'entry_photo'         => $publicUrl,
                    'entry_photo_caption' => $caption,
                    'entry_photo_res'     => $resLabel,
                    'entry_photo_order'   => $i,
                ];

                if ($existing) {
                    $photoModel->update((int) $existing->entry_photo_id, $data);
                } else {
                    $photoModel->insert($data, false);
                }
            } catch (\Throwable $e) {
                return ['photo' . $i => 'Image processing failed.'];
            }
        }

        if ($requireAtLeastThree && $saved < 3) {
            return ['photos' => 'Please upload at least three JPG images (10 MB max each).'];
        }

        return null;
    }

    private function isEditLockedForStatus(string $entryStatus, object $competition): bool
    {
        $now = date('Y-m-d H:i:s');
        $status = trim($entryStatus);

        if ($status === 'Draft' || $status === 'Entrant') {
            $open = trim((string)($competition->jury_phase_1_open ?? ''));
            $close = trim((string)($competition->jury_phase_1_close ?? ''));
            if ($open !== '' && $close !== '' && $open <= $now && $close > $now) {
                return true;
            }
        }

        if ($status === 'Finalist') {
            $open = trim((string)($competition->jury_phase_2_open ?? ''));
            $close = trim((string)($competition->jury_phase_2_close ?? ''));
            if ($open !== '' && $close !== '' && $open <= $now && $close > $now) {
                return true;
            }
        }

        return false;
    }
}
