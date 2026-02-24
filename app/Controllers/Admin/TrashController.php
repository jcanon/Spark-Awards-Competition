<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class TrashController extends BaseController
{
    public function index()
    {
        $trashPath = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash';
        $stats = $this->collectTrashStats($trashPath);

        return view('admin/trash/index', [
            'trashExists' => is_dir($trashPath),
            'trashPath' => $trashPath,
            'photoCount' => $stats['photo_count'],
            'fileCount' => $stats['file_count'],
            'dirCount' => $stats['dir_count'],
            'totalBytes' => $stats['total_bytes'],
            'canDelete' => (string) session('role') !== 'editor',
        ]);
    }

    public function purge()
    {
        if ((string) session('role') === 'editor') {
            return redirect()->to('/admin/system-tools/trash')->with('error', 'Editors are not allowed to delete records.');
        }

        $trashPath = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash';
        if (! is_dir($trashPath)) {
            return redirect()->to('/admin/system-tools/trash')->with('error', 'Trash directory does not exist.');
        }

        try {
            [$removedFiles, $removedDirs] = $this->purgeTrash($trashPath);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/system-tools/trash')->with('error', 'Trash purge failed: ' . $e->getMessage());
        }

        return redirect()->to('/admin/system-tools/trash')
            ->with('success', 'Trash purged. Removed ' . $removedFiles . ' file(s) and ' . $removedDirs . ' folder(s).');
    }

    private function collectTrashStats(string $trashPath): array
    {
        if (! is_dir($trashPath)) {
            return [
                'photo_count' => 0,
                'file_count' => 0,
                'dir_count' => 0,
                'total_bytes' => 0,
            ];
        }

        $photoExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff'];
        $photoCount = 0;
        $fileCount = 0;
        $dirCount = 0;
        $totalBytes = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($trashPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $dirCount++;
                continue;
            }
            if (! $item->isFile()) {
                continue;
            }

            $fileCount++;
            $totalBytes += (int) $item->getSize();
            $ext = strtolower((string) $item->getExtension());
            if (in_array($ext, $photoExt, true)) {
                $photoCount++;
            }
        }

        return [
            'photo_count' => $photoCount,
            'file_count' => $fileCount,
            'dir_count' => $dirCount,
            'total_bytes' => $totalBytes,
        ];
    }

    private function purgeTrash(string $trashPath): array
    {
        $removedFiles = 0;
        $removedDirs = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($trashPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isFile() || $item->isLink()) {
                if (! @unlink($path)) {
                    throw new RuntimeException('Unable to delete file: ' . $path);
                }
                $removedFiles++;
                continue;
            }
            if ($item->isDir()) {
                if (! @rmdir($path)) {
                    throw new RuntimeException('Unable to delete folder: ' . $path);
                }
                $removedDirs++;
            }
        }

        return [$removedFiles, $removedDirs];
    }
}
