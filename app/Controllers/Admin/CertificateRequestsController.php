<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Entries\CertificateRequestModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class CertificateRequestsController extends BaseController
{
    private const STATUS_OPTIONS = [
        'Pending',
        'In Review',
        'Quoted',
        'Paid',
        'In Production',
        'Shipped',
        'Completed',
        'Cancelled',
    ];

    public function index()
    {
        $db = db_connect();

        $years = $db->table('comp_entry_certificate_requests r')
            ->distinct()
            ->select('c.comp_year')
            ->join('comp_entries e', 'e.entry_id = r.entry_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->orderBy('c.comp_year', 'DESC')
            ->get()
            ->getResultArray();

        $year = (int)($this->request->getGet('year') ?? 0);

        $competitionOptionsBuilder = $db->table('comp_entry_certificate_requests r')
            ->select('c.comp_id, c.comp_year, t.comp_type_name')
            ->join('comp_entries e', 'e.entry_id = r.entry_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->groupBy('c.comp_id, c.comp_year, t.comp_type_name')
            ->orderBy('c.comp_year', 'DESC')
            ->orderBy('t.comp_type_name', 'ASC');
        if ($year > 0) {
            $competitionOptionsBuilder->where('c.comp_year', $year);
        }
        $competitionOptions = $competitionOptionsBuilder
            ->get()
            ->getResultArray();

        $competitionId = (int)($this->request->getGet('competition_id') ?? 0);
        if ($competitionId > 0) {
            $validComp = false;
            foreach ($competitionOptions as $opt) {
                if ((int)$opt['comp_id'] === $competitionId) {
                    $validComp = true;
                    break;
                }
            }
            if (!$validComp) {
                $competitionId = 0;
            }
        }

        $statusFilter = trim((string)$this->request->getGet('status'));
        if ($statusFilter !== '' && !in_array($statusFilter, self::STATUS_OPTIONS, true)) {
            $statusFilter = '';
        }

        $builder = $db->table('comp_entry_certificate_requests r')
            ->select('r.certificate_request_id, r.request_status, r.requested_at, r.updated_at, r.certificate_quantity, r.contact_person, r.contact_email, e.entry_id, e.design_name, e.entry_status, e.winner_level, c.comp_id, c.comp_year, t.comp_type_name, w.winner_level_name, u.first_name, u.last_name')
            ->join('comp_entries e', 'e.entry_id = r.entry_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = e.winner_level', 'left')
            ->join('comp_users u', 'u.user_id = e.user_id');

        if ($year > 0) {
            $builder->where('c.comp_year', $year);
        }

        if ($competitionId > 0) {
            $builder->where('c.comp_id', $competitionId);
        }
        if ($statusFilter !== '') {
            $builder->where('r.request_status', $statusFilter);
        }

        $rows = $builder
            ->orderBy('r.updated_at', 'DESC')
            ->orderBy('r.requested_at', 'DESC')
            ->orderBy('e.design_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/certificate_requests/index', [
            'rows' => $rows,
            'years' => $years,
            'competitionOptions' => $competitionOptions,
            'filters' => [
                'year' => $year,
                'competition_id' => $competitionId,
                'status' => $statusFilter,
            ],
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function updateStatus(int $certificateRequestId)
    {
        $status = trim((string)$this->request->getPost('request_status'));
        if (!in_array($status, self::STATUS_OPTIONS, true)) {
            return redirect()->back()->with('error', 'Invalid request status.');
        }

        $row = $this->findRequestRow($certificateRequestId);
        if (!$row) {
            return redirect()->back()->with('error', 'Certificate request not found.');
        }
        $oldStatus = trim((string)($row['request_status'] ?? ''));

        $ok = (new CertificateRequestModel())->update($certificateRequestId, [
            'request_status' => $status,
            'reviewed_by' => (string)session('uid'),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$ok) {
            return redirect()->back()->with('error', 'Unable to update request status.');
        }

        if (strcasecmp($oldStatus, $status) !== 0) {
            $updatedRow = $this->findRequestRow($certificateRequestId) ?? $row;
            $this->sendContactStatusChangedNotification($updatedRow, $status);
        }

        return redirect()->to($this->buildListUrlFromInput())->with('success', 'Request status updated.');
    }

    public function export()
    {
        $rows = $this->buildExportRows();
        $binary = $this->toXlsx($rows);
        $filename = 'certificate-requests-' . date('Ymd_His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($binary);
    }

    public function edit(int $certificateRequestId)
    {
        $row = $this->findRequestRow($certificateRequestId);
        if (!$row) {
            return redirect()->to('admin/certificate-requests')->with('error', 'Certificate request not found.');
        }

        return view('admin/certificate_requests/edit', [
            'row' => $row,
            'statusOptions' => self::STATUS_OPTIONS,
            'filters' => [
                'year' => (int)$this->request->getGet('year'),
                'competition_id' => (int)$this->request->getGet('competition_id'),
                'status' => trim((string)$this->request->getGet('status')),
            ],
        ]);
    }

    public function update(int $certificateRequestId)
    {
        $row = $this->findRequestRow($certificateRequestId);
        if (!$row) {
            return redirect()->to('admin/certificate-requests')->with('error', 'Certificate request not found.');
        }
        $oldStatus = trim((string)($row['request_status'] ?? ''));

        $rules = [
            'request_status' => 'required',
            'certificate_quantity' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[999]',
            'designer_names' => 'permit_empty|max_length[120]',
            'additional_persons' => 'permit_empty|max_length[30]',
            'printed_organization' => 'permit_empty|max_length[120]',
            'contact_person' => 'permit_empty|max_length[120]',
            'contact_phone' => 'permit_empty|max_length[25]',
            'contact_email' => 'permit_empty|valid_email|max_length[120]',
            'shipping_method' => 'permit_empty|in_list[least_expensive,express]',
            'shipping_company' => 'permit_empty|max_length[120]',
            'shipping_address1' => 'permit_empty|max_length[150]',
            'shipping_address2' => 'permit_empty|max_length[150]',
            'shipping_city' => 'permit_empty|max_length[80]',
            'shipping_state' => 'permit_empty|max_length[80]',
            'shipping_postal_code' => 'permit_empty|max_length[30]',
            'shipping_country' => 'permit_empty|max_length[80]',
            'lamination_requested' => 'permit_empty|in_list[0,1]',
            'special_instructions' => 'permit_empty|max_length[2000]',
            'admin_notes' => 'permit_empty|max_length[2000]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please correct the certificate request form.');
        }

        $status = trim((string)$this->request->getPost('request_status'));
        if (!in_array($status, self::STATUS_OPTIONS, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid request status.');
        }

        $payload = [
            'request_status' => $status,
            'certificate_quantity' => max(0, (int)$this->request->getPost('certificate_quantity')),
            'designer_names' => trim((string)$this->request->getPost('designer_names')),
            'additional_persons' => trim((string)$this->request->getPost('additional_persons')),
            'printed_organization' => trim((string)$this->request->getPost('printed_organization')),
            'contact_person' => trim((string)$this->request->getPost('contact_person')),
            'contact_phone' => trim((string)$this->request->getPost('contact_phone')),
            'contact_email' => trim((string)$this->request->getPost('contact_email')),
            'shipping_method' => trim((string)$this->request->getPost('shipping_method')),
            'shipping_company' => trim((string)$this->request->getPost('shipping_company')),
            'shipping_address1' => trim((string)$this->request->getPost('shipping_address1')),
            'shipping_address2' => trim((string)$this->request->getPost('shipping_address2')),
            'shipping_city' => trim((string)$this->request->getPost('shipping_city')),
            'shipping_state' => trim((string)$this->request->getPost('shipping_state')),
            'shipping_postal_code' => trim((string)$this->request->getPost('shipping_postal_code')),
            'shipping_country' => trim((string)$this->request->getPost('shipping_country')),
            'lamination_requested' => ((string)$this->request->getPost('lamination_requested') === '1') ? 1 : 0,
            'special_instructions' => trim((string)$this->request->getPost('special_instructions')),
            'admin_notes' => trim((string)$this->request->getPost('admin_notes')),
            'reviewed_by' => (string)session('uid'),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $ok = (new CertificateRequestModel())->update($certificateRequestId, $payload);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'Unable to update certificate request.');
        }

        if (strcasecmp($oldStatus, $status) !== 0) {
            $updatedRow = $this->findRequestRow($certificateRequestId) ?? array_merge($row, $payload);
            $this->sendContactStatusChangedNotification($updatedRow, $status);
        }

        return redirect()->to($this->buildListUrlFromInput())->with('success', 'Certificate request updated.');
    }

    private function sendContactStatusChangedNotification(array $row, string $newStatus): void
    {
        $contactEmail = trim((string)($row['contact_email'] ?? ''));
        if ($contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $contactName = trim((string)($row['contact_person'] ?? ''));
        $certificateRequestId = (int)($row['certificate_request_id'] ?? 0);
        $entryId = trim((string)($row['entry_id'] ?? ''));
        $entryName = trim((string)($row['design_name'] ?? ''));
        $compLabel = trim((string)($row['comp_year'] ?? '') . ' ' . (string)($row['comp_type_name'] ?? ''));
        $quantity = (int)($row['certificate_quantity'] ?? 0);
        $requestedAt = format_datetime_ui((string)($row['requested_at'] ?? ''), '-');
        $ordersUrl = site_url('certificates');

        try {
            $email = service('email');
            $subject = 'Certificate Request Status Updated (Order #' . $certificateRequestId . ')';
            $message = '<p>Hello ' . esc($contactName !== '' ? $contactName : 'there') . ',</p>'
                . '<p>Your certificate request status has changed to <strong>' . esc($newStatus) . '</strong>.</p>'
                . '<p><strong>Order #:</strong> ' . $certificateRequestId . '<br>'
                . '<strong>Entry ID:</strong> ' . esc($entryId) . '<br>'
                . '<strong>Design Name:</strong> ' . esc($entryName) . '<br>'
                . '<strong>Competition:</strong> ' . esc($compLabel) . '<br>'
                . '<strong>Requested:</strong> ' . esc((string)$requestedAt) . '<br>'
                . '<strong>Quantity:</strong> ' . $quantity . '<br>'
                . '<strong>Current Status:</strong> ' . esc($newStatus) . '</p>'
                . '<p>You can view your certificate requests at <a href="' . esc($ordersUrl) . '">' . esc($ordersUrl) . '</a>.</p>';

            $email->setTo($contactEmail)
                ->setSubject($subject)
                ->setMessage($message);
            $email->send(false);
        } catch (Throwable $e) {
            log_message('error', 'Certificate request status email failed: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function findRequestRow(int $certificateRequestId): ?array
    {
        $row = db_connect()->table('comp_entry_certificate_requests r')
            ->select('r.*, e.entry_id, e.design_name, e.entry_status, e.winner_level, c.comp_year, c.comp_id, t.comp_type_name, w.winner_level_name')
            ->join('comp_entries e', 'e.entry_id = r.entry_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = e.winner_level', 'left')
            ->where('r.certificate_request_id', $certificateRequestId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function buildExportRows(): array
    {
        $rows = db_connect()->table('comp_entry_certificate_requests r')
            ->select([
                'r.*',
                'e.entry_id',
                'e.design_name',
                'e.entry_status',
                'e.winner_level',
                'e.company_name AS entry_company_name',
                'c.comp_id',
                'c.comp_year',
                't.comp_type_name',
                'w.winner_level_name',
                'u.first_name',
                'u.last_name',
                'u.email_address',
                'u.phone',
                'u.mobile',
                'u.company_name AS account_company_name',
                'u.address1 AS account_address1',
                'u.address2 AS account_address2',
                'u.city AS account_city',
                'u.state AS account_state',
                'st.state AS account_state_name',
                'u.zipcode AS account_zipcode',
                'u.country AS account_country',
                'co.country AS account_country_name',
                'rst.state AS shipping_state_name',
                'rco.country AS shipping_country_name',
            ])
            ->join('comp_entries e', 'e.entry_id = r.entry_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = e.winner_level', 'left')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->join('states st', 'st.scode = u.state', 'left')
            ->join('countries co', 'co.ccode = u.country', 'left')
            ->join('states rst', 'rst.scode = r.shipping_state', 'left')
            ->join('countries rco', 'rco.ccode = r.shipping_country', 'left')
            ->orderBy("CASE WHEN r.request_status = 'Pending' THEN 0 ELSE 1 END", '', false)
            ->orderBy('r.request_status', 'ASC')
            ->orderBy('e.design_name', 'ASC')
            ->orderBy('r.certificate_request_id', 'ASC')
            ->get()
            ->getResultArray();

        $exportRows = [];
        foreach ($rows as $row) {
            [$entryStatus] = entry_status_pill($row);
            $shippingState = $this->preferFullName((string)($row['shipping_state'] ?? ''), (string)($row['shipping_state_name'] ?? ''));
            $shippingCountry = $this->preferFullName((string)($row['shipping_country'] ?? ''), (string)($row['shipping_country_name'] ?? ''));
            $accountState = $this->preferFullName((string)($row['account_state'] ?? ''), (string)($row['account_state_name'] ?? ''));
            $accountCountry = $this->preferFullName((string)($row['account_country'] ?? ''), (string)($row['account_country_name'] ?? ''));

            $exportRows[] = [
                'Status' => (string)($row['request_status'] ?? ''),
                'SubmissionName' => (string)($row['design_name'] ?? ''),
                'SubmissionCompany' => (string)($row['entry_company_name'] ?? ''),
                'CertificateRequestId' => (string)($row['certificate_request_id'] ?? ''),
                'EntryId' => (string)($row['entry_id'] ?? ''),
                'Competition' => trim((string)($row['comp_type_name'] ?? '') . ' ' . (string)($row['comp_year'] ?? '')),
                'CompetitionId' => (string)($row['comp_id'] ?? ''),
                'EntryStatus' => $entryStatus,
                'WinnerLevel' => (string)($row['winner_level_name'] ?? ''),
                'EntrantFirstName' => (string)($row['first_name'] ?? ''),
                'EntrantLastName' => (string)($row['last_name'] ?? ''),
                'EntrantEmail' => (string)($row['email_address'] ?? ''),
                'EntrantPhone' => (string)($row['phone'] ?? ''),
                'EntrantMobile' => (string)($row['mobile'] ?? ''),
                'RequestedAt' => (string)($row['requested_at'] ?? ''),
                'UpdatedAt' => (string)($row['updated_at'] ?? ''),
                'ReviewedAt' => (string)($row['reviewed_at'] ?? ''),
                'ReviewedBy' => (string)($row['reviewed_by'] ?? ''),
                'CertificateQuantity' => (string)($row['certificate_quantity'] ?? ''),
                'DesignerNames' => (string)($row['designer_names'] ?? ''),
                'AdditionalPersons' => (string)($row['additional_persons'] ?? ''),
                'PrintedOrganization' => (string)($row['printed_organization'] ?? ''),
                'OfficialLevel' => (string)($row['official_level'] ?? ''),
                'AwardCategory' => (string)($row['award_category'] ?? ''),
                'LaminationRequested' => ((string)($row['lamination_requested'] ?? '0') === '1') ? 'Yes' : 'No',
                'LaminationQuantity' => (string)($row['lamination_quantity'] ?? ''),
                'ShippingMethod' => (string)($row['shipping_method'] ?? ''),
                'ContactPerson' => (string)($row['contact_person'] ?? ''),
                'ContactEmail' => (string)($row['contact_email'] ?? ''),
                'ContactPhone' => (string)($row['contact_phone'] ?? ''),
                'ShippingCompany' => (string)($row['shipping_company'] ?? ''),
                'ShippingAddress1' => (string)($row['shipping_address1'] ?? ''),
                'ShippingAddress2' => (string)($row['shipping_address2'] ?? ''),
                'ShippingCity' => (string)($row['shipping_city'] ?? ''),
                'ShippingStateProvince' => $shippingState,
                'ShippingStateProvinceCode' => (string)($row['shipping_state'] ?? ''),
                'ShippingPostalCode' => (string)($row['shipping_postal_code'] ?? ''),
                'ShippingCountry' => $shippingCountry,
                'ShippingCountryCode' => (string)($row['shipping_country'] ?? ''),
                'ShippingFullAddress' => $this->formatAddress([
                    (string)($row['shipping_company'] ?? ''),
                    (string)($row['shipping_address1'] ?? ''),
                    (string)($row['shipping_address2'] ?? ''),
                    trim((string)($row['shipping_city'] ?? '') . ', ' . $shippingState . ' ' . (string)($row['shipping_postal_code'] ?? ''), " ,"),
                    $shippingCountry,
                ]),
                'AccountCompany' => (string)($row['account_company_name'] ?? ''),
                'AccountAddress1' => (string)($row['account_address1'] ?? ''),
                'AccountAddress2' => (string)($row['account_address2'] ?? ''),
                'AccountCity' => (string)($row['account_city'] ?? ''),
                'AccountStateProvince' => $accountState,
                'AccountStateProvinceCode' => (string)($row['account_state'] ?? ''),
                'AccountPostalCode' => (string)($row['account_zipcode'] ?? ''),
                'AccountCountry' => $accountCountry,
                'AccountCountryCode' => (string)($row['account_country'] ?? ''),
                'AccountFullAddress' => $this->formatAddress([
                    (string)($row['account_company_name'] ?? ''),
                    (string)($row['account_address1'] ?? ''),
                    (string)($row['account_address2'] ?? ''),
                    trim((string)($row['account_city'] ?? '') . ', ' . $accountState . ' ' . (string)($row['account_zipcode'] ?? ''), " ,"),
                    $accountCountry,
                ]),
                'SpecialInstructions' => (string)($row['special_instructions'] ?? ''),
                'AdminNotes' => (string)($row['admin_notes'] ?? ''),
                'AdminEditUrl' => site_url('admin/certificate-requests/edit/' . (int)($row['certificate_request_id'] ?? 0)),
            ];
        }

        return $exportRows;
    }

    private function toXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Certificate Requests');

        $this->writeRowsToSheet($sheet, $rows);

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
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastHeaderCol . '1');

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

    private function preferFullName(string $codeOrName, string $fullName): string
    {
        $codeOrName = trim($codeOrName);
        $fullName = trim($fullName);

        return $fullName !== '' ? $fullName : $codeOrName;
    }

    private function formatAddress(array $parts): string
    {
        $lines = [];
        foreach ($parts as $part) {
            $line = trim((string)$part);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    private function buildListUrlFromInput(): string
    {
        $year = (int)$this->request->getPost('year');
        $competitionId = (int)$this->request->getPost('competition_id');
        $statusFilter = trim((string)$this->request->getPost('status'));

        if ($year <= 0) {
            $year = (int)$this->request->getGet('year');
        }
        if ($competitionId <= 0) {
            $competitionId = (int)$this->request->getGet('competition_id');
        }
        if ($statusFilter === '') {
            $statusFilter = trim((string)$this->request->getGet('status'));
        }

        $query = [];
        if ($year > 0) {
            $query['year'] = $year;
        }
        if ($competitionId > 0) {
            $query['competition_id'] = $competitionId;
        }
        if ($statusFilter !== '' && in_array($statusFilter, self::STATUS_OPTIONS, true)) {
            $query['status'] = $statusFilter;
        }

        $url = site_url('admin/certificate-requests');
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}
