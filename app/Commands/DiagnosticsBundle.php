<?php

namespace App\Commands;

use App\Services\Admin\SystemToolsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DiagnosticsBundle extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'diagnostics:bundle';
    protected $description = 'Generate a sanitized diagnostics zip bundle in writable/diagnostics.';

    public function run(array $params)
    {
        $svc = new SystemToolsService();
        $path = $svc->createDiagnosticsBundle();
        CLI::write('Diagnostics bundle generated:', 'green');
        CLI::write($path);
    }
}

