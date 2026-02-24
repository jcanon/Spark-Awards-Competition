<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'comp_notifications';
    protected $primaryKey = 'notification_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'title',
        'message',
        'level',
        'audience',
        'link_url',
        'link_text',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
