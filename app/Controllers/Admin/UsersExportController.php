<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        $binary = $this->toXlsx($rows);
        $filename = 'users-export-' . date('Ymd_His') . '.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($binary);
    }

    private function toXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        if ($rows === []) {
            $sheet->setCellValueExplicit('A1', 'No data', DataType::TYPE_STRING);
        } else {
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

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        ob_start();
        $writer->save('php://output');

        return (string)ob_get_clean();
    }
}
