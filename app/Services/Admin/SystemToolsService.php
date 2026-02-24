<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Config\AuthorizeNet;
use CodeIgniter\CodeIgniter;
use CodeIgniter\Database\BaseConnection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

class SystemToolsService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function collectUploadQueueHealth(): array
    {
        $rows = $this->fetchPhotoRowsForHealth();
        $failed = [];
        $orphans = [];
        $missingFiles = [];

        foreach ($rows as $row) {
            $entryPhotoId = (int) ($row['entry_photo_id'] ?? 0);
            $entryId = (string) ($row['entry_id'] ?? '');
            $hasEntry = ! empty($row['entry_exists']);

            if (! $hasEntry) {
                $orphans[] = [
                    'entry_photo_id' => $entryPhotoId,
                    'entry_id' => $entryId,
                    'entry_photo_res' => (string) ($row['entry_photo_res'] ?? ''),
                    'entry_photo_order' => (int) ($row['entry_photo_order'] ?? 0),
                    'comp_year' => (int) ($row['comp_year'] ?? 0),
                    'comp_type_name' => (string) ($row['comp_type_name'] ?? ''),
                ];
                continue;
            }

            $entryPhoto = trim((string) ($row['entry_photo'] ?? ''));
            $entryCert = trim((string) ($row['entry_certificate'] ?? ''));
            $res = strtoupper(trim((string) ($row['entry_photo_res'] ?? '')));

            if ($res === 'LOW' && $entryPhoto === '') {
                $failed[] = [
                    'entry_photo_id' => $entryPhotoId,
                    'entry_id' => $entryId,
                    'reason' => 'Low-resolution photo URL is empty.',
                    'comp_year' => (int) ($row['comp_year'] ?? 0),
                    'comp_type_name' => (string) ($row['comp_type_name'] ?? ''),
                ];
            }
            if ($res === 'PDF' && $entryPhoto === '' && $entryCert === '') {
                $failed[] = [
                    'entry_photo_id' => $entryPhotoId,
                    'entry_id' => $entryId,
                    'reason' => 'Certificate row has no image/PDF URL.',
                    'comp_year' => (int) ($row['comp_year'] ?? 0),
                    'comp_type_name' => (string) ($row['comp_type_name'] ?? ''),
                ];
            }

            foreach (['entry_photo', 'entry_certificate'] as $field) {
                $raw = trim((string) ($row[$field] ?? ''));
                if ($raw === '' || preg_match('#^https?://#i', $raw) === 1) {
                    continue;
                }
                $abs = $this->resolveAbsolutePath($raw);
                if ($abs === null || ! is_file($abs)) {
                    $expected = $this->expectedPathsForRow($row);
                    $missingFiles[] = [
                        'entry_photo_id' => $entryPhotoId,
                        'entry_id' => $entryId,
                        'field' => $field,
                        'current' => $raw,
                        'expected' => (string) ($expected[$field] ?? ''),
                        'comp_year' => (int) ($row['comp_year'] ?? 0),
                        'comp_type_name' => (string) ($row['comp_type_name'] ?? ''),
                    ];
                }
            }
        }

        return [
            'totals' => [
                'rows_scanned' => count($rows),
                'failed_count' => count($failed),
                'orphan_count' => count($orphans),
                'missing_file_count' => count($missingFiles),
            ],
            'failed' => $failed,
            'orphans' => $orphans,
            'missing_files' => $missingFiles,
        ];
    }

    public function repairUploadQueueHealth(bool $deleteOrphans = true, bool $repairExpectedPaths = true, bool $deleteEmptyRows = false): array
    {
        $rows = $this->fetchPhotoRowsForHealth();
        $deletedOrphans = 0;
        $deletedEmpty = 0;
        $updatedRows = 0;
        $warnings = [];

        foreach ($rows as $row) {
            $entryPhotoId = (int) ($row['entry_photo_id'] ?? 0);
            $hasEntry = ! empty($row['entry_exists']);
            $entryPhoto = trim((string) ($row['entry_photo'] ?? ''));
            $entryCert = trim((string) ($row['entry_certificate'] ?? ''));

            if (! $hasEntry && $deleteOrphans) {
                $this->db->table('comp_entry_photos')->where('entry_photo_id', $entryPhotoId)->delete();
                $deletedOrphans++;
                continue;
            }

            if ($deleteEmptyRows && $entryPhoto === '' && $entryCert === '') {
                $this->db->table('comp_entry_photos')->where('entry_photo_id', $entryPhotoId)->delete();
                $deletedEmpty++;
                continue;
            }

            if (! $repairExpectedPaths || ! $hasEntry) {
                continue;
            }

            $expected = $this->expectedPathsForRow($row);
            $updates = [];

            foreach (['entry_photo', 'entry_certificate'] as $field) {
                $target = trim((string) ($expected[$field] ?? ''));
                if ($target === '') {
                    continue;
                }
                $targetAbs = $this->resolveAbsolutePath($target);
                if ($targetAbs === null || ! is_file($targetAbs)) {
                    continue;
                }

                $current = trim((string) ($row[$field] ?? ''));
                $currentAbs = $current !== '' ? $this->resolveAbsolutePath($current) : null;
                $currentMissing = $current === '' || $currentAbs === null || ! is_file($currentAbs);
                if ($currentMissing || $current !== $target) {
                    $updates[$field] = $target;
                }
            }

            if ($updates !== []) {
                $ok = $this->db->table('comp_entry_photos')->where('entry_photo_id', $entryPhotoId)->update($updates);
                if ($ok) {
                    $updatedRows++;
                } else {
                    $warnings[] = 'Failed to update photo row ' . $entryPhotoId . '.';
                }
            }
        }

        return [
            'deleted_orphans' => $deletedOrphans,
            'deleted_empty_rows' => $deletedEmpty,
            'updated_rows' => $updatedRows,
            'warnings' => $warnings,
        ];
    }

    public function collectMediaCleanerReport(): array
    {
        $uploadsRoot = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';
        $files = $this->scanUploadsFiles($uploadsRoot);
        $referenced = $this->collectReferencedUploadFiles();

        foreach ($files as &$file) {
            $key = $this->normalizeWebPath((string) ($file['rel'] ?? ''));
            $file['is_referenced'] = isset($referenced[$key]);
        }
        unset($file);

        $hashGroups = [];
        foreach ($files as $idx => $file) {
            $hash = @sha1_file($file['abs']);
            if (! is_string($hash) || $hash === '') {
                continue;
            }
            $hashGroups[$hash][] = $idx;
        }

        $duplicateGroups = [];
        foreach ($hashGroups as $hash => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            $groupFiles = [];
            $referencedCount = 0;
            $size = 0;
            foreach ($indexes as $i) {
                $f = $files[$i];
                $groupFiles[] = $f;
                $size = (int) $f['size'];
                if (! empty($f['is_referenced'])) {
                    $referencedCount++;
                }
            }
            $duplicateGroups[] = [
                'hash' => $hash,
                'count' => count($groupFiles),
                'size_each' => $size,
                'referenced_count' => $referencedCount,
                'orphan_count' => count($groupFiles) - $referencedCount,
                'files' => $groupFiles,
            ];
        }

        $plan = $this->buildMediaOrphanPurgePlan($files, $hashGroups);
        $orphanCount = 0;
        $orphanBytes = 0;
        $referencedCount = 0;
        foreach ($files as $file) {
            if (! empty($file['is_referenced'])) {
                $referencedCount++;
            } else {
                $orphanCount++;
                $orphanBytes += (int) $file['size'];
            }
        }

        return [
            'totals' => [
                'files_total' => count($files),
                'files_referenced' => $referencedCount,
                'files_unreferenced' => $orphanCount,
                'files_orphan' => $orphanCount,
                'unreferenced_bytes' => $orphanBytes,
                'orphan_bytes' => $orphanBytes,
                'duplicate_group_count' => count($duplicateGroups),
                'purge_candidate_count' => count($plan['candidates']),
                'purge_candidate_bytes' => $plan['reclaimable_bytes'],
            ],
            'duplicate_groups' => $duplicateGroups,
            'purge_plan' => $plan,
            'uploads_root' => $uploadsRoot,
        ];
    }

    public function collectSiteHealth(): array
    {
        $dbInfo = [
            'driver' => (string) ($this->db->DBDriver ?? 'unknown'),
            'database' => (string) ($this->db->database ?? ''),
            'version' => null,
            'connected' => false,
            'error' => null,
        ];
        try {
            $verRow = $this->db->query('SELECT VERSION() AS v')->getRowArray();
            $dbInfo['version'] = (string) ($verRow['v'] ?? '');
            $dbInfo['connected'] = true;
        } catch (\Throwable $e) {
            $dbInfo['error'] = $e->getMessage();
        }

        $tableCounts = [];
        try {
            $tableCounts = [
                'competitions' => $this->safeTableCount('comp_competitions'),
                'entries' => $this->safeTableCount('comp_entries'),
                'entry_photos' => $this->safeTableCount('comp_entry_photos'),
                'payments' => $this->safeTableCount('comp_entry_payments'),
                'users' => $this->safeTableCount('comp_users'),
            ];
        } catch (\Throwable $e) {
            $tableCounts = ['error' => $e->getMessage()];
        }

        $anet = new AuthorizeNet();
        $sandboxReady = $anet->sandboxApiLoginId !== '' && $anet->sandboxTransactionKey !== '';
        $productionReady = $anet->productionApiLoginId !== '' && $anet->productionTransactionKey !== '';
        $sandboxProbe = $this->probeHttpsEndpoint('https://test.authorize.net/payment/payment');
        $productionProbe = $this->probeHttpsEndpoint('https://accept.authorize.net/payment/payment');

        $runtime = [
            'environment' => ENVIRONMENT,
            'timezone' => (string) date_default_timezone_get(),
            'now_utc' => gmdate('Y-m-d H:i:s'),
            'server_time' => date('Y-m-d H:i:s'),
            'base_url' => site_url('/'),
            'php_sapi' => PHP_SAPI,
            'hostname' => (string) gethostname(),
            'os' => PHP_OS_FAMILY . ' (' . php_uname('r') . ')',
        ];

        $php = [
            'version' => PHP_VERSION,
            'memory_limit' => (string) ini_get('memory_limit'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'max_file_uploads' => (string) ini_get('max_file_uploads'),
            'max_execution_time' => (string) ini_get('max_execution_time'),
            'max_input_time' => (string) ini_get('max_input_time'),
            'max_input_vars' => (string) ini_get('max_input_vars'),
            'default_socket_timeout' => (string) ini_get('default_socket_timeout'),
            'display_errors' => (string) ini_get('display_errors'),
            'log_errors' => (string) ini_get('log_errors'),
            'error_reporting' => (string) ini_get('error_reporting'),
            'loaded_extensions_count' => count(get_loaded_extensions()),
            'opcache' => [
                'enabled_ini' => (string) ini_get('opcache.enable'),
                'jit_enabled_ini' => (string) ini_get('opcache.jit'),
                'status_available' => function_exists('opcache_get_status'),
                'status' => function_exists('opcache_get_status') ? ((opcache_get_status(false) ?: ['opcache_enabled' => false])) : null,
            ],
            'required_extensions' => [
                'curl' => extension_loaded('curl'),
                'gd' => extension_loaded('gd'),
                'intl' => extension_loaded('intl'),
                'mbstring' => extension_loaded('mbstring'),
                'mysqli' => extension_loaded('mysqli'),
                'openssl' => extension_loaded('openssl'),
                'zip' => extension_loaded('zip'),
                'fileinfo' => extension_loaded('fileinfo'),
                'exif' => extension_loaded('exif'),
                'json' => extension_loaded('json'),
            ],
        ];

        $ciVersion = defined(CodeIgniter::class . '::CI_VERSION')
            ? (string) CodeIgniter::CI_VERSION
            : 'unknown';

        $paths = [
            'fcp' => FCPATH,
            'app' => APPPATH,
            'writable' => WRITEPATH,
            'system' => SYSTEMPATH,
            'uploads_public' => rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads',
            'trash_public' => rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash',
            'logs_writable' => rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs',
            'tmp_uploads_writable' => rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tmp',
            'diagnostics_bundles' => rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'system-tools' . DIRECTORY_SEPARATOR . 'bundles',
        ];

        $permissions = [];
        foreach ($paths as $key => $path) {
            $permissions[$key] = $this->pathPermissions($path);
        }

        $storage = [
            'uploads' => $this->dirQuickStats($paths['uploads_public']),
            'trash' => $this->dirQuickStats($paths['trash_public']),
            'logs' => $this->dirQuickStats($paths['logs_writable']),
            'tmp_uploads' => $this->dirQuickStats($paths['tmp_uploads_writable']),
            'diagnostics_bundles' => $this->dirQuickStats($paths['diagnostics_bundles']),
        ];

        $payment = [
            'authorize_net_mode' => $anet->mode,
            'active_environment' => $anet->useProduction() ? 'production' : 'sandbox',
            'active_hosted_url' => $anet->hostedPaymentUrl(),
            'sandbox_credentials_present' => $sandboxReady,
            'production_credentials_present' => $productionReady,
            'sandbox_endpoint_probe' => $sandboxProbe,
            'production_endpoint_probe' => $productionProbe,
            'sdk_version' => $this->composerPackageVersion('authorizenet/authorizenet'),
        ];

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'runtime' => $runtime,
            'php' => $php,
            'codeigniter' => [
                'version' => $ciVersion,
            ],
            'database' => $dbInfo,
            'table_counts' => $tableCounts,
            'paths' => $paths,
            'permissions' => $permissions,
            'storage' => $storage,
            'payment' => $payment,
        ];
    }

    public function purgeMediaOrphans(): array
    {
        $report = $this->collectMediaCleanerReport();
        $candidates = $report['purge_plan']['candidates'] ?? [];
        $deleted = 0;
        $failed = 0;
        $failedPaths = [];

        foreach ($candidates as $candidate) {
            $abs = (string) ($candidate['abs'] ?? '');
            if ($abs === '' || ! is_file($abs)) {
                continue;
            }
            if (@unlink($abs)) {
                $deleted++;
            } else {
                $failed++;
                $failedPaths[] = $abs;
            }
        }

        return [
            'deleted_files' => $deleted,
            'failed_files' => $failed,
            'failed_paths' => $failedPaths,
            'reclaimed_bytes_estimate' => (int) ($report['purge_plan']['reclaimable_bytes'] ?? 0),
        ];
    }

    public function loadRetentionRules(): array
    {
        $defaults = $this->defaultRetentionRules();
        $path = $this->retentionConfigPath();
        if (! is_file($path)) {
            return $defaults;
        }

        $raw = @file_get_contents($path);
        if (! is_string($raw) || $raw === '') {
            return $defaults;
        }
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            return $defaults;
        }

        foreach ($defaults as $key => $rule) {
            if (! isset($json[$key]) || ! is_array($json[$key])) {
                continue;
            }
            $saved = $json[$key];
            $defaults[$key]['enabled'] = ! empty($saved['enabled']);
            $defaults[$key]['days'] = max(1, (int) ($saved['days'] ?? $rule['days']));
            $defaults[$key]['path'] = trim((string) ($saved['path'] ?? $rule['path']));
            $defaults[$key]['glob'] = trim((string) ($saved['glob'] ?? $rule['glob']));
        }

        return $defaults;
    }

    public function saveRetentionRules(array $post): array
    {
        $rules = $this->defaultRetentionRules();
        foreach ($rules as $key => $rule) {
            $rules[$key]['enabled'] = ! empty($post['enabled_' . $key]);
            $rules[$key]['days'] = max(1, (int) ($post['days_' . $key] ?? $rule['days']));
            $rules[$key]['path'] = trim((string) ($post['path_' . $key] ?? $rule['path']));
            $rules[$key]['glob'] = trim((string) ($post['glob_' . $key] ?? $rule['glob']));
        }

        $path = $this->retentionConfigPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ok = @file_put_contents($path, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($ok === false) {
            throw new RuntimeException('Unable to save retention rules.');
        }

        return $rules;
    }

    public function runRetention(bool $dryRun, ?array $rules = null): array
    {
        $rules = $rules ?? $this->loadRetentionRules();
        $now = time();
        $items = [];
        $deleted = 0;
        $errors = [];
        $bytes = 0;
        $ruleSummaries = [];

        foreach ($rules as $key => $rule) {
            $enabled = ! empty($rule['enabled']);
            $path = trim((string) ($rule['path'] ?? ''));
            $days = max(1, (int) ($rule['days'] ?? 1));
            $glob = trim((string) ($rule['glob'] ?? ''));
            $cutoff = $now - ($days * 86400);
            $candidates = 0;
            $candidateBytes = 0;

            if (! $enabled) {
                $ruleSummaries[$key] = ['status' => 'disabled', 'candidates' => 0, 'bytes' => 0];
                continue;
            }
            if (! is_dir($path)) {
                $ruleSummaries[$key] = ['status' => 'missing-dir', 'candidates' => 0, 'bytes' => 0];
                continue;
            }
            if (! $this->isAllowedMaintenancePath($path)) {
                $ruleSummaries[$key] = ['status' => 'invalid-path', 'candidates' => 0, 'bytes' => 0];
                $errors[] = 'Rule ' . $key . ' path is outside allowed roots.';
                continue;
            }

            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var SplFileInfo $item */
            foreach ($iter as $item) {
                if (! $item->isFile()) {
                    continue;
                }
                $baseName = $item->getBasename();
                if (in_array($baseName, ['index.html', '.htaccess'], true)) {
                    continue;
                }
                if ($glob !== '' && ! fnmatch($glob, $baseName)) {
                    continue;
                }
                $mtime = (int) $item->getMTime();
                if ($mtime >= $cutoff) {
                    continue;
                }
                $candidateBytes += (int) $item->getSize();
                $candidates++;
                $abs = $item->getPathname();
                $items[] = ['rule' => $key, 'path' => $abs, 'size' => (int) $item->getSize(), 'mtime' => $mtime];
                if (! $dryRun) {
                    if (@unlink($abs)) {
                        $deleted++;
                    } else {
                        $errors[] = 'Failed deleting: ' . $abs;
                    }
                }
            }

            if (! $dryRun) {
                $this->deleteEmptyDirectories($path);
            }

            $bytes += $candidateBytes;
            $ruleSummaries[$key] = ['status' => 'ok', 'candidates' => $candidates, 'bytes' => $candidateBytes];
        }

        return [
            'dry_run' => $dryRun,
            'total_candidates' => count($items),
            'total_bytes' => $bytes,
            'deleted_files' => $deleted,
            'errors' => $errors,
            'rules' => $ruleSummaries,
            'items' => array_slice($items, 0, 400),
        ];
    }

    public function competitionReadiness(?int $compId = null, ?int $year = null): array
    {
        $builder = $this->db->table('comp_competitions c')
            ->select('c.*, t.comp_type_name')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id', 'left');
        if ($compId !== null && $compId > 0) {
            $builder->where('c.comp_id', $compId);
        }
        if ($year !== null && $year > 0) {
            $builder->where('c.comp_year', $year);
        }
        $competitions = $builder->orderBy('c.comp_year', 'DESC')->orderBy('t.comp_type_name', 'ASC')->get()->getResultArray();

        $questionCount = (int) $this->db->table('comp_entry_questions')->countAllResults();
        $designRows = $this->db->table('comp_design_types')->select('comp_type_id, COUNT(*) AS cnt')->groupBy('comp_type_id')->get()->getResultArray();
        $designCounts = [];
        foreach ($designRows as $r) {
            $designCounts[(int) $r['comp_type_id']] = (int) $r['cnt'];
        }

        $retailRows = $this->db->table('comp_retail_items')->select('phase, COUNT(*) AS cnt')->whereIn('active', ['Y', '1', 1])->groupBy('phase')->get()->getResultArray();
        $phaseRetail = [];
        foreach ($retailRows as $r) {
            $phaseRetail[(int) $r['phase']] = (int) $r['cnt'];
        }

        $out = [];
        foreach ($competitions as $comp) {
            $issues = [];
            $blockers = 0;
            $warnings = 0;

            if ($questionCount === 0) {
                $issues[] = ['severity' => 'blocker', 'message' => 'No submission questions configured.'];
                $blockers++;
            }

            $ctype = (int) ($comp['comp_type_id'] ?? 0);
            if (((int) ($designCounts[$ctype] ?? 0)) === 0) {
                $issues[] = ['severity' => 'blocker', 'message' => 'No design types configured for this competition category.'];
                $blockers++;
            }
            if (((int) ($phaseRetail[1] ?? 0)) === 0) {
                $issues[] = ['severity' => 'blocker', 'message' => 'No active Phase 1 retail fee item configured.'];
                $blockers++;
            }
            if (((int) ($phaseRetail[2] ?? 0)) === 0) {
                $issues[] = ['severity' => 'warning', 'message' => 'No active Phase 2 retail fee item configured.'];
                $warnings++;
            }

            $dt = static fn (?string $v): ?int => ($v && strtotime($v) !== false) ? strtotime($v) : null;
            $p1Open = $dt((string) ($comp['comp_phase_1_open'] ?? ''));
            $p1Close = $dt((string) ($comp['comp_phase_1_close'] ?? ''));
            $p2Open = $dt((string) ($comp['comp_phase_2_open'] ?? ''));
            $p2Close = $dt((string) ($comp['comp_phase_2_close'] ?? ''));
            $jr1Open = $dt((string) ($comp['jury_phase_1_open'] ?? ''));
            $jr1Close = $dt((string) ($comp['jury_phase_1_close'] ?? ''));
            $jr2Open = $dt((string) ($comp['jury_phase_2_open'] ?? ''));
            $jr2Close = $dt((string) ($comp['jury_phase_2_close'] ?? ''));

            if ($p1Open === null || $p1Close === null || $p1Open >= $p1Close) {
                $issues[] = ['severity' => 'blocker', 'message' => 'Phase 1 open/close window is invalid.'];
                $blockers++;
            }
            if ($p2Open === null || $p2Close === null || $p2Open >= $p2Close) {
                $issues[] = ['severity' => 'blocker', 'message' => 'Phase 2 open/close window is invalid.'];
                $blockers++;
            }
            if ($jr1Open === null || $jr1Close === null || $jr1Open >= $jr1Close) {
                $issues[] = ['severity' => 'warning', 'message' => 'Jury Phase 1 window is invalid.'];
                $warnings++;
            }
            if ($jr2Open === null || $jr2Close === null || $jr2Open >= $jr2Close) {
                $issues[] = ['severity' => 'warning', 'message' => 'Jury Phase 2 window is invalid.'];
                $warnings++;
            }
            if ($p1Close !== null && $p2Open !== null && $p1Close > $p2Open) {
                $issues[] = ['severity' => 'warning', 'message' => 'Phase 1 closes after Phase 2 opens.'];
                $warnings++;
            }

            $out[] = [
                'comp_id' => (int) ($comp['comp_id'] ?? 0),
                'competition' => trim((string) ($comp['comp_type_name'] ?? '') . ' ' . (string) ($comp['comp_year'] ?? '')),
                'issues' => $issues,
                'blockers' => $blockers,
                'warnings' => $warnings,
                'is_ready' => $blockers === 0,
            ];
        }

        return ['competitions' => $out, 'question_count' => $questionCount, 'phase_retail_counts' => $phaseRetail];
    }

    public function createDiagnosticsBundle(): string
    {
        $diagDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'diagnostics';
        if (! is_dir($diagDir)) {
            @mkdir($diagDir, 0775, true);
        }
        $stamp = date('Ymd_His');
        $zipPath = $diagDir . DIRECTORY_SEPARATOR . 'diagnostics_' . $stamp . '.zip';

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is not available.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create diagnostics archive.');
        }

        $dbStatus = ['ok' => false, 'message' => 'unknown'];
        try {
            $this->db->query('SELECT 1 AS ok')->getRowArray();
            $dbStatus = ['ok' => true, 'message' => 'connected'];
        } catch (\Throwable $e) {
            $dbStatus = ['ok' => false, 'message' => $e->getMessage()];
        }

        $summary = [
            'generated_at' => date('c'),
            'php_version' => PHP_VERSION,
            'codeigniter_version' => defined(CodeIgniter::class . '::CI_VERSION')
                ? CodeIgniter::CI_VERSION
                : 'unknown',
            'app_env' => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown',
            'base_url' => (string) (config('App')->baseURL ?? ''),
            'db' => $dbStatus,
        ];

        $permissions = [
            'public_uploads' => $this->pathPermissions(rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads'),
            'public_trash' => $this->pathPermissions(rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash'),
            'writable_logs' => $this->pathPermissions(rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs'),
            'writable_diagnostics' => $this->pathPermissions($diagDir),
        ];

        $uploadHealth = $this->collectUploadQueueHealth();
        $media = $this->collectMediaCleanerReport();
        $checklist = $this->competitionReadiness();
        $retention = $this->loadRetentionRules();

        $zip->addFromString('summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('permissions.json', json_encode($permissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('upload_health_summary.json', json_encode($uploadHealth['totals'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('media_cleaner_summary.json', json_encode($media['totals'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('readiness_summary.json', json_encode([
            'competition_count' => count($checklist['competitions']),
            'question_count' => $checklist['question_count'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('retention_rules.json', json_encode($retention, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('routes_excerpt.txt', $this->loadRoutesExcerpt());
        $zip->addFromString('storage_snapshot.json', json_encode([
            'trash' => $this->dirQuickStats(rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash'),
            'uploads' => $this->dirQuickStats(rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads'),
            'logs' => $this->dirQuickStats(rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        foreach ($this->recentLogTails(3, 250) as $name => $content) {
            $zip->addFromString('logs/' . $name, $content);
        }

        $zip->close();
        return $zipPath;
    }

    public function listDiagnosticsBundles(): array
    {
        $diagDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'diagnostics';
        if (! is_dir($diagDir)) {
            return [];
        }
        $paths = glob($diagDir . DIRECTORY_SEPARATOR . 'diagnostics_*.zip') ?: [];
        $rows = [];
        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }
            $rows[] = [
                'name' => basename($path),
                'size' => (int) (@filesize($path) ?: 0),
                'mtime' => (int) (@filemtime($path) ?: 0),
                'path' => $path,
            ];
        }
        usort($rows, static fn (array $a, array $b): int => ($b['mtime'] <=> $a['mtime']));
        return $rows;
    }

    public function diagnosticsBundlePath(string $name): ?string
    {
        $safe = basename($name);
        if ($safe === '' || ! preg_match('/^diagnostics_[0-9_]+\\.zip$/', $safe)) {
            return null;
        }
        $path = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'diagnostics' . DIRECTORY_SEPARATOR . $safe;
        return is_file($path) ? $path : null;
    }

    private function fetchPhotoRowsForHealth(): array
    {
        return $this->db->table('comp_entry_photos p')
            ->select('p.entry_photo_id, p.entry_id, p.entry_photo, p.entry_certificate, p.entry_photo_res, p.entry_photo_order, e.entry_id AS entry_exists, e.photo_id, c.comp_year, t.comp_type_name')
            ->join('comp_entries e', 'e.entry_id = p.entry_id', 'left')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id', 'left')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id', 'left')
            ->get()
            ->getResultArray();
    }

    private function expectedPathsForRow(array $row): array
    {
        $year = (int) ($row['comp_year'] ?? 0);
        $photoId = trim((string) ($row['photo_id'] ?? ''));
        $order = (int) ($row['entry_photo_order'] ?? 0);
        $typeName = strtolower(trim((string) ($row['comp_type_name'] ?? '')));
        $typeDir = str_replace(['/', '\\'], '-', $typeName);
        if ($year <= 0 || $photoId === '' || $typeDir === '') {
            return ['entry_photo' => '', 'entry_certificate' => ''];
        }

        $res = strtoupper(trim((string) ($row['entry_photo_res'] ?? '')));
        if ($res === 'LOW' && $order > 0) {
            return [
                'entry_photo' => '/uploads/' . $year . '/' . $typeDir . '/CompPhotoLow_' . $order . '_' . $photoId . '.jpg',
                'entry_certificate' => '',
            ];
        }
        if ($res === 'PDF') {
            return [
                'entry_photo' => '/uploads/' . $year . '/' . $typeDir . '/CertificateImage_' . $photoId . '.jpg',
                'entry_certificate' => '/uploads/' . $year . '/' . $typeDir . '/CertificatePDF_' . $photoId . '.pdf',
            ];
        }

        return ['entry_photo' => '', 'entry_certificate' => ''];
    }

    private function scanUploadsFiles(string $uploadsRoot): array
    {
        if (! is_dir($uploadsRoot)) {
            return [];
        }
        $files = [];
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iter as $item) {
            if (! $item->isFile()) {
                continue;
            }
            $base = $item->getBasename();
            if (in_array($base, ['index.html', '.htaccess'], true)) {
                continue;
            }
            $abs = $item->getPathname();
            $rel = '/uploads/' . ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($abs, strlen($uploadsRoot))), '/');
            $files[] = [
                'abs' => $this->normalizeFsPath($abs),
                'rel' => $rel,
                'size' => (int) $item->getSize(),
                'mtime' => (int) $item->getMTime(),
                'name' => $base,
            ];
        }
        return $files;
    }

    private function collectReferencedUploadFiles(): array
    {
        $rows = $this->db->table('comp_entry_photos')
            ->select('entry_photo, entry_certificate')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            foreach (['entry_photo', 'entry_certificate'] as $field) {
                $raw = trim((string) ($row[$field] ?? ''));
                if ($raw === '') {
                    continue;
                }
                if (preg_match('#^https?://#i', $raw) === 1) {
                    $raw = (string) (parse_url($raw, PHP_URL_PATH) ?: '');
                }
                $normalized = $this->normalizeWebPath($raw);
                if ($normalized === '' || ! str_starts_with($normalized, '/uploads/')) {
                    continue;
                }
                $out[$normalized] = true;
            }
        }
        return $out;
    }

    private function normalizeWebPath(string $path): string
    {
        $path = rawurldecode(trim($path));
        if ($path === '') {
            return '';
        }
        $path = str_replace('\\', '/', $path);
        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        return strtolower($path);
    }

    private function buildMediaOrphanPurgePlan(array $files, array $hashGroups): array
    {
        $candidates = [];
        foreach ($hashGroups as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            usort($indexes, function (int $a, int $b) use ($files): int {
                $fa = $files[$a];
                $fb = $files[$b];
                $ra = ! empty($fa['is_referenced']) ? 1 : 0;
                $rb = ! empty($fb['is_referenced']) ? 1 : 0;
                if ($ra !== $rb) {
                    return $rb <=> $ra;
                }
                if ((int) $fa['mtime'] !== (int) $fb['mtime']) {
                    return ((int) $fb['mtime']) <=> ((int) $fa['mtime']);
                }
                return strcmp((string) $fa['rel'], (string) $fb['rel']);
            });

            $keeper = $indexes[0];
            foreach ($indexes as $i) {
                if ($i === $keeper) {
                    continue;
                }
                $f = $files[$i];
                if (! empty($f['is_referenced'])) {
                    continue;
                }
                $candidates[$f['abs']] = [
                    'abs' => $f['abs'],
                    'rel' => $f['rel'],
                    'size' => (int) $f['size'],
                    'reason' => 'duplicate_unreferenced',
                    'mtime' => (int) $f['mtime'],
                ];
            }
        }

        $bytes = 0;
        foreach ($candidates as $c) {
            $bytes += (int) ($c['size'] ?? 0);
        }

        $list = array_values($candidates);
        usort($list, static fn (array $a, array $b): int => ((int) $b['size']) <=> ((int) $a['size']));

        return ['reclaimable_bytes' => $bytes, 'candidates' => $list];
    }

    private function retentionConfigPath(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'system-tools' . DIRECTORY_SEPARATOR . 'retention-rules.json';
    }

    private function defaultRetentionRules(): array
    {
        return [
            'trash' => [
                'label' => 'Public Trash',
                'enabled' => true,
                'days' => 30,
                'path' => rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash',
                'glob' => '*',
            ],
            'logs' => [
                'label' => 'Writable Logs',
                'enabled' => true,
                'days' => 30,
                'path' => rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs',
                'glob' => '*.log',
            ],
            'temp_uploads' => [
                'label' => 'Temp Uploads',
                'enabled' => true,
                'days' => 7,
                'path' => rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tmp',
                'glob' => '*',
            ],
        ];
    }

    private function isAllowedMaintenancePath(string $path): bool
    {
        if ($path === '' || ! is_dir($path)) {
            return false;
        }
        $real = realpath($path);
        $public = realpath(FCPATH);
        $writable = realpath(WRITEPATH);
        if (! is_string($real) || ! is_string($public) || ! is_string($writable)) {
            return false;
        }
        $realN = $this->normalizeFsPath($real);
        return str_starts_with($realN, $this->normalizeFsPath($public))
            || str_starts_with($realN, $this->normalizeFsPath($writable));
    }

    private function deleteEmptyDirectories(string $rootPath): void
    {
        if (! is_dir($rootPath)) {
            return;
        }
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iter as $item) {
            if (! $item->isDir()) {
                continue;
            }
            $dir = $item->getPathname();
            if ($this->normalizeFsPath($dir) === $this->normalizeFsPath($rootPath)) {
                continue;
            }
            $left = @scandir($dir);
            if (is_array($left) && count($left) <= 2) {
                @rmdir($dir);
            }
        }
    }

    private function resolveAbsolutePath(string $rawPath): ?string
    {
        $path = (string) (parse_url($rawPath, PHP_URL_PATH) ?: $rawPath);
        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
        $rel = ltrim($path, '/');
        $public = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_file($public) || is_dir(dirname($public))) {
            return $public;
        }
        $writable = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_file($writable) || is_dir(dirname($writable))) {
            return $writable;
        }
        return null;
    }

    private function normalizeFsPath(string $path): string
    {
        return strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
    }

    private function pathPermissions(string $path): array
    {
        return [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => is_readable($path),
            'writable' => is_writable($path),
        ];
    }

    private function loadRoutesExcerpt(): string
    {
        $path = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
        if (! is_file($path)) {
            return 'Routes file not found.';
        }
        $content = (string) @file_get_contents($path);
        if ($content === '') {
            return 'Routes file is empty.';
        }
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        return implode(PHP_EOL, array_slice($lines, 0, 300));
    }

    private function dirQuickStats(string $path): array
    {
        if (! is_dir($path)) {
            return ['exists' => false, 'files' => 0, 'bytes' => 0];
        }
        $files = 0;
        $bytes = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        /** @var SplFileInfo $item */
        foreach ($iter as $item) {
            if ($item->isFile()) {
                $files++;
                $bytes += (int) $item->getSize();
            }
        }
        return ['exists' => true, 'files' => $files, 'bytes' => $bytes];
    }

    private function recentLogTails(int $fileCount, int $lineCount): array
    {
        $logDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        if (! is_dir($logDir)) {
            return [];
        }
        $logs = glob($logDir . DIRECTORY_SEPARATOR . '*.log') ?: [];
        usort($logs, static fn (string $a, string $b): int => ((int) @filemtime($b)) <=> ((int) @filemtime($a)));
        $logs = array_slice($logs, 0, $fileCount);
        $out = [];
        foreach ($logs as $path) {
            $name = basename($path);
            $out[$name] = $this->sanitizeDiagnosticsText($this->tailFile($path, $lineCount));
        }
        return $out;
    }

    private function tailFile(string $path, int $lineCount): string
    {
        if (! is_file($path) || $lineCount <= 0) {
            return '';
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (! is_array($lines)) {
            return '';
        }
        return implode(PHP_EOL, array_slice($lines, -$lineCount));
    }

    private function composerPackageVersion(string $package): ?string
    {
        $lockPath = rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.lock';
        if (! is_file($lockPath)) {
            return null;
        }
        $json = @json_decode((string) @file_get_contents($lockPath), true);
        if (! is_array($json)) {
            return null;
        }

        foreach (['packages', 'packages-dev'] as $key) {
            $rows = $json[$key] ?? [];
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((string) ($row['name'] ?? '') === $package) {
                    return (string) ($row['version'] ?? '');
                }
            }
        }

        return null;
    }

    private function probeHttpsEndpoint(string $url, int $timeoutSeconds = 3): array
    {
        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '');
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($host === '') {
            return ['ok' => false, 'latency_ms' => null, 'error' => 'invalid_host'];
        }

        $start = microtime(true);
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeoutSeconds);
        $latency = (int) round((microtime(true) - $start) * 1000);
        if (! is_resource($socket)) {
            return [
                'ok' => false,
                'latency_ms' => $latency,
                'error' => trim($errstr) !== '' ? $errstr : ('errno_' . $errno),
            ];
        }
        @fclose($socket);
        return ['ok' => true, 'latency_ms' => $latency, 'error' => null];
    }

    private function safeTableCount(string $table): ?int
    {
        try {
            return (int) $this->db->table($table)->countAllResults();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function sanitizeDiagnosticsText(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $text) ?? $text;
        $text = preg_replace('/(password|pwd|token|secret)\s*[:=]\s*([^\s]+)/i', '$1=[redacted]', $text) ?? $text;
        return $text;
    }
}
