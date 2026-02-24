<?php

namespace App\Commands;

use App\Services\Admin\SystemToolsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MaintenanceRetention extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'maintenance:retention';
    protected $description = 'Run configured file retention cleanup rules (trash/logs/temp uploads).';
    protected $usage = 'maintenance:retention [--dry-run]';
    protected $options = [
        '--dry-run' => 'Preview retention candidates without deleting.',
    ];

    public function run(array $params)
    {
        $dryRun = CLI::getOption('dry-run') !== null;
        $svc = new SystemToolsService();
        $summary = $svc->runRetention($dryRun, $svc->loadRetentionRules());

        CLI::write('Retention Summary', 'green');
        CLI::write('Dry Run: ' . ($dryRun ? 'Yes' : 'No'));
        CLI::write('Candidates: ' . (int) ($summary['total_candidates'] ?? 0));
        CLI::write('Deleted: ' . (int) ($summary['deleted_files'] ?? 0));
        CLI::write('Bytes: ' . (int) ($summary['total_bytes'] ?? 0));
        foreach (($summary['rules'] ?? []) as $key => $row) {
            CLI::write(
                sprintf(
                    '- %s: %s (%d files)',
                    $key,
                    (string) ($row['status'] ?? 'unknown'),
                    (int) ($row['candidates'] ?? 0)
                )
            );
        }
        foreach (($summary['errors'] ?? []) as $err) {
            CLI::write('ERROR: ' . (string) $err, 'red');
        }
    }
}

