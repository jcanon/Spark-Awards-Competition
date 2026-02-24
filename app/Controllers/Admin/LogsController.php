<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use RuntimeException;
use SplFileObject;

class LogsController extends BaseController
{
    public function index()
    {
        $logsPath = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        $files = $this->listLogFiles($logsPath);

        $requested = basename((string) $this->request->getGet('file'));
        $selectedFile = '';
        if ($requested !== '') {
            foreach ($files as $file) {
                if ($file['name'] === $requested) {
                    $selectedFile = $requested;
                    break;
                }
            }
        }
        if ($selectedFile === '' && $files !== []) {
            $selectedFile = (string) $files[0]['name'];
        }

        $preview = '';
        if ($selectedFile !== '') {
            $preview = $this->tailFile($logsPath . DIRECTORY_SEPARATOR . $selectedFile, 250);
        }

        $totalBytes = 0;
        foreach ($files as $file) {
            $totalBytes += (int) ($file['size'] ?? 0);
        }

        return view('admin/logs/index', [
            'logsExists' => is_dir($logsPath),
            'logsPath' => $logsPath,
            'files' => $files,
            'fileCount' => count($files),
            'totalBytes' => $totalBytes,
            'selectedFile' => $selectedFile,
            'preview' => $preview,
            'canDelete' => (string) session('role') !== 'editor',
        ]);
    }

    public function purge()
    {
        if ((string) session('role') === 'editor') {
            return redirect()->to('/admin/system-tools/logs')->with('error', 'Editors are not allowed to delete records.');
        }

        $logsPath = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        if (! is_dir($logsPath)) {
            return redirect()->to('/admin/system-tools/logs')->with('error', 'Logs directory does not exist.');
        }

        try {
            $deleted = $this->purgeLogFiles($logsPath);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/system-tools/logs')->with('error', 'Log purge failed: ' . $e->getMessage());
        }

        return redirect()->to('/admin/system-tools/logs')->with('success', 'Log purge complete. Deleted ' . $deleted . ' file(s).');
    }

    private function listLogFiles(string $logsPath): array
    {
        if (! is_dir($logsPath)) {
            return [];
        }

        $paths = glob($logsPath . DIRECTORY_SEPARATOR . '*.log') ?: [];
        $rows = [];
        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }
            $rows[] = [
                'name' => basename($path),
                'size' => (int) (@filesize($path) ?: 0),
                'mtime' => (int) (@filemtime($path) ?: 0),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return ($b['mtime'] <=> $a['mtime']) ?: strcmp((string) $b['name'], (string) $a['name']);
        });

        return $rows;
    }

    private function purgeLogFiles(string $logsPath): int
    {
        $count = 0;
        $paths = glob($logsPath . DIRECTORY_SEPARATOR . '*.log') ?: [];
        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }
            if (! @unlink($path)) {
                throw new RuntimeException('Unable to delete log file: ' . basename($path));
            }
            $count++;
        }
        return $count;
    }

    private function tailFile(string $path, int $lines): string
    {
        if (! is_file($path) || $lines <= 0) {
            return '';
        }

        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $start = max(0, $lastLine - $lines + 1);

        $buffer = [];
        $file->seek($start);
        while (! $file->eof()) {
            $buffer[] = (string) $file->current();
            $file->next();
        }

        return trim(implode('', $buffer));
    }
}
