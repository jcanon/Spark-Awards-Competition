<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Entries\CertificateRequestModel;
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
