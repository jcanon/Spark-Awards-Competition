<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class RepairCategoryPathRename extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'photos:repair-category-path-rename';
    protected $description = 'Repair uploads directory names and comp_entry_photos URLs after a competition category folder rename.';
    protected $usage = 'photos:repair-category-path-rename --from old-name --to new-name [--dry-run]';
    protected $options = [
        '--from' => 'Old category folder segment (example: space).',
        '--to' => 'New category folder segment (example: spaces).',
        '--dry-run' => 'Preview changes without writing files or DB rows.',
    ];

    public function run(array $params)
    {
        $fromOpt = trim((string) CLI::getOption('from'));
        $toOpt = trim((string) CLI::getOption('to'));
        $dryRun = CLI::getOption('dry-run') !== null;

        $from = $this->legacyTypeDir($fromOpt);
        $to = $this->legacyTypeDir($toOpt);

        if ($from === '' || $to === '' || $from === 'unknown' || $to === 'unknown') {
            CLI::error('Both --from and --to are required.');
            return;
        }
        if ($from === $to) {
            CLI::error('--from and --to resolve to the same folder segment.');
            return;
        }

        $uploadsRoot = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';
        $years = [];
        if (is_dir($uploadsRoot)) {
            $items = scandir($uploadsRoot);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    if (ctype_digit($item) && is_dir($uploadsRoot . DIRECTORY_SEPARATOR . $item)) {
                        $years[] = (int) $item;
                    }
                }
            }
        }
        sort($years);

        $renameCandidates = [];
        foreach ($years as $year) {
            $base = $uploadsRoot . DIRECTORY_SEPARATOR . $year;
            $oldAbs = $base . DIRECTORY_SEPARATOR . $from;
            $newAbs = $base . DIRECTORY_SEPARATOR . $to;
            if (! is_dir($oldAbs)) {
                continue;
            }
            $renameCandidates[] = ['year' => $year, 'from' => $oldAbs, 'to' => $newAbs];
        }

        CLI::write('Directory rename candidates: ' . count($renameCandidates));
        foreach ($renameCandidates as $op) {
            CLI::write(' - ' . $op['from'] . ' -> ' . $op['to']);
        }

        $db = Database::connect();
        $rows = $db->table('comp_entry_photos')
            ->select('entry_photo_id, entry_photo, entry_certificate')
            ->groupStart()
                ->like('entry_photo', '/uploads/%/' . $from . '/')
                ->orLike('entry_certificate', '/uploads/%/' . $from . '/')
            ->groupEnd()
            ->get()
            ->getResultArray();

        CLI::write('Photo rows to update: ' . count($rows));

        if ($dryRun) {
            CLI::write('Dry run only. No changes applied.', 'yellow');
            return;
        }

        foreach ($renameCandidates as $op) {
            $fromAbs = (string) $op['from'];
            $toAbs = (string) $op['to'];
            if (is_dir($toAbs) && ! $this->isDirectorySemanticallyEmpty($toAbs)) {
                CLI::error('Target directory exists and is not empty: ' . $toAbs);
                return;
            }
        }

        $db->transBegin();

        $renamed = 0;
        foreach ($renameCandidates as $op) {
            $fromAbs = (string) $op['from'];
            $toAbs = (string) $op['to'];
            if (! is_dir($fromAbs)) {
                continue;
            }
            if (is_dir($toAbs) && $this->isDirectorySemanticallyEmpty($toAbs)) {
                @rmdir($toAbs);
            }
            if (! @rename($fromAbs, $toAbs)) {
                $db->transRollback();
                CLI::error('Failed renaming: ' . $fromAbs . ' -> ' . $toAbs);
                return;
            }
            $renamed++;
        }

        $updatedRows = 0;
        foreach ($rows as $row) {
            $id = (int) ($row['entry_photo_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $updates = [];
            foreach (['entry_photo', 'entry_certificate'] as $field) {
                $raw = (string) ($row[$field] ?? '');
                if ($raw === '') {
                    continue;
                }
                $updated = $this->replaceUploadsTypeSegment($raw, $from, $to);
                if ($updated !== $raw) {
                    $updates[$field] = $updated;
                }
            }
            if ($updates !== []) {
                $ok = $db->table('comp_entry_photos')->where('entry_photo_id', $id)->update($updates);
                if (! $ok) {
                    $db->transRollback();
                    CLI::error('Failed updating photo row: ' . $id);
                    return;
                }
                $updatedRows++;
            }
        }

        if (! $db->transStatus()) {
            $db->transRollback();
            CLI::error('Database transaction failed.');
            return;
        }
        $db->transCommit();

        CLI::write('Completed.', 'green');
        CLI::write('Directories renamed: ' . $renamed);
        CLI::write('Photo rows updated: ' . $updatedRows);
    }

    private function replaceUploadsTypeSegment(string $value, string $oldTypeDir, string $newTypeDir): string
    {
        $quotedOld = preg_quote($oldTypeDir, '#');
        return (string) preg_replace(
            '#(/uploads/\d{4}/)' . $quotedOld . '(/)#i',
            '$1' . $newTypeDir . '$2',
            $value
        );
    }

    private function isDirectorySemanticallyEmpty(string $dir): bool
    {
        $items = @scandir($dir);
        if (! is_array($items)) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (in_array($item, ['index.html', '.htaccess'], true)) {
                continue;
            }
            return false;
        }
        return true;
    }

    private function legacyTypeDir(string $name): string
    {
        $lower = strtolower(trim($name));
        if ($lower === '') {
            return '';
        }
        return str_replace(['/', '\\'], '-', $lower);
    }
}

