<?php

namespace App\Models\Admin;

use CodeIgniter\Model;
use App\Entities\Admin\CompSettings;

class CompSettingsModel extends Model
{
    protected $table = 'comp_settings';
    protected $primaryKey = 'url';
    protected $returnType = CompSettings::class;
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

    protected $validationRules = [
        'url' => 'required|max_length[100]',
        'email_server' => 'permit_empty|max_length[100]',
        'email_username' => 'permit_empty|max_length[100]',
        'email_password' => 'permit_empty|max_length[100]',
        'email' => 'permit_empty|valid_email|max_length[100]',
        'title' => 'permit_empty|max_length[200]',
        'site_maintenance' => 'permit_empty|in_list[0,1]',
        'site_maintenance_message' => 'permit_empty|max_length[2000]',
    ];
}
