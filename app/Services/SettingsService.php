<?php

namespace App\Services;

use App\Models\Admin\CompSettingsModel;
use App\Entities\Admin\CompSettings;

class SettingsService
{
    // Request-scoped cache of the settings Entity
    private ?CompSettings $cache = null;

    /**
     * Generic getter: reads a property from the CompSettings entity.
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->entity();
        if ($settings === null) {
            return $default;
        }

        $val = $settings->{$key} ?? null;
        return $val !== null ? $val : $default;
    }

    /**
     * Site title, with default.
     */
    public function getTitle(): string
    {
        $title = (string) ($this->get('title') ?? '');
        return $title !== '' ? $title : 'Spark Awards';
    }

    /**
     * Return the settings Entity (or null if none).
     */
    public function entity(): ?CompSettings
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        // If multiple rows ever exist, we take the first by convention.
        $row = (new CompSettingsModel())
            ->orderBy('url', 'ASC')
            ->first();

        $this->cache = $row instanceof CompSettings ? $row : null;
        return $this->cache;
    }

    public function reload(): void
    {
        $this->cache = null;
    }
}
