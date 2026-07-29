<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Entries\EntryAnswersModel;
use App\Models\Admin\CompetitionModel;
use App\Models\Judging\EntryPhotoModel;
use App\Services\SubmissionsService;
use App\Services\Admin\SubmissionsAdminService;
use Config\Services;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SubmissionsController extends BaseController
{
    private const VIDEO_URL_ERROR = 'Video URL must be a valid YouTube or Vimeo URL (for example: https://www.youtube.com/embed/VIDEO_ID or https://player.vimeo.com/video/VIDEO_ID).';
    private const SHORT_DESCRIPTION_MAX_WORDS = 250;
    private const FULL_DESCRIPTION_MAX_WORDS = 1000;
    private const PHOTO_MAX_SIZE_KB = 10240;

    private SubmissionsAdminService $adminSubs;

    public function __construct()
    {
        $this->adminSubs = new SubmissionsAdminService();
    }

    public function index()
    {
        $years = $this->adminSubs->getCompetitionYears();
        $defaultYear = (int)($years[0]['comp_year'] ?? date('Y'));
        $filters = $this->getFilters($defaultYear);
        $rows = $this->adminSubs->list($filters);

        return view('admin/submissions/index', [
            'rows' => $rows,
            'filters' => $filters,
            'years' => $years,
            'types' => $this->adminSubs->getCompetitionTypesByYear((int)$filters['compYear']),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function create()
    {
        $years = $this->adminSubs->getCompetitionYears();
        $defaultYear = (int)($years[0]['comp_year'] ?? date('Y'));
        $filters = $this->getFilters($defaultYear);

        return view('admin/submissions/create', [
            'filters' => $filters,
            'years' => $years,
            'types' => $this->adminSubs->getCompetitionTypesByYear((int)$filters['compYear']),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function userSearch()
    {
        $q = trim((string)$this->request->getGet('q'));
        $rows = service('profiles')->searchUsersForSubmission($q, 50);

        $data = [];
        foreach ($rows as $row) {
            $label = trim((string)($row['last_name'] ?? '') . ', ' . (string)($row['first_name'] ?? '') . ' - ' . (string)($row['email_address'] ?? ''));
            $data[] = [
                'id' => (string)($row['user_id'] ?? ''),
                'label' => $label,
            ];
        }

        return $this->response->setJSON([
            'data' => $data,
        ]);
    }

    public function store()
    {
        $rules = [
            'user_id' => 'required|max_length[35]',
            'comp_id' => 'required|integer',
            'design_name' => 'required|max_length[100]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Invalid submission data.');
        }

        $entryId = $this->adminSubs->create($this->request->getPost());
        return redirect()->to('/admin/submissions/edit/' . rawurlencode($entryId))->with('success', 'Submission created.');
    }

    public function edit(string $entryId)
    {
        $row = $this->adminSubs->find($entryId);
        if (!$row) {
            return redirect()->to('/admin/submissions')->with('error', 'Submission not found.');
        }

        $answers = (new EntryAnswersModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->findAll();
        $answersMap = [];
        foreach ($answers as $answer) {
            $answersMap[(int)$answer['entry_question_id']] = (string)$answer['entry_answer'];
        }

        $photos = (new EntryPhotoModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->orderBy('entry_photo_order', 'ASC')
            ->findAll();
        $photos = $this->ensureCertificateThumbnailForEdit($row, $photos);

        $designTypes = (new SubmissionsService())->getDesignTypes((int)($row['comp_type_id'] ?? 0));

        $addOnLines = [];
        $paymentSvc = service('payments');
        foreach ($paymentSvc->getAddonItems() as $addon) {
            $entryAddon = $paymentSvc->addonItemUser($entryId, (int)$addon['retail_item_id']);
            $qty = (int)($entryAddon['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $price = (float)$addon['item_price'];
            $addOnLines[] = [
                'name' => (string)$addon['item_name'],
                'qty' => $qty,
                'total' => round($qty * $price, 2),
            ];
        }

        return view('admin/submissions/edit', [
            'row' => $row,
            'questions' => $this->adminSubs->questions(),
            'answersMap' => $answersMap,
            'photos' => $photos,
            'videoEmbedUrl' => (new SubmissionsService())->normalizeVideoEmbedUrl((string)($row['youtube_url'] ?? '')),
            'designTypes' => $designTypes,
            'addonLines' => $addOnLines,
            'winnerLevels' => $this->adminSubs->getWinnerLevels(),
            'types' => service('competitions')->getAllCompetitions(),
            'canDelete' => $this->canDelete(),
            'payments' => service('payments')->getAllPaymentDetails($entryId),
        ]);
    }

    public function copy(string $entryId)
    {
        $row = $this->adminSubs->find($entryId);
        if (!$row) {
            return redirect()->to('/admin/submissions')->with('error', 'Submission not found.');
        }

        $year = (int)($this->request->getGet('compYear') ?? ($row['comp_year'] ?? date('Y')));
        $competitions = $this->adminSubs->getCompetitionTypesByYear($year);

        return view('admin/submissions/copy', [
            'row' => $row,
            'year' => $year,
            'years' => $this->adminSubs->getCompetitionYears(),
            'competitions' => $competitions,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function copyPost(string $entryId)
    {
        $row = $this->adminSubs->find($entryId);
        if (!$row) {
            return redirect()->to('/admin/submissions')->with('error', 'Submission not found.');
        }

        $rules = [
            'comp_id' => 'required|integer',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid destination competition.');
        }

        $newCompId = (int)$this->request->getPost('comp_id');
        $newEntryId = $this->adminSubs->copyToCompetition($entryId, $newCompId);
        if (!$newEntryId) {
            return redirect()->back()->withInput()->with('error', 'Submission copy failed.');
        }

        return redirect()->to('/admin/submissions/edit/' . rawurlencode($newEntryId))->with('success', 'Submission copied successfully.');
    }

    public function update(string $entryId)
    {
        $rules = [
            'design_name' => 'required|max_length[100]',
            'company_name' => 'permit_empty|max_length[100]',
            'entry_status' => 'required|in_list[Draft,Entrant,Finalist,Winner]',
            'winner_level' => 'permit_empty|integer',
            'designer_first_name' => 'required|max_length[100]',
            'designer_last_name' => 'required|max_length[100]',
            'designer_phone' => 'required|max_length[25]',
            'designer_email_address' => 'required|valid_email|max_length[100]',
            'short_description' => 'required|max_length[2500]',
            'full_description' => 'required|max_length[10000]',
            'youtube_url' => 'permit_empty|max_length[255]',
        ];
        for ($i = 1; $i <= 10; $i++) {
            $rules['low_photo_' . $i] = 'if_exist|max_size[low_photo_' . $i . ',' . self::PHOTO_MAX_SIZE_KB . ']|ext_in[low_photo_' . $i . ',jpg,jpeg]|mime_in[low_photo_' . $i . ',image/jpeg]';
        }
        $rules['certificate_pdf'] = 'if_exist|max_size[certificate_pdf,5120]|ext_in[certificate_pdf,pdf]|mime_in[certificate_pdf,application/pdf]';
        $rules['winner_badge'] = 'if_exist|max_size[winner_badge,5120]|is_image[winner_badge]';

        $badgeFile = $this->request->getFile('winner_badge');
        if (!$this->canManageBadge() && $badgeFile && $badgeFile->getError() !== UPLOAD_ERR_NO_FILE) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Only admins can upload a Winner Badge.');
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Submission validation failed. Please review the highlighted fields.')
                ->with('errors', $this->validator ? $this->validator->getErrors() : []);
        }

        $contentErrors = $this->validateEntrantContentRules((array)$this->request->getPost());
        if ($contentErrors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Submission validation failed. Please review the highlighted fields.')
                ->with('errors', $contentErrors);
        }

        $videoUrl = (string)$this->request->getPost('youtube_url');
        if (!(new SubmissionsService())->isValidVideoEmbedUrl($videoUrl)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Submission validation failed. Please review the highlighted fields.')
                ->with('errors', ['youtube_url' => self::VIDEO_URL_ERROR]);
        }

        $requiredPhotoErrors = $this->validateRequiredLowPhotos($entryId);
        if ($requiredPhotoErrors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Submission validation failed. Please review the highlighted fields.')
                ->with('errors', $requiredPhotoErrors);
        }

        $ok = false;
        $errorMessage = 'Submission update failed.';

        try {
            $post = (array)$this->request->getPost();
            // These legacy fields were intentionally removed from admin edit flow.
            unset(
                $post['launch_year'],
                $post['return_design'],
                $post['postal_carrier'],
                $post['return_number'],
                $post['designer_title']
            );
            $ok = $this->adminSubs->update($entryId, $post);
            if ($ok) {
                $entry = $this->adminSubs->find($entryId);
                if ($entry) {
                    $photoErrors = $this->processImages($entryId, (int)$entry['comp_id'], 'Low', 1, 10);
                    if ($photoErrors !== null) {
                        return redirect()->back()->withInput()->with('error', implode(' ', $photoErrors));
                    }
                    $certErrors = $this->processCertificate($entryId, (int)$entry['comp_id']);
                    if ($certErrors !== null) {
                        return redirect()->back()->withInput()->with('error', implode(' ', $certErrors));
                    }
                    if ($this->canManageBadge()) {
                        $badgeErrors = $this->processWinnerBadge($entryId, (int)$entry['comp_id']);
                        if ($badgeErrors !== null) {
                            return redirect()->back()->withInput()->with('error', implode(' ', $badgeErrors));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Admin submission update failed for {entryId}: {message}', [
                'entryId' => $entryId,
                'message' => $e->getMessage(),
            ]);
            $ok = false;
            $errorMessage = 'Submission update failed: ' . $e->getMessage();
        }

        if (!$ok) {
            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }

        return redirect()->to('/admin/submissions/edit/' . rawurlencode($entryId))->with('success', 'Submission updated.');
    }

    public function delete(string $entryId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/submissions')->with('error', 'Editors are not allowed to delete records.');
        }

        if (!$this->adminSubs->delete($entryId)) {
            return redirect()->to('/admin/submissions')->with('error', 'Submission could not be deleted.');
        }

        return redirect()->to('/admin/submissions')->with('success', 'Submission deleted.');
    }

    public function bulk()
    {
        $action = (string)$this->request->getPost('action');
        $ids = $this->request->getPost('entry_ids') ?? [];
        if ($action === '') {
            return redirect()->back()->with('error', 'Please choose a bulk update option.');
        }
        if (!is_array($ids) || $ids === []) {
            return redirect()->back()->with('error', 'No entries selected.');
        }

        if ($action === 'delete' && !$this->canDelete()) {
            return redirect()->back()->with('error', 'Editors are not allowed to delete records.');
        }

        if ($action === 'delete') {
            $count = 0;
            foreach ($ids as $id) {
                if ($this->adminSubs->delete((string)$id)) {
                    $count++;
                }
            }
            return redirect()->back()->with('success', 'Deleted ' . $count . ' submission(s).');
        }

        $count = $this->adminSubs->bulkAction($action, $ids);
        return redirect()->back()->with('success', 'Updated ' . $count . ' submission(s).');
    }

    public function export()
    {
        $filters = $this->getFilters();
        $rows = $this->adminSubs->export($filters);
        $judgingRows = $this->adminSubs->exportJudging($filters);
        $binary = $this->toXlsx($rows, $judgingRows);
        $filename = 'submissions-export-' . date('Ymd_His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($binary);
    }

    public function judgingCards()
    {
        $filters = [
            'compYear' => (int)($this->request->getGet('compYear') ?? date('Y')),
            'compType' => (string)($this->request->getGet('compType') ?? 'ALL'),
            'compPhase' => (int)($this->request->getGet('compPhase') ?? 1),
            'excludeNonFinalists' => (string)($this->request->getGet('excludeNonFinalists') ?? 'No'),
        ];

        return view('admin/submissions/judging_cards', [
            'rows' => $this->adminSubs->judgingCards($filters),
            'filters' => $filters,
            'years' => $this->adminSubs->getCompetitionYears(),
            'types' => $this->adminSubs->getCompetitionTypesByYear((int)$filters['compYear']),
        ]);
    }

    public function receipt(int $paymentId, string $entryId)
    {
        $row = service('payments')->getPaymentReceipt($paymentId, $entryId);
        if (!$row) {
            return redirect()->to('/admin/submissions/edit/' . rawurlencode($entryId))->with('error', 'Receipt not found.');
        }

        $row['payment_receipt'] = $this->resolveLegacyReceiptTemplate((string)($row['payment_receipt'] ?? ''), $row, $entryId);

        return view('admin/submissions/receipt', [
            'entryId' => $entryId,
            'payment' => $row,
        ]);
    }

    public function receiptContent(int $paymentId, string $entryId)
    {
        $row = service('payments')->getPaymentReceipt($paymentId, $entryId);
        if (!$row) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('<div class="alert alert-danger mb-0">Receipt not found.</div>');
        }

        $row['payment_receipt'] = $this->resolveLegacyReceiptTemplate((string)($row['payment_receipt'] ?? ''), $row, $entryId);

        return $this->response->setBody(
            view('admin/submissions/_receipt_content', ['payment' => $row])
        );
    }

    public function receiptPdf(int $paymentId, string $entryId)
    {
        $row = service('payments')->getPaymentReceipt($paymentId, $entryId);
        if (!$row) {
            return redirect()->to('/admin/submissions/edit/' . rawurlencode($entryId))->with('error', 'Receipt not found.');
        }

        $row['payment_receipt'] = $this->resolveLegacyReceiptTemplate((string)($row['payment_receipt'] ?? ''), $row, $entryId);
        $entryMeta = $this->loadReceiptEntryContext($entryId);

        helper('html_sanitize');

        $html = view('receipt/pdf', [
            'payment' => $row,
            'entryId' => $entryId,
            'entryMeta' => $entryMeta,
            'logoPath' => FCPATH . 'img/sparklogo.jpg',
            'title' => 'Spark Awards Payment Receipt',
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $fileName = 'spark-receipt-' . $entryId . '-payment-' . $paymentId . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($dompdf->output());
    }

    public function deletePhoto(int $entryPhotoId)
    {
        if (!$this->canDelete()) {
            return redirect()->back()->with('error', 'Editors are not allowed to delete records.');
        }

        $ok = (new SubmissionsService())->deletePhoto((string)$entryPhotoId);
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Photo deleted.' : 'Photo could not be deleted.');
    }

    public function deleteCertificate(int $entryPhotoId)
    {
        if (!$this->canDelete()) {
            return redirect()->back()->with('error', 'Editors are not allowed to delete records.');
        }

        $photoModel = new EntryPhotoModel();
        $photo = $photoModel->asArray()->find($entryPhotoId);
        if (!$photo) {
            return redirect()->back()->with('error', 'Certificate not found.');
        }

        $certificatePublicPath = trim((string)($photo['entry_certificate'] ?? ''));
        if ($certificatePublicPath !== '' && $this->isManagedUploadPublicPath($certificatePublicPath)) {
            $certificateAbsolutePath = $this->publicUrlToAbsolutePath($certificatePublicPath);
            if (is_file($certificateAbsolutePath)) {
                @unlink($certificateAbsolutePath);
            }
        }

        $isPdfRow = (string)($photo['entry_photo_res'] ?? '') === 'PDF';
        $updateData = ['entry_certificate' => ''];
        if ($isPdfRow) {
            $thumbnailPublicPath = trim((string)($photo['entry_photo'] ?? ''));
            if ($thumbnailPublicPath !== '' && $this->isManagedUploadPublicPath($thumbnailPublicPath)) {
                $thumbnailAbsolutePath = $this->publicUrlToAbsolutePath($thumbnailPublicPath);
                if (is_file($thumbnailAbsolutePath)) {
                    @unlink($thumbnailAbsolutePath);
                }
            }
            $updateData['entry_photo'] = '';
        }

        $ok = $photoModel->skipValidation(true)->update($entryPhotoId, $updateData);
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Certificate PDF removed.' : 'Certificate could not be removed.');
    }

    public function generateCertificate(string $entryId)
    {
        $entry = $this->adminSubs->find($entryId);
        if (!$entry) {
            return redirect()->to('/admin/submissions')->with('error', 'Submission not found.');
        }

        $errors = $this->generateCertificateFiles($entry);
        if ($errors !== null) {
            return redirect()->back()->with('error', implode(' ', $errors));
        }

        return redirect()->to('/admin/submissions/edit/' . rawurlencode($entryId))->with('success', 'Certificate generated and assigned.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }

    private function canManageBadge(): bool
    {
        return (string)session('role') === 'admin';
    }

    private function resolveLegacyReceiptTemplate(string $rawReceipt, array $payment, string $entryId): string
    {
        if ($rawReceipt === '' || strpos($rawReceipt, '#') === false) {
            return $rawReceipt;
        }

        $ctx = db_connect()->table('comp_entries e')
            ->select('e.design_name, u.first_name, u.last_name, u.address1, u.city, u.state, u.zipcode, u.country, u.email_address')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray() ?? [];

        $tokenMap = [
            '#x_description#' => (string)($ctx['design_name'] ?? 'Spark Awards Entry Payment'),
            '#paymentDetails.payment_id#' => (string)($payment['payment_id'] ?? ''),
            '#x_first_name#' => (string)($ctx['first_name'] ?? ''),
            '#x_last_name#' => (string)($ctx['last_name'] ?? ''),
            '#x_address#' => (string)($ctx['address1'] ?? ''),
            '#x_city#' => (string)($ctx['city'] ?? ''),
            '#x_state#' => (string)($ctx['state'] ?? ''),
            '#x_zip#' => (string)($ctx['zipcode'] ?? ''),
            '#x_country#' => (string)($ctx['country'] ?? ''),
            '#userDetails.email_address#' => (string)($ctx['email_address'] ?? ''),
            '#x_amount#' => (string)($payment['payment_total'] ?? ''),
        ];

        $resolved = strtr($rawReceipt, $tokenMap);

        // Replace any unknown legacy token remnants with empty text.
        $resolved = preg_replace('/#[A-Za-z0-9_.]+#/', '', $resolved) ?? $resolved;

        return trim($resolved);
    }

    private function getFilters(?int $defaultYear = null): array
    {
        $effectiveYear = $defaultYear ?? (int)date('Y');
        $compYear = (int)($this->request->getGet('compYear') ?? $effectiveYear);
        if ($compYear <= 0) {
            $compYear = $effectiveYear;
        }

        return [
            'filterBy' => (string)($this->request->getGet('filterBy') ?? ''),
            'compYear' => $compYear,
            'compType' => (string)($this->request->getGet('compType') ?? 'ALL'),
            'excludeNonFinalists' => (string)($this->request->getGet('excludeNonFinalists') ?? 'No'),
        ];
    }

    private function loadReceiptEntryContext(string $entryId): array
    {
        return db_connect()->table('comp_entries e')
            ->select('e.design_name, e.company_name, c.comp_year, t.comp_type_name, u.first_name, u.last_name, u.email_address')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id', 'left')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id', 'left')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray() ?? [];
    }

    private function toXlsx(array $rows, array $judgingRows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Submissions');

        $this->writeRowsToSheet($sheet, $rows);

        $judgingSheet = $spreadsheet->createSheet();
        $judgingSheet->setTitle('Judging');
        $this->writeRowsToSheet($judgingSheet, $judgingRows);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        ob_start();
        $writer->save('php://output');

        return (string)ob_get_clean();
    }

    private function writeRowsToSheet($sheet, array $rows): void
    {
        if ($rows === []) {
            $sheet->setCellValueExplicit('A1', 'No data', DataType::TYPE_STRING);
            return;
        }

        $headers = array_keys($rows[0]);
        $lastHeaderCol = Coordinate::stringFromColumnIndex(count($headers));

        foreach ($headers as $index => $header) {
            $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValueExplicit($cell, (string)$header, DataType::TYPE_STRING);
        }

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
                $val = $row[$header] ?? '';
                $cell = Coordinate::stringFromColumnIndex($index + 1) . (string)$rowNum;
                $sheet->setCellValueExplicit(
                    $cell,
                    is_scalar($val) ? (string)$val : (string)json_encode($val, JSON_UNESCAPED_UNICODE),
                    DataType::TYPE_STRING
                );
            }
            $rowNum++;
        }
    }

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

        $short = (string)($form['short_description'] ?? '');
        if ($this->wordCount($short) > self::SHORT_DESCRIPTION_MAX_WORDS) {
            $errors['short_description'] = 'Short Description must be ' . self::SHORT_DESCRIPTION_MAX_WORDS . ' words or fewer.';
        }

        $full = (string)($form['full_description'] ?? '');
        if ($this->wordCount($full) > self::FULL_DESCRIPTION_MAX_WORDS) {
            $errors['full_description'] = 'Full Description must be ' . self::FULL_DESCRIPTION_MAX_WORDS . ' words or fewer.';
        }

        return $errors;
    }

    private function processImages(string $entryId, int $compId, string $resLabel, int $from, int $to): ?array
    {
        $imageService = Services::image();

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first();
        if (!$comp) {
            return ['Invalid competition for image upload.'];
        }

        $year     = (int) ($comp->comp_year ?? date('Y'));
        $typeName = strtolower(trim((string) ($comp->comp_type_name ?? 'unknown')));
        $typeDir  = str_replace(['/', '\\'], '-', $typeName);
        $baseDir  = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $year
            . DIRECTORY_SEPARATOR . $typeDir;

        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'index.html', '');
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . '.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx)$\">\nRequire all denied\n</FilesMatch>\n");
        }

        $photoModel = new EntryPhotoModel();
        $entryRow = $this->adminSubs->find($entryId);
        $photoId  = (string)($entryRow['photo_id'] ?? '');

        $errors = [];
        for ($i = $from; $i <= $to; $i++) {
            $file = $this->request->getFile('low_photo_' . $i);
            $caption = (string)($this->request->getPost('low_caption_' . $i) ?? '');
            $existing = $photoModel->asArray()
                ->where('entry_id', $entryId)
                ->where('entry_photo_res', $resLabel)
                ->where('entry_photo_order', $i)
                ->first();

            if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
                if ($existing) {
                    $photoModel->update((int)$existing['entry_photo_id'], ['entry_photo_caption' => $caption]);
                }
                continue;
            }

            if (!$file->isValid()) {
                $errors[] = 'Photo ' . $i . ' failed upload.';
                continue;
            }

            $clientExt = strtolower((string)$file->getClientExtension());
            if (!in_array($clientExt, ['jpg', 'jpeg'], true)) {
                $errors[] = 'Photo ' . $i . ' must be a valid JPG image.';
                continue;
            }

            $imgInfo = @getimagesize($file->getTempName());
            if (!is_array($imgInfo) || ($imgInfo['mime'] ?? '') !== 'image/jpeg') {
                $errors[] = 'Photo ' . $i . ' must be a valid JPG image.';
                continue;
            }

            try {
                $safeName = 'CompPhotoLow_' . $i . '_' . $photoId . '.jpg';
                $target   = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
                $imageService->withFile($file->getTempName())->resize(2000, 2000, true, 'auto')->save($target, 85);
                $publicUrl = '/uploads/' . $year . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $typeDir) . '/' . $safeName;

                $payload = [
                    'entry_id' => $entryId,
                    'entry_photo' => $publicUrl,
                    'entry_photo_caption' => $caption,
                    'entry_photo_res' => $resLabel,
                    'entry_photo_order' => $i,
                ];
                if ($existing) {
                    $photoModel->update((int)$existing['entry_photo_id'], $payload);
                } else {
                    $photoModel->insert($payload, false);
                }
            } catch (\Throwable $e) {
                $errors[] = 'Photo ' . $i . ' could not be processed.';
            }
        }

        return $errors === [] ? null : $errors;
    }

    private function processCertificate(string $entryId, int $compId): ?array
    {
        $pdf = $this->request->getFile('certificate_pdf');
        $clientThumbnailData = trim((string)($this->request->getPost('certificate_thumb_data') ?? ''));
        if (!$pdf || $pdf->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!$pdf->isValid()) {
            return ['Certificate upload failed. Please try again.'];
        }

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first();
        if (!$comp) {
            return ['Invalid competition for certificate upload.'];
        }

        $year     = (int) ($comp->comp_year ?? date('Y'));
        $typeName = strtolower(trim((string) ($comp->comp_type_name ?? 'unknown')));
        $typeDir  = str_replace(['/', '\\'], '-', $typeName);
        $baseDir  = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $year
            . DIRECTORY_SEPARATOR . $typeDir;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'index.html', '');
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . '.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx)$\">\nRequire all denied\n</FilesMatch>\n");
        }

        $entryRow = $this->adminSubs->find($entryId);
        $photoId  = (string)($entryRow['photo_id'] ?? '');
        if ($photoId === '') {
            $photoId = preg_replace('/[^A-Za-z0-9_-]/', '', $entryId) ?: 'entry';
        }
        $photoModel = new EntryPhotoModel();
        $cert = $photoModel->asArray()->where('entry_id', $entryId)->where('entry_photo_res', 'PDF')->first();
        $payload = [];
        $errors = [];

        if (strtolower((string)$pdf->getExtension()) !== 'pdf') {
            $errors[] = 'Certificate file must be a PDF.';
        } else {
            try {
                $name = 'CertificatePDF_' . $photoId . '.pdf';
                $target = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
                $pdf->move($baseDir, $name, true);
                if (!is_file($target)) {
                    throw new \RuntimeException('Moved PDF file not found at target path.');
                }

                $payload['entry_certificate'] = '/uploads/' . $year . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $typeDir) . '/' . $name;
                $payload['entry_photo'] = $this->certificateFallbackThumbnailPath();

                $thumbPublicPath = '/uploads/' . $year . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $typeDir) . '/CertificateThumb_' . $photoId . '.jpg';
                $thumbAbsolutePath = $this->publicUrlToAbsolutePath($thumbPublicPath);
                $savedClientThumbnail = false;
                if ($clientThumbnailData !== '') {
                    $savedClientThumbnail = $this->saveClientGeneratedCertificateThumbnail($clientThumbnailData, $thumbAbsolutePath);
                }
                if ($savedClientThumbnail || $this->generatePdfThumbnail($target, $thumbAbsolutePath)) {
                    $payload['entry_photo'] = $thumbPublicPath;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Certificate PDF could not be saved.';
            }
        }

        if ($payload !== []) {
            $base = [
                'entry_id' => $entryId,
                'entry_photo_res' => 'PDF',
                'entry_photo_order' => 11,
                'entry_photo_caption' => '',
                'entry_photo' => $cert['entry_photo'] ?? '',
                'entry_certificate' => $cert['entry_certificate'] ?? '',
            ];
            $final = array_merge($base, $payload);
            $persisted = false;
            if ($cert) {
                $persisted = (bool)$photoModel->skipValidation(true)->update((int)$cert['entry_photo_id'], $final);
            } else {
                $persisted = $photoModel->skipValidation(true)->insert($final, false) !== false;
            }

            if (!$persisted) {
                if (!empty($payload['entry_certificate'])) {
                    $certificateAbsolutePath = $this->publicUrlToAbsolutePath((string)$payload['entry_certificate']);
                    if (is_file($certificateAbsolutePath)) {
                        @unlink($certificateAbsolutePath);
                    }
                }
                if (!empty($payload['entry_photo']) && $this->isManagedUploadPublicPath((string)$payload['entry_photo'])) {
                    $thumbnailAbsolutePath = $this->publicUrlToAbsolutePath((string)$payload['entry_photo']);
                    if (is_file($thumbnailAbsolutePath)) {
                        @unlink($thumbnailAbsolutePath);
                    }
                }
                $errors[] = 'Certificate PDF could not be linked to this submission.';
            }
        }

        return $errors === [] ? null : $errors;
    }

    private function generateCertificateFiles(array $entry): ?array
    {
        if (!extension_loaded('gd') || !function_exists('imagecreatefrompng') || !function_exists('imagejpeg')) {
            return ['Certificate generation requires the PHP GD extension with PNG and JPEG support.'];
        }

        $templatePath = $this->resolveCertificateTemplatePath();
        if ($templatePath === null) {
            return ['Certificate template not found. Expected /public_html/img/CertificateTemplate.png or /public/img/CertificateTemplate.png.'];
        }

        $entryId = (string)($entry['entry_id'] ?? '');
        $photoId = trim((string)($entry['photo_id'] ?? ''));
        if ($photoId === '') {
            $photoId = preg_replace('/[^A-Za-z0-9_-]/', '', $entryId) ?: 'entry';
        }

        $year = (int)($entry['comp_year'] ?? date('Y'));
        $typeName = strtolower(trim((string)($entry['comp_type_name'] ?? 'unknown')));
        $typeDir = str_replace(['/', '\\'], '-', $typeName);
        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $year
            . DIRECTORY_SEPARATOR . $typeDir;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'index.html', '');
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . '.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx)$\">\nRequire all denied\n</FilesMatch>\n");
        }
        if (!is_dir($baseDir) || !is_writable($baseDir)) {
            return ['Certificate upload folder could not be prepared.'];
        }

        $imageName = 'CertificateImage_' . $photoId . '.jpg';
        $pdfName = 'CertificatePDF_' . $photoId . '.pdf';
        $imageAbsolutePath = $baseDir . DIRECTORY_SEPARATOR . $imageName;
        $pdfAbsolutePath = $baseDir . DIRECTORY_SEPARATOR . $pdfName;
        $publicBase = '/uploads/' . $year . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $typeDir);
        $imagePublicPath = $publicBase . '/' . $imageName;
        $pdfPublicPath = $publicBase . '/' . $pdfName;

        $certificateData = [
            'year' => (string)$year,
            'competition' => trim((string)($entry['comp_type_name'] ?? '')),
            'award' => trim((string)($entry['selected_winner_level_name'] ?? '')),
            'submission' => trim((string)($entry['design_name'] ?? '')),
            'designers' => $this->formatCertificateDesigners($entry),
        ];
        if ($certificateData['award'] === '') {
            return ['Winner award level is required before generating a certificate.'];
        }
        if ($certificateData['submission'] === '') {
            return ['Submission name is required before generating a certificate.'];
        }
        if ($certificateData['designers'] === '') {
            return ['Designer or team member names are required before generating a certificate.'];
        }

        $dimensions = $this->renderCertificateImage($templatePath, $imageAbsolutePath, $certificateData);
        if ($dimensions === null) {
            return ['Certificate image could not be generated.'];
        }
        if (!$this->renderCertificatePdf($imageAbsolutePath, $pdfAbsolutePath, $dimensions['width'], $dimensions['height'])) {
            @unlink($imageAbsolutePath);
            return ['Certificate PDF could not be generated.'];
        }

        $photoModel = new EntryPhotoModel();
        $existing = $photoModel->asArray()->where('entry_id', $entryId)->where('entry_photo_res', 'PDF')->first();
        $payload = [
            'entry_id' => $entryId,
            'entry_photo_res' => 'PDF',
            'entry_photo_order' => 11,
            'entry_photo_caption' => '',
            'entry_photo' => $imagePublicPath,
            'entry_certificate' => $pdfPublicPath,
        ];

        $persisted = false;
        if ($existing) {
            $persisted = (bool)$photoModel->skipValidation(true)->update((int)$existing['entry_photo_id'], $payload);
        } else {
            $persisted = $photoModel->skipValidation(true)->insert($payload, false) !== false;
        }
        if (!$persisted) {
            @unlink($imageAbsolutePath);
            @unlink($pdfAbsolutePath);
            return ['Generated certificate could not be linked to this submission.'];
        }

        return null;
    }

    private function resolveCertificateTemplatePath(): ?string
    {
        $publicRoot = rtrim(FCPATH, DIRECTORY_SEPARATOR);
        $projectRoot = dirname($publicRoot);
        $candidates = [
            $projectRoot . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'CertificateTemplate.png',
            $publicRoot . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'CertificateTemplate.png',
            $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'CertificateTemplate.png',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function renderCertificateImage(string $templatePath, string $targetPath, array $data): ?array
    {
        $image = @imagecreatefrompng($templatePath);
        if (!$image) {
            return null;
        }

        imagealphablending($image, true);
        imagesavealpha($image, false);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min($width / 1536, $height / 2048);
        $black = imagecolorallocate($image, 0, 0, 0);
        $fontRegular = $this->certificateFontPath(false);
        $fontBold = $this->certificateFontPath(true) ?? $fontRegular;

        $this->drawCertificateText($image, $data['year'], 115 * $scale, 1380 * $scale, 58 * $scale, $black, $fontBold, 470 * $scale, 1);
        $this->drawCertificateText($image, $data['award'], 810 * $scale, 1350 * $scale, 34 * $scale, $black, $fontBold, 510 * $scale, 1);
        $this->drawCertificateText($image, $data['submission'], 810 * $scale, 1415 * $scale, 32 * $scale, $black, $fontBold, 520 * $scale, 3);
        $this->drawCertificateText($image, $data['designers'], 810 * $scale, 1605 * $scale, 30 * $scale, $black, $fontBold, 530 * $scale, 4);
        $this->drawCertificateText($image, $data['competition'], 810 * $scale, 1825 * $scale, 30 * $scale, $black, $fontBold, 530 * $scale, 3);

        $dir = dirname($targetPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($image);
            return null;
        }

        $ok = imagejpeg($image, $targetPath, 94);
        imagedestroy($image);

        return $ok && is_file($targetPath) ? ['width' => $width, 'height' => $height] : null;
    }

    private function drawCertificateText($image, string $text, float $x, float $baselineY, float $fontSize, int $color, ?string $fontPath, float $maxWidth, int $maxLines): void
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return;
        }

        if ($fontPath === null || !function_exists('imagettftext')) {
            imagestring($image, 5, (int)$x, (int)$baselineY, $text, $color);
            return;
        }

        $size = $fontSize;
        do {
            $lines = $this->wrapCertificateText($text, $fontPath, $size, $maxWidth);
            if (count($lines) <= $maxLines || $size <= 16) {
                break;
            }
            $size -= 2;
        } while (true);

        $lineHeight = $size * 1.62;
        foreach (array_slice($lines, 0, $maxLines) as $idx => $line) {
            imagettftext($image, (int)round($size), 0, (int)round($x), (int)round($baselineY + ($idx * $lineHeight)), $color, $fontPath, $line);
        }
    }

    private function wrapCertificateText(string $text, string $fontPath, float $fontSize, float $maxWidth): array
    {
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($this->certificateTextWidth($candidate, $fontPath, $fontSize) <= $maxWidth || $line === '') {
                $line = $candidate;
                continue;
            }

            $lines[] = $line;
            $line = $word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function certificateTextWidth(string $text, string $fontPath, float $fontSize): int
    {
        $box = imagettfbbox((int)round($fontSize), 0, $fontPath, $text);
        if (!is_array($box)) {
            return 0;
        }

        return abs((int)$box[2] - (int)$box[0]);
    }

    private function renderCertificatePdf(string $imagePath, string $targetPath, int $imageWidth, int $imageHeight): bool
    {
        if (!is_file($imagePath)) {
            return false;
        }

        try {
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $dompdf = new Dompdf($options);
            $pageWidth = $imageWidth * 0.75;
            $pageHeight = $imageHeight * 0.75;
            $imageData = base64_encode((string)file_get_contents($imagePath));
            $html = '<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:0;}html,body{margin:0;padding:0;width:100%;height:100%;}img{display:block;width:100%;height:100%;}</style></head><body><img src="data:image/jpeg;base64,' . $imageData . '" alt="Spark Award Certificate"></body></html>';
            $dompdf->setPaper([0, 0, $pageWidth, $pageHeight]);
            $dompdf->loadHtml($html);
            $dompdf->render();

            return @file_put_contents($targetPath, $dompdf->output()) !== false && is_file($targetPath);
        } catch (\Throwable $e) {
            log_message('error', 'Certificate PDF generation failed: {message}', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function certificateFontPath(bool $bold): ?string
    {
        $candidates = $bold ? [
            'C:\\Windows\\Fonts\\georgiab.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ] : [
            'C:\\Windows\\Fonts\\georgia.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function formatCertificateDesigners(array $entry): string
    {
        $names = [];
        $seen = [];
        foreach ($this->splitCertificateNames((string)($entry['additional_team_members'] ?? '')) as $member) {
            $key = $this->normalizeCertificateName($member);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $names[] = $member;
            $seen[$key] = true;
        }

        return $this->joinCertificateNames($names);
    }

    private function splitCertificateNames(string $rawNames): array
    {
        $rawNames = trim($rawNames);
        if ($rawNames === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:,|;|\r?\n|\s+&\s+|\s+and\s+)\s*/i', $rawNames) ?: [];
        $names = [];
        foreach ($parts as $part) {
            $name = trim(preg_replace('/\s+/', ' ', $part) ?? '');
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function normalizeCertificateName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized) ?? '';
        return $normalized;
    }

    private function joinCertificateNames(array $names): string
    {
        $names = array_values(array_filter(array_map('trim', $names), static fn(string $name): bool => $name !== ''));
        $count = count($names);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $names[0];
        }
        if ($count === 2) {
            return $names[0] . ' & ' . $names[1];
        }

        return implode(', ', array_slice($names, 0, -1)) . ' & ' . $names[$count - 1];
    }

    private function processWinnerBadge(string $entryId, int $compId): ?array
    {
        $badge = $this->request->getFile('winner_badge');
        if (!$badge || $badge->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!$badge->isValid()) {
            return ['Winner Badge upload failed. Please try again.'];
        }

        $imgInfo = @getimagesize($badge->getTempName());
        if (!is_array($imgInfo) || !isset($imgInfo[2])) {
            return ['Winner Badge must be a valid image file.'];
        }

        $imageType = (int)$imgInfo[2];
        $extension = ltrim(strtolower((string)image_type_to_extension($imageType, true)), '.');
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if ($extension === '') {
            $extension = strtolower((string)$badge->getClientExtension());
        }
        if ($extension === '') {
            $extension = 'jpg';
        }

        $comp = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first();
        if (!$comp) {
            return ['Invalid competition for winner badge upload.'];
        }

        $year     = (int) ($comp->comp_year ?? date('Y'));
        $typeName = strtolower(trim((string) ($comp->comp_type_name ?? 'unknown')));
        $typeDir  = str_replace(['/', '\\'], '-', $typeName);
        $baseDir  = rtrim(FCPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $year
            . DIRECTORY_SEPARATOR . $typeDir;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'index.html', '');
            @file_put_contents($baseDir . DIRECTORY_SEPARATOR . '.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx)$\">\nRequire all denied\n</FilesMatch>\n");
        }

        $entryRow = $this->adminSubs->find($entryId);
        $photoId  = (string)($entryRow['photo_id'] ?? '');
        if ($photoId === '') {
            $photoId = preg_replace('/[^A-Za-z0-9_-]/', '', $entryId) ?: 'entry';
        }

        $mime = strtolower((string)($imgInfo['mime'] ?? ''));
        $isTiff = in_array($extension, ['tif', 'tiff'], true) || in_array($mime, ['image/tiff', 'image/x-tiff'], true);
        $safeName = 'WinnerBadge_' . $photoId . '.' . ($isTiff ? 'jpg' : $extension);
        $target = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if ($isTiff) {
            if (!$this->convertBadgeToJpeg($badge->getTempName(), $target)) {
                return ['Winner Badge TIFF conversion failed. Please install Imagick or upload JPG/PNG.'];
            }
        } else {
            try {
                $badge->move($baseDir, $safeName, true);
            } catch (\Throwable $e) {
                return ['Winner Badge could not be saved.'];
            }
            if (!is_file($target)) {
                return ['Winner Badge could not be saved.'];
            }
        }

        $publicUrl = '/uploads/' . $year . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $typeDir) . '/' . $safeName;
        $photoModel = new EntryPhotoModel();
        $existing = $photoModel->asArray()
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', 'Badge')
            ->first();

        $oldPath = trim((string)($existing['entry_photo'] ?? ''));
        $payload = [
            'entry_id' => $entryId,
            'entry_photo_res' => 'Badge',
            'entry_photo_order' => 12,
            'entry_photo_caption' => 'Winner Badge',
            'entry_photo' => $publicUrl,
            'entry_certificate' => '',
        ];

        $persisted = false;
        if ($existing) {
            $persisted = (bool)$photoModel->skipValidation(true)->update((int)$existing['entry_photo_id'], $payload);
        } else {
            $persisted = $photoModel->skipValidation(true)->insert($payload, false) !== false;
        }
        if (!$persisted) {
            @unlink($target);
            return ['Winner Badge could not be linked to this submission.'];
        }

        if ($oldPath !== '' && $oldPath !== $publicUrl && $this->isManagedUploadPublicPath($oldPath)) {
            $oldAbsolute = $this->publicUrlToAbsolutePath($oldPath);
            if (is_file($oldAbsolute)) {
                @unlink($oldAbsolute);
            }
        }

        return null;
    }

    private function convertBadgeToJpeg(string $sourcePath, string $targetPath): bool
    {
        if (!is_file($sourcePath)) {
            return false;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($sourcePath);
                if ($imagick->getNumberImages() > 1) {
                    $imagick->setFirstIterator();
                }
                $imagick->setImageBackgroundColor('white');
                $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompressionQuality(90);
                $ok = $imagick->writeImage($targetPath);
                $imagick->clear();
                $imagick->destroy();
                return $ok && is_file($targetPath);
            } catch (\Throwable $e) {
                log_message('error', 'Winner Badge TIFF->JPG conversion failed: {message}', [
                    'message' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return false;
    }

    private function ensureCertificateThumbnailForEdit(array $entry, array $photos): array
    {
        if ($photos === []) {
            return $photos;
        }

        $photoModel = new EntryPhotoModel();
        foreach ($photos as $idx => $photo) {
            if ((string)($photo['entry_photo_res'] ?? '') !== 'PDF') {
                continue;
            }

            $pdfPublicPath = trim((string)($photo['entry_certificate'] ?? ''));
            if ($pdfPublicPath === '') {
                continue;
            }

            $thumbnailPublicPath = trim((string)($photo['entry_photo'] ?? ''));
            $thumbnailMissing = $thumbnailPublicPath === '';
            if (!$thumbnailMissing) {
                $thumbnailAbsolutePath = $this->publicUrlToAbsolutePath($thumbnailPublicPath);
                $thumbnailMissing = !is_file($thumbnailAbsolutePath);
            }
            if (!$thumbnailMissing) {
                continue;
            }

            $pdfAbsolutePath = $this->publicUrlToAbsolutePath($pdfPublicPath);
            if (!is_file($pdfAbsolutePath)) {
                continue;
            }

            $pdfDir = trim(str_replace('\\', '/', dirname($pdfPublicPath)), '/.');
            if ($pdfDir === '') {
                continue;
            }

            $thumbSeed = trim((string)($entry['photo_id'] ?? ''));
            if ($thumbSeed === '') {
                $thumbSeed = trim((string)($photo['entry_photo_id'] ?? ''));
            }
            if ($thumbSeed === '') {
                $thumbSeed = 'entry';
            }
            $thumbSeed = preg_replace('/[^A-Za-z0-9_-]/', '', $thumbSeed) ?: 'entry';
            $generatedThumbPublicPath = '/' . $pdfDir . '/CertificateThumb_' . $thumbSeed . '.jpg';
            $generatedThumbAbsolutePath = $this->publicUrlToAbsolutePath($generatedThumbPublicPath);
            if (!$this->generatePdfThumbnail($pdfAbsolutePath, $generatedThumbAbsolutePath)) {
                $fallbackThumbPath = $this->certificateFallbackThumbnailPath();
                if ($thumbnailPublicPath !== $fallbackThumbPath) {
                    $photoModel->update((int)($photo['entry_photo_id'] ?? 0), [
                        'entry_photo' => $fallbackThumbPath,
                    ]);
                    $photos[$idx]['entry_photo'] = $fallbackThumbPath;
                }
                continue;
            }

            $photoModel->update((int)($photo['entry_photo_id'] ?? 0), [
                'entry_photo' => $generatedThumbPublicPath,
            ]);
            $photos[$idx]['entry_photo'] = $generatedThumbPublicPath;
        }

        return $photos;
    }

    private function generatePdfThumbnail(string $pdfAbsolutePath, string $thumbnailAbsolutePath): bool
    {
        if (!class_exists(\Imagick::class)) {
            return false;
        }
        if (!is_file($pdfAbsolutePath)) {
            return false;
        }

        $thumbDir = dirname($thumbnailAbsolutePath);
        if (!is_dir($thumbDir) && !@mkdir($thumbDir, 0775, true) && !is_dir($thumbDir)) {
            return false;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfAbsolutePath . '[0]');
            $imagick->setImageBackgroundColor('white');
            $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $imagick->setImageFormat('jpeg');
            $imagick->thumbnailImage(560, 0, true, true);
            $ok = $imagick->writeImage($thumbnailAbsolutePath);
            $imagick->clear();
            $imagick->destroy();

            return $ok && is_file($thumbnailAbsolutePath);
        } catch (\Throwable $e) {
            log_message('error', 'Certificate thumbnail generation failed: {message}', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function publicUrlToAbsolutePath(string $publicPath): string
    {
        $path = (string)(parse_url($publicPath, PHP_URL_PATH) ?? '');
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        return rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }

    private function isManagedUploadPublicPath(string $publicPath): bool
    {
        $path = (string)(parse_url($publicPath, PHP_URL_PATH) ?? '');
        $normalized = '/' . ltrim(str_replace('\\', '/', $path), '/');
        return str_starts_with($normalized, '/uploads/');
    }

    private function certificateFallbackThumbnailPath(): string
    {
        return '/vendor/fontawesome-free/svgs/regular/file-pdf.svg';
    }

    private function saveClientGeneratedCertificateThumbnail(string $dataUrl, string $thumbnailAbsolutePath): bool
    {
        if (!preg_match('/^data:image\/jpeg;base64,/i', $dataUrl)) {
            return false;
        }

        $commaPos = strpos($dataUrl, ',');
        if ($commaPos === false) {
            return false;
        }

        $rawBase64 = substr($dataUrl, $commaPos + 1);
        if ($rawBase64 === false || $rawBase64 === '') {
            return false;
        }

        $binary = base64_decode(str_replace(' ', '+', $rawBase64), true);
        if ($binary === false || $binary === '') {
            return false;
        }

        // Guard against unexpectedly large inline payloads.
        if (strlen($binary) > (5 * 1024 * 1024)) {
            return false;
        }

        $thumbDir = dirname($thumbnailAbsolutePath);
        if (!is_dir($thumbDir) && !@mkdir($thumbDir, 0775, true) && !is_dir($thumbDir)) {
            return false;
        }

        if (@file_put_contents($thumbnailAbsolutePath, $binary) === false) {
            return false;
        }

        $imgInfo = @getimagesize($thumbnailAbsolutePath);
        if (!is_array($imgInfo) || ($imgInfo['mime'] ?? '') !== 'image/jpeg') {
            @unlink($thumbnailAbsolutePath);
            return false;
        }

        return is_file($thumbnailAbsolutePath) && filesize($thumbnailAbsolutePath) > 0;
    }

    private function validateRequiredLowPhotos(string $entryId): array
    {
        $existing = (new EntryPhotoModel())
            ->asArray()
            ->select('entry_photo_order')
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', 'Low')
            ->whereIn('entry_photo_order', [1, 2, 3])
            ->findAll();

        $existingByOrder = [];
        foreach ($existing as $row) {
            $existingByOrder[(int)($row['entry_photo_order'] ?? 0)] = true;
        }

        $errors = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = $this->request->getFile('low_photo_' . $i);
            $hasUpload = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;
            if (!$hasUpload && !isset($existingByOrder[$i])) {
                $errors['low_photo_' . $i] = 'Photo ' . $i . ' is required. Please upload a JPG image.';
            }
        }

        return $errors;
    }
}
