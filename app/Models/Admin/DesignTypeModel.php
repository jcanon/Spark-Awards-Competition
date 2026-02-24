<?php

namespace App\Models\Admin;

use CodeIgniter\Model;
use App\Entities\Admin\DesignType;

class DesignTypeModel extends Model
{
    protected $table = 'comp_design_types';
    protected $primaryKey = 'design_type_id';
    protected $returnType = DesignType::class;
    protected $useTimestamps = false;

    protected $allowedFields = ['comp_type_id', 'design_type_name'];

    protected $validationRules = [
        'comp_type_id' => 'required|integer',
        'design_type_name' => 'required|max_length[200]',
    ];
}