<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\SystemToolsService;

class SystemToolsController extends BaseController
{
    private SystemToolsService $tools;

    public function __construct()
    {
        $this->tools = new SystemToolsService();
    }

    public function index()
    {
        return view('admin/system_tools/index');
    }

    public function uploadHealth()
    {
        return view('admin/system_tools/upload_health', [
            'report' => $this->tools->collectUploadQueueHealth(),
            'canDelete' => (string) session('role') !== 'editor',
        ]);
    }

    public function uploadHealthRepair()
    {
        if ((string) session('role') === 'editor') {
            return redirect()->to('/admin/system-tools/upload-health')->with('error', 'Editors are not allowed to delete records.');
        }

        $summary = $this->tools->repairUploadQueueHealth(
            true,
            true,
            ! empty($this->request->getPost('delete_empty_rows'))
        );

        return redirect()->to('/admin/system-tools/upload-health')->with(
            'success',
            'Repair finished. Updated ' . (int) $summary['updated_rows']
            . ', deleted orphan rows ' . (int) $summary['deleted_orphans']
            . ', deleted empty rows ' . (int) $summary['deleted_empty_rows'] . '.'
        );
    }

    public function mediaCleaner()
    {
        return view('admin/system_tools/media_cleaner', [
            'report' => $this->tools->collectMediaCleanerReport(),
            'canDelete' => (string) session('role') !== 'editor',
        ]);
    }

    public function mediaCleanerPurge()
    {
        if ((string) session('role') === 'editor') {
            return redirect()->to('/admin/system-tools/media-cleaner')->with('error', 'Editors are not allowed to delete records.');
        }
        $summary = $this->tools->purgeMediaOrphans();
        return redirect()->to('/admin/system-tools/media-cleaner')->with(
            'success',
            'Purge finished. Deleted ' . (int) $summary['deleted_files'] . ' file(s).'
        );
    }

    public function retention()
    {
        $rules = $this->tools->loadRetentionRules();
        $preview = null;
        if ((string) $this->request->getGet('preview') === '1') {
            $preview = $this->tools->runRetention(true, $rules);
        }

        return view('admin/system_tools/retention', [
            'rules' => $rules,
            'preview' => $preview,
            'canDelete' => (string) session('role') !== 'editor',
        ]);
    }

    public function retentionSave()
    {
        $this->tools->saveRetentionRules((array) $this->request->getPost());
        return redirect()->to('/admin/system-tools/retention')->with('success', 'Retention rules saved.');
    }

    public function retentionRun()
    {
        if ((string) session('role') === 'editor') {
            return redirect()->to('/admin/system-tools/retention')->with('error', 'Editors are not allowed to delete records.');
        }

        $rules = $this->tools->loadRetentionRules();
        $summary = $this->tools->runRetention(false, $rules);

        return redirect()->to('/admin/system-tools/retention')->with(
            'success',
            'Retention run complete. Deleted ' . (int) $summary['deleted_files'] . ' file(s).'
        );
    }

    public function readiness()
    {
        $yearParam = $this->request->getGet('year');
        if ($yearParam === null) {
            $year = (int) date('Y');
        } else {
            $year = (int) $yearParam;
            $year = $year > 0 ? $year : null;
        }
        $compId = (int) ($this->request->getGet('compId') ?? 0);
        $compId = $compId > 0 ? $compId : null;

        $yearRows = db_connect()
            ->table('comp_competitions')
            ->select('comp_year')
            ->groupBy('comp_year')
            ->orderBy('comp_year', 'DESC')
            ->get()
            ->getResultArray();
        $years = array_map(static fn (array $r): int => (int) ($r['comp_year'] ?? 0), $yearRows);

        return view('admin/system_tools/readiness', [
            'report' => $this->tools->competitionReadiness($compId, $year),
            'years' => $years,
            'selectedYear' => $year,
        ]);
    }

    public function diagnostics()
    {
        return view('admin/system_tools/diagnostics', [
            'bundles' => $this->tools->listDiagnosticsBundles(),
            'canDelete' => (string) session('role') !== 'editor',
        ]);
    }

    public function paymentWebhooks()
    {
        return view('admin/system_tools/payment_webhooks', [
            'report' => $this->tools->collectPaymentWebhookDiagnostics(),
        ]);
    }

    public function siteHealth()
    {
        return view('admin/system_tools/site_health', [
            'report' => $this->tools->collectSiteHealth(),
        ]);
    }

    public function diagnosticsGenerate()
    {
        $path = $this->tools->createDiagnosticsBundle();
        return $this->response->download($path, null)->setFileName(basename($path));
    }

    public function diagnosticsDownload(string $name)
    {
        $path = $this->tools->diagnosticsBundlePath($name);
        if ($path === null) {
            return redirect()->to('/admin/system-tools/diagnostics')->with('error', 'Diagnostics bundle not found.');
        }
        return $this->response->download($path, null)->setFileName(basename($path));
    }

    public function testErrorAlert()
    {
        $request = service('request');
        $requestUrl = '';
        if ($request !== null && method_exists($request, 'getUri')) {
            $uri = $request->getUri();
            $requestUrl = $uri ? (string)$uri : '';
        }

        $payload = [
            'test_id' => bin2hex(random_bytes(8)),
            'environment' => ENVIRONMENT,
            'user_id' => (string)(session('user_id') ?? ''),
            'role' => (string)(session('role') ?? ''),
            'method' => $request !== null && method_exists($request, 'getMethod') ? strtoupper((string)$request->getMethod()) : '',
            'url' => $requestUrl,
            'ip' => $request !== null && method_exists($request, 'getIPAddress') ? (string)$request->getIPAddress() : '',
            'timestamp' => date('c'),
        ];

        log_message('critical', 'Manual test error alert trigger: {payload}', [
            'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);

        return redirect()->back()->with(
            'success',
            'Test error alert logged (ID: ' . $payload['test_id'] . '). '
            . (ENVIRONMENT === 'production'
                ? 'If email is configured, you should receive it shortly.'
                : 'Email alerts are production-only in current config.')
        );
    }
}
