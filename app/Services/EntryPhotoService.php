<?php

namespace App\Services;

use App\Models\Judging\EntryPhotoModel;
use App\Entities\Judging\EntryPhoto;
use RuntimeException;

class EntryPhotoService
{
    public function addPhoto(string $entryId, string $url, ?string $caption, string $res, int $order = 1): int
    {
        $model = new EntryPhotoModel();
        $model->insert([
            'entry_id' => $entryId,
            'entry_photo' => $url,
            'entry_photo_caption' => $caption ?? '',
            'entry_photo_res' => $res,
            'entry_photo_order' => $order,
        ], false);

        return (int)$model->getInsertID();
    }

    public function updatePhoto(int $entryPhotoId, array $changes): bool
    {
        $allowed = ['entry_photo', 'entry_photo_caption', 'entry_photo_res', 'entry_photo_order', 'entry_certificate'];
        $payload = array_intersect_key($changes, array_flip($allowed));
        if ($payload === []) {
            return true;
        }
        return (bool)(new EntryPhotoModel())->update($entryPhotoId, $payload);
    }

    public function reorderPhotos(string $entryId, string $res, array $orderedPhotoIds): void
    {
        $model = new EntryPhotoModel();
        $i = 1;
        foreach ($orderedPhotoIds as $pid) {
            $model->where('entry_id', $entryId)
                ->where('entry_photo_res', $res)
                ->where('entry_photo_id', (int)$pid)
                ->set('entry_photo_order', $i++)
                ->update();
        }
    }

    public function certificateFor(string $entryId, string $res): ?EntryPhoto
    {
        return (new EntryPhotoModel())
            ->where('entry_id', $entryId)
            ->where('entry_photo_res', $res)
            ->first();
    }

    public function deletePhotoWithFile(int $entryPhotoId): bool
    {
        $model = new EntryPhotoModel();
        /** @var EntryPhoto|null $photo */
        $photo = $model->find($entryPhotoId);
        if (!$photo) {
            return false;
        }

        $abs = $this->absolutePathFromUrl((string)$photo->entry_photo);
        $this->moveToTrash($abs);

        return (bool)$model->delete($entryPhotoId);
    }

    // ---- file helpers (same behavior used in submissions) ----

    private function moveToTrash(string $absPath): void
    {
        $trash = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trash';
        if (!is_dir($trash)) {
            @mkdir($trash, 0775, true);
        }
        @rename($absPath, $trash . DIRECTORY_SEPARATOR . basename($absPath));
    }

    private function absolutePathFromUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? $url;
        $path = ltrim($path, '/');
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $public = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalized;
        if (is_file($public)) {
            return $public;
        }
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalized;
    }
}
