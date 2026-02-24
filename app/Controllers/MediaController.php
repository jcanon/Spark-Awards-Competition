<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Judging\EntryPhotoModel;

class MediaController extends BaseController
{
    public function photo(int $photoId)
    {
        if (!session('uid')) {
            return $this->response->setStatusCode(401);
        }

        $photo = (new EntryPhotoModel())->asArray()->find($photoId);
        if (!$photo) {
            return $this->response->setStatusCode(404);
        }

        if (!$this->canAccessEntry((string)$photo['entry_id'])) {
            return $this->response->setStatusCode(403);
        }

        $url = (string)$photo['entry_photo'];
        if (preg_match('#^https?://#i', $url) === 1) {
            return redirect()->to($url);
        }

        $path = ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/\\');
        $abs = FCPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (!is_file($abs)) {
            $alt = WRITEPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (!is_file($alt)) {
                return $this->response->setStatusCode(404);
            }
            $abs = $alt;
        }

        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = $ext === 'pdf' ? 'application/pdf' : 'image/jpeg';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string)file_get_contents($abs));
    }

    private function canAccessEntry(string $entryId): bool
    {
        $role = (string)session('role');
        if (in_array($role, ['admin', 'editor', 'judge'], true)) {
            return true;
        }

        $entry = db_connect()->table('comp_entries')
            ->select('user_id')
            ->where('entry_id', $entryId)
            ->get()
            ->getRowArray();

        if (!$entry) {
            return false;
        }

        return hash_equals((string)$entry['user_id'], (string)session('uid'));
    }
}
