<?php

namespace App\Models\Accounts;

use CodeIgniter\Model;
use App\Entities\Accounts\UserType;

class UserTypeModel extends Model
{
    protected $table = 'comp_user_type';
    protected $primaryKey = 'user_type_id';
    protected $returnType = UserType::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_type_name',
        'user_type_pricing',
    ];

    protected $validationRules = [
        'user_type_name' => 'required|max_length[100]',
        'user_type_pricing' => 'required|in_list[Pro,Student]',
    ];
}