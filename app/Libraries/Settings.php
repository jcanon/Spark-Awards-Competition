<?php

namespace App\Libraries;

use App\Models\CompSettingsModel;

class Settings
{
    /** @var array<string,mixed> */
    private array $row;

    public function __construct()
    {
        $this->row = model(CompSettingsModel::class)->first() ?? [];
    }

    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->row) ? $this->row[$key] : $default;
    }

    /** If you later add writes, fetch fresh from DB */
    public function refresh(): void
    {
        $this->row = model(CompSettingsModel::class)->first() ?? [];
    }
}
