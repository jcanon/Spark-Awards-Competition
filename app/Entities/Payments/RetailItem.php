<?php

namespace App\Entities\Payments;

use CodeIgniter\Entity\Entity;

class RetailItem extends Entity
{
    protected $casts = [
        'retail_item_id' => 'integer',
        'item_price' => 'integer',
        'phase' => 'integer',
    ];

    public function isActive(): bool
    {
        $v = $this->attributes['active'] ?? 'N';
        return $v === 'Y' || $v === '1' || $v === 1;
    }
}