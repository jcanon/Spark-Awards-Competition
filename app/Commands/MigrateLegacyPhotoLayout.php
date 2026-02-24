<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class MigrateLegacyPhotoLayout extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'photos:migrate-legacy-layout';
    protected $description = 'Move comp_entry_photos files to legacy /uploads/<year>/<lowercase comp_type_name>/ layout and update DB URLs.';
    protected $usage = 'photos:migrate-legacy-layout [--dry-run] [--limit N]';
    protected $options = [
        '--dry-run' => 'Preview changes without writing files or DB updates.',
        '--limit'   => 'Only process the first N photo rows.',
    ];

    public function run(array $params)
    {
        $dryRun = CLI::getOption('dry-run') !== null;
        $limitOpt = CLI::getOption('limit');
        $limit = is_numeric($limitOpt) ? max(0, (int) $limitOpt) : 0;

        $db = Database::connect();
        $builder = $db->table('comp_entry_photos p')
            ->select('p.entry_photo_id, p.entry_id, p.entry_photo, c.comp_year, t.comp_type_name')
            ->join('comp_entries e', 'e.entry_id = p.entry_id', 'left')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id', 'left')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id', 'left');

        if ($limit > 0) {
            $builder->limit($limit);
        }

        $rows = $builder->get()->getResultArray();
        if ($rows === []) {
            CLI::write('No photo rows found.', 'yellow');
            return;
        }

        $processed = 0;
        $moved = 0;
        $dbUpdated = 0;
        $already = 0;
        $missing = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $processed++;
            $photoId = (int) ($row['entry_photo_id'] ?? 0);
            $rawUrl = (string) ($row['entry_photo'] ?? '');
            if ($rawUrl === '') {
                $skipped++;
                continue;
            }

            $path = $this->normalizedPath($rawUrl);
            if ($path === '' || strpos($path, '/uploads/') !== 0) {
                $skipped++;
                continue;
            }

            $year = (int) ($row['comp_year'] ?? 0);
            if ($year <= 0) {
                $parts = explode('/', trim($path, '/'));
                if (isset($parts[1]) && ctype_digit($parts[1])) {
                    $year = (int) $parts[1];
                }
            }
            if ($year <= 0) {
                CLI::write("[$photoId] Missing competition year; skipping.", 'yellow');
                $skipped++;
                continue;
            }

            $typeDir = $this->legacyTypeDir((string) ($row['comp_type_name'] ?? 'unknown'));
            $fileName = basename($path);
            $targetRel = '/uploads/' . $year . '/' . $typeDir . '/' . $fileName;
            $targetAbs = $this->toPublicAbs($targetRel);
            $sourceAbs = $this->resolveExistingAbs($path);

            // Safety guard: only migrate records from the newer entrant layout
            // that used an entry-scoped folder segment.
            $isEntryScopedPath = strpos($path, '/entry_') !== false;
            if (! $isEntryScopedPath) {
                $skipped++;
                continue;
            }

            $needsDbUpdate = $rawUrl !== $targetRel;
            $needsMove = $sourceAbs !== null && $this->normalizeFsPath($sourceAbs) !== $this->normalizeFsPath($targetAbs);

            if (! $needsDbUpdate && ! $needsMove) {
                $already++;
                continue;
            }

            if ($dryRun) {
                CLI::write("[$photoId] " . $rawUrl . ' -> ' . $targetRel, 'cyan');
                if ($sourceAbs === null && ! is_file($targetAbs)) {
                    CLI::write("[$photoId] Source file missing.", 'yellow');
                }
                continue;
            }

            if (! is_dir(dirname($targetAbs))) {
                @mkdir(dirname($targetAbs), 0775, true);
                @file_put_contents(dirname($targetAbs) . DIRECTORY_SEPARATOR . 'index.html', '');
            }

            if ($needsMove) {
                if ($sourceAbs === null || ! is_file($sourceAbs)) {
                    CLI::write("[$photoId] Source file missing; cannot move.", 'yellow');
                    $missing++;
                } else {
                    if (! is_file($targetAbs)) {
                        $ok = @rename($sourceAbs, $targetAbs);
                        if (! $ok) {
                            $ok = @copy($sourceAbs, $targetAbs);
                            if ($ok) {
                                @unlink($sourceAbs);
                            }
                        }
                        if (! $ok) {
                            CLI::write("[$photoId] Failed moving file to " . $targetAbs, 'red');
                            $errors++;
                            continue;
                        }
                        $moved++;
                    } else {
                        CLI::write("[$photoId] Target already exists; leaving existing file.", 'yellow');
                    }
                }
            }

            if ($needsDbUpdate) {
                $ok = $db->table('comp_entry_photos')
                    ->where('entry_photo_id', $photoId)
                    ->update(['entry_photo' => $targetRel]);
                if (! $ok) {
                    CLI::write("[$photoId] Failed DB update for URL.", 'red');
                    $errors++;
                    continue;
                }
                $dbUpdated++;
            }
        }

        CLI::newLine();
        CLI::write('Migration summary:', 'green');
        CLI::write('Processed: ' . $processed);
        CLI::write('Moved files: ' . $moved);
        CLI::write('DB URL updates: ' . $dbUpdated);
        CLI::write('Already correct: ' . $already);
        CLI::write('Missing source files: ' . $missing);
        CLI::write('Skipped: ' . $skipped);
        CLI::write('Errors: ' . $errors);
        if ($dryRun) {
            CLI::write('Dry run only. No file or DB changes were made.', 'yellow');
        }
    }

    private function normalizedPath(string $urlOrPath): string
    {
        $path = parse_url($urlOrPath, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $urlOrPath;
        }
        $path = str_replace('\\', '/', $path);
        $path = '/' . ltrim($path, '/');
        return preg_replace('#/+#', '/', $path) ?? $path;
    }

    private function legacyTypeDir(string $compTypeName): string
    {
        $lower = strtolower(trim($compTypeName));
        if ($lower === '') {
            $lower = 'unknown';
        }
        return str_replace(['/', '\\'], '-', $lower);
    }

    private function toPublicAbs(string $relPath): string
    {
        return rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
    }

    private function toWritableAbs(string $relPath): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
    }

    private function resolveExistingAbs(string $relPath): ?string
    {
        $public = $this->toPublicAbs($relPath);
        if (is_file($public)) {
            return $public;
        }

        $writable = $this->toWritableAbs($relPath);
        if (is_file($writable)) {
            return $writable;
        }

        return null;
    }

    private function normalizeFsPath(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return strtolower($normalized);
    }

}
