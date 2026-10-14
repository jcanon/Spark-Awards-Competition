<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\BrokenImageScannerService;

class BrokenImageScannerController extends BaseController
{
    public function index()
    {
        $scanner = new BrokenImageScannerService();
        return view('admin/system_tools/broken_image_scanner', [
            'ready' => $scanner->ready(),
            'state' => $scanner->ready() ? $scanner->status() : ['phase' => 'idle'],
        ]);
    }

    public function step()
    {
        try {
            if ((string) session('role') !== 'admin') {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Administrator access required.', 'csrf' => csrf_hash()]);
            }
            $scanner = new BrokenImageScannerService();
            if (!$scanner->ready()) {
                throw new \RuntimeException('Run the database migrations before starting the scanner.');
            }
            return $this->response->setJSON([
                'state' => $scanner->advance($this->request->getPost('action') === 'start'),
                'csrf' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Image scanner: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(409)->setJSON(['error' => $e->getMessage(), 'csrf' => csrf_hash()]);
        }
    }
}
