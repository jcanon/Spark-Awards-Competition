<?php

namespace App\Entities\Payments;

use CodeIgniter\Entity\Entity;

class RetailItemUser extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'retail_item_id' => 'integer',
        'quantity' => 'integer',
    ];
}