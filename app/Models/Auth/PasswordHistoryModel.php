<?php

namespace App\Models\Auth;

use CodeIgniter\Model;

class PasswordHistoryModel extends Model
{
    protected $table = 'comp_user_password_history';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'password_hash', 'changed_at', 'source'];
    protected $returnType = 'array';
    public $useTimestamps = false;
}

