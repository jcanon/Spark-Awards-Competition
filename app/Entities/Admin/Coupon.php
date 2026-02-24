<?php

namespace App\Entities\Admin;

use CodeIgniter\Entity\Entity;

class Coupon extends Entity
{
    protected $dates = ['coupon_start_date','coupon_end_date'];

    protected $casts = [
        'coupon_id' => 'integer',
        'coupon_comp' => 'integer',
    ];
}