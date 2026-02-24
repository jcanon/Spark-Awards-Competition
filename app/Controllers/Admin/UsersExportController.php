<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class UsersExportController extends BaseController
{
    public function export()
    {
        if (!session('uid')) {
            return redirect()->to('/auth/login');
        }
        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return redirect()->to('/home');
        }
        if (!session('2fa_pass')) {
            return redirect()->to('/auth/2fa');
        }

        $orderBy = (string)$this->request->getGet('orderBy');
        $sortBy = (string)$this->request->getGet('sortBy');
        $userType = (string)$this->request->getGet('userType');
        $profileCompleted = (string)$this->request->getGet('profileCompleted');
        $exportGroup = strtolower(trim((string)$this->request->getGet('group')));

        $userTypePricing = '';
        if ($exportGroup === 'professionals') {
            $userTypePricing = 'Pro';
        } elseif ($exportGroup === 'students') {
            $userTypePricing = 'Student';
        }

        $svc = service('profiles');
        $rows = $svc->exportAllUsers([
            'orderBy' => $orderBy !== '' ? $orderBy : 'last_name',
            'sortBy' => $sortBy !== '' ? $sortBy : 'ASC',
            'userType' => $userType !== '' ? $userType : 'ALL',
            'profileCompleted' => $profileCompleted !== '' ? $profileCompleted : 'ALL',
            'userTypePricing' => $userTypePricing,
        ]);

        $csv = $this->toCsv($rows);
        $filename = 'users-export-' . date('Ymd_His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody("\xEF\xBB\xBF" . $csv); // UTF-8 BOM for Excel
    }

    /**
     * Convert an array of associative rows to CSV text.
     * Uses keys from the first row as headers.
     */
    private function toCsv(array $rows): string
    {
        if ($rows === []) {
            return "No data\n";
        }

        $headers = array_keys($rows[0]);

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $val = $row[$h] ?? '';
                $line[] = is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            fputcsv($fh, $line);
        }
        rewind($fh);
        $csv = (string)stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
