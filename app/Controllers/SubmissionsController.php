<?php

namespace App\Controllers;

use App\Models\Admin\CompetitionModel;
use App\Models\Judging\EntryPhotoModel;
use App\Models\Entries\EntryQuestionModel;
use App\Services\SubmissionsService;
use Config\Services;

class SubmissionsController extends BaseController
{
    private function wordCount(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        preg_match_all('/[^\s]+/u', $value, $matches);
        return count($matches[0] ?? []);
    }

    private function isValidYoutubeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return true;
        }
        if (preg_match('/^[\w\-]{6,25}$/', $url) === 1) {
            return true;
        }

        $patterns = [
            '#^https?://youtu\.be/[\w\-]{6,25}(?:\?.*)?$#i',
            '#^https?://(?:www\.)?youtube\.com/watch\?v=[\w\-]{6,25}(?:&.*)?$#i',
            '#^https?://(?:www\.)?youtube\.com/embed/[\w\-]{6,25}(?:\?.*)?$#i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url) === 1) {
                return true;
            }
        }
        return false;
    }

    private function validateEntrantContentRules(array $form): array
    {
        $errors = [];

        $short = (string) ($form['short_description'] ?? '');
        if ($this->wordCount($short) > 50) {
            $errors['short_description'] = lang('Entrant.short_description_word_limit_error');
        }

        $full = (string) ($form['full_description'] ?? '');
        if ($this->wordCount($full) > 200) {
            $errors['full_description'] = lang('Entrant.full_description_word_limit_error');
        }

        $youtube = (string) ($form['youtube_url'] ?? '');
        if (! $this->isValidYoutubeUrl($youtube)) {
            $errors['youtube_url'] = lang('Entrant.youtube_share_url_invalid');
        }

        foreach ($form as $key => $value) {
            if (strpos((string) $key, 'entry_question_') !== 0) {
                continue;
            }
            if ($this->wordCount((string) $value) > 50) {
                $errors[(string) $key] = lang('Entrant.question_answer_word_limit_error');
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
                . '|max_size[low_photo_' . $i . ',1024]'
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
            return redirect()->to('/competitions')->with('error', lang('Entrant.select_competition_first'));
        }

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first();
        if (! $comp) {
            return redirect()->to('/competitions')->with('error', lang('Entrant.invalid_competition'));
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
            return redirect()->back()->withInput()->with('errors', ['general' => lang('Entrant.invalid_competition')]);
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

        if ($this->request->getPost('submitPayment')) {
            return redirect()->to('/payments/entry/' . rawurlencode($entryId) . '/phase/1')->with('success', lang('Entrant.submission_saved_proceed_payment'));
        }

        return redirect()->to('/submissions')->with('success', lang('Entrant.submission_saved_draft'));
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
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
        }

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', (int) $entry->comp_id)
            ->first();

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
            'user'        => $user,
            'entry'       => $entry,
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
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
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
                $requiredPhotoErrors['low_photo_' . $i] = lang('Entrant.photo_slot_required', [$i]);
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

        return redirect()->to('/submissions')->with('success', lang('Entrant.submission_updated'));
    }

    public function delete(string $entryId)
    {
        if (! session('uid')) {
            return redirect()->to('/auth/login');
        }
        if ((string)session('role') === 'editor') {
            return redirect()->to('/submissions')->with('error', lang('Entrant.editors_cannot_delete'));
        }

        $svc = new SubmissionsService();

        try {
            $ok = $svc->deleteSubmission($entryId);
            if (! $ok) {
                return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found_or_owned'));
            }
            return redirect()->to('/submissions')->with('success', lang('Entrant.entry_deleted'));
        } catch (\Throwable $e) {
            return redirect()->to('/submissions')->with('error', lang('Entrant.unable_delete_entry'));
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
                    'error' => lang('Entrant.first_three_photos_cannot_delete'),
                ]);
            }
            return redirect()->back()->with('error', lang('Entrant.first_three_photos_cannot_delete'));
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

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? lang('Entrant.photo_deleted') : lang('Entrant.unable_delete_photo'));
    }

    // ---------- helpers ----------

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
                return ['photo' . $i => lang('Entrant.only_valid_jpg')];
            }

            $imgInfo = @getimagesize($file->getTempName());
            if (!is_array($imgInfo) || ($imgInfo['mime'] ?? '') !== 'image/jpeg') {
                return ['photo' . $i => lang('Entrant.only_valid_jpg')];
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
                return ['photo' . $i => lang('Entrant.image_processing_failed')];
            }
        }

        if ($requireAtLeastThree && $saved < 3) {
            return ['photos' => lang('Entrant.upload_at_least_three_jpg')];
        }

        return null;
    }
}
