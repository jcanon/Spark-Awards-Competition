<?php

namespace App\Entities\Admin;

use CodeIgniter\Entity\Entity;

class DesignType extends Entity
{
    protected $casts = [
        'design_type_id' => 'integer',
        'comp_type_id' => 'integer',
    ];
}