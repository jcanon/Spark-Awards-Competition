<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Entries\CertificateRequestModel;

class CertificatesController extends BaseController
{
    public function index()
    {
        $uid = (string)session('uid');
        if ($uid === '') {
            return redirect()->to('/auth/login');
        }

        $rows = db_connect()->table('comp_entries e')
            ->select('e.entry_id, e.design_name, e.entry_status, e.winner_level, c.comp_year, c.jury_phase_2_close, t.comp_type_name, w.winner_level_name, r.request_status, r.updated_at')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = e.winner_level', 'left')
            ->join('comp_entry_certificate_requests r', 'r.entry_id = e.entry_id', 'left')
            ->where('e.user_id', $uid)
            ->orderBy('c.comp_year', 'DESC')
            ->orderBy('t.comp_type_name', 'ASC')
            ->orderBy('e.design_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('certificates/index', [
            'rows' => $rows,
        ]);
    }

    public function request(string $entryId)
    {
        $entry = $this->ownedEntry($entryId);
        if ($entry === null) {
            return redirect()->to('/certificates')->with('error', 'Submission not found.');
        }
        if (!$this->isCertificateAvailable($entry)) {
            return redirect()->to('/certificates')->with('error', 'Certificate requests are available only after judging has closed for that competition.');
        }

        $request = (new CertificateRequestModel())
            ->where('entry_id', $entryId)
            ->first();
        $requestStatus = trim((string)($request['request_status'] ?? ''));
        $isReadOnly = $request !== null && strcasecmp($requestStatus, 'Pending') !== 0;

        $prefill = [
            'certificate_quantity' => 1,
            'designer_names' => trim((string)($entry['designer_first_name'] ?? '') . ' ' . (string)($entry['designer_last_name'] ?? '')),
            'additional_persons' => '',
            'printed_organization' => (string)($entry['company_name'] ?? ''),
            'contact_person' => trim((string)($entry['first_name'] ?? '') . ' ' . (string)($entry['last_name'] ?? '')),
            'contact_phone' => (string)($entry['phone'] ?? ''),
            'contact_email' => (string)($entry['email_address'] ?? ''),
            'shipping_method' => 'least_expensive',
            'shipping_company' => (string)($entry['company_name'] ?? ''),
            'shipping_address1' => (string)($entry['address1'] ?? ''),
            'shipping_address2' => (string)($entry['address2'] ?? ''),
            'shipping_city' => (string)($entry['city'] ?? ''),
            'shipping_state' => (string)($entry['state'] ?? ''),
            'shipping_postal_code' => (string)($entry['zipcode'] ?? ''),
            'shipping_country' => (string)($entry['country'] ?? ''),
            'lamination_requested' => 0,
            'special_instructions' => '',
        ];

        return view('certificates/form', [
            'entry' => $entry,
            'request' => $request ?: [],
            'prefill' => $prefill,
            'isReadOnly' => $isReadOnly,
        ]);
    }

    public function save(string $entryId)
    {
        $entry = $this->ownedEntry($entryId);
        if ($entry === null) {
            return redirect()->to('/certificates')->with('error', 'Submission not found.');
        }
        if (!$this->isCertificateAvailable($entry)) {
            return redirect()->to('/certificates')->with('error', 'Certificate requests are available only after judging has closed for that competition.');
        }

        $rules = [
            'certificate_quantity' => 'required|integer|greater_than[0]|less_than_equal_to[999]',
            'designer_names' => 'required|max_length[120]',
            'additional_persons' => 'permit_empty|max_length[30]',
            'printed_organization' => 'permit_empty|max_length[120]',
            'contact_person' => 'required|max_length[120]',
            'contact_phone' => 'required|max_length[25]',
            'contact_email' => 'required|valid_email|max_length[120]',
            'shipping_method' => 'required|in_list[least_expensive,express]',
            'shipping_company' => 'permit_empty|max_length[120]',
            'shipping_address1' => 'required|max_length[150]',
            'shipping_address2' => 'permit_empty|max_length[150]',
            'shipping_city' => 'required|max_length[80]',
            'shipping_state' => 'required|max_length[80]',
            'shipping_postal_code' => 'required|max_length[30]',
            'shipping_country' => 'required|max_length[80]',
            'lamination_requested' => 'permit_empty|in_list[0,1]',
            'special_instructions' => 'permit_empty|max_length[2000]',
        ];

        $model = new CertificateRequestModel();
        $existing = $model->where('entry_id', $entryId)->first();
        $existingStatus = trim((string)($existing['request_status'] ?? ''));
        if ($existing && strcasecmp($existingStatus, 'Pending') !== 0) {
            return redirect()->to('/certificates')->with('error', 'This certificate request is no longer editable.');
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please correct the certificate request form.');
        }

        $uid = (string)session('uid');
        $post = $this->request->getPost();
        $laminationRequested = ((string)($post['lamination_requested'] ?? '0') === '1') ? 1 : 0;

        $payload = [
            'entry_id' => $entryId,
            'user_id' => $uid,
            'certificate_quantity' => (int)$post['certificate_quantity'],
            'designer_names' => trim((string)$post['designer_names']),
            'additional_persons' => trim((string)($post['additional_persons'] ?? '')),
            'printed_organization' => trim((string)($post['printed_organization'] ?? '')),
            'contact_person' => trim((string)$post['contact_person']),
            'contact_phone' => trim((string)$post['contact_phone']),
            'contact_email' => trim((string)$post['contact_email']),
            'shipping_method' => trim((string)$post['shipping_method']),
            'shipping_company' => trim((string)($post['shipping_company'] ?? '')),
            'shipping_address1' => trim((string)$post['shipping_address1']),
            'shipping_address2' => trim((string)($post['shipping_address2'] ?? '')),
            'shipping_city' => trim((string)$post['shipping_city']),
            'shipping_state' => trim((string)$post['shipping_state']),
            'shipping_postal_code' => trim((string)$post['shipping_postal_code']),
            'shipping_country' => trim((string)$post['shipping_country']),
            'lamination_requested' => $laminationRequested,
            'special_instructions' => trim((string)($post['special_instructions'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $payload['request_status'] = trim((string)($existing['request_status'] ?? 'Pending'));
            $model->update((int)$existing['certificate_request_id'], $payload);
        } else {
            $payload['request_status'] = 'Pending';
            $payload['requested_at'] = date('Y-m-d H:i:s');
            $model->insert($payload, false);
        }

        return redirect()->to('/certificates')->with('success', 'Certificate request saved.');
    }

    private function ownedEntry(string $entryId): ?array
    {
        $uid = (string)session('uid');
        if ($uid === '') {
            return null;
        }

        $row = db_connect()->table('comp_entries e')
            ->select('e.entry_id, e.design_name, e.entry_status, e.winner_level, e.designer_first_name, e.designer_last_name, e.company_name, c.comp_year, c.jury_phase_2_close, t.comp_type_name, w.winner_level_name, u.first_name, u.last_name, u.phone, u.email_address, u.address1, u.address2, u.city, u.state, u.zipcode, u.country')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels w', 'w.winner_level_id = e.winner_level', 'left')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->where('e.entry_id', $entryId)
            ->where('e.user_id', $uid)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function isCertificateAvailable(array $entry): bool
    {
        $closedAt = trim((string)($entry['jury_phase_2_close'] ?? ''));
        if ($closedAt === '') {
            return false;
        }

        return $closedAt <= date('Y-m-d H:i:s');
    }
}
