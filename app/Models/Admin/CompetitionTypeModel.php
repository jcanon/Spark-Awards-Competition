<?php

namespace App\Models\Admin;

use CodeIgniter\Model;
use App\Entities\Admin\CompetitionType;

class CompetitionTypeModel extends Model
{
    protected $table = 'comp_type';
    protected $primaryKey = 'comp_type_id';
    protected $returnType = CompetitionType::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'comp_type_name',
        'is_student_comp',
        'archived',
    ];

    protected $validationRules = [
        'comp_type_name' => 'required|max_length[100]',
        'is_student_comp' => 'required|in_list[0,1]|integer',
        'archived' => 'permit_empty|in_list[0,1]|integer',
    ];
}
