<?php

namespace App\Models\Auth;

use CodeIgniter\Model;

class RecoveryCodeModel extends Model
{
    protected $table = 'comp_user_recovery_codes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'code_hash',
        'used_at',
        'expires_at',
        'created_at',
    ];
    public $useTimestamps = false;
}
