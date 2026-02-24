<?php

namespace App\Services\Admin;

use App\Models\Admin\CompSettingsModel;
use App\Entities\Admin\CompSettings;

/**
 * Admin-facing settings service using the CompSettings entity.
 */
class AdminSettingsService
{
    /**
     * Returns the single settings row as an Entity.
     */
    public function getSettings(): ?CompSettings
    {
        /** @var CompSettings|null $row */
        $row = (new CompSettingsModel())->orderBy('url', 'ASC')->first();
        return $row ?: null;
    }

    /**
     * Update or insert the single settings row.
     * Accepts a partial payload.
     */
    public function updateSettings(array $data): bool
    {
        $model = new CompSettingsModel();

        /** @var CompSettings|null $current */
        $current = $model->orderBy('url', 'ASC')->first();

        $payload = [
            'url' => (string)($data['url'] ?? ($current->url ?? '')),
            'email_server' => (string)($data['email_server'] ?? ($current->email_server ?? '')),
            'email_username' => (string)($data['email_username'] ?? ($current->email_username ?? '')),
            'email_password' => (string)($data['email_password'] ?? ($current->email_password ?? '')),
            'email' => (string)($data['email'] ?? ($current->email ?? '')),
            'title' => (string)($data['title'] ?? ($current->title ?? '')),
        ];

        if ($current) {
            // Primary key is 'url' per model; update by PK
            return $model->update((string)$current->url, $payload);
        }

        return (bool)$model->insert($payload);
    }
}