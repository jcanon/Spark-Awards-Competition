<?php

namespace App\Models\Admin;

use CodeIgniter\Model;
use App\Entities\Admin\Setting;

class SettingsModel extends Model
{
    protected $table = 'comp_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'url',
        'email_server',
        'email_username',
        'email_password',
        'email',
        'title',
        'site_maintenance',
        'site_maintenance_message',
    ];

    public function getSettings(): ?Setting
    {
        // Assumes a single row table
        return $this->orderBy('id', 'DESC')->first();
    }
}
