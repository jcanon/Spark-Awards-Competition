<?php

namespace App\Models\Admin;

use CodeIgniter\Model;
use App\Entities\Admin\Coupon;

class CouponModel extends Model
{
    protected $table = 'comp_coupons';
    protected $primaryKey = 'coupon_id';
    protected $returnType = Coupon::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'coupon_code',
        'coupon_type',
        'coupon_comp',
        'coupon_start_date',
        'coupon_end_date',
        'coupon_amount',
    ];

    protected $validationRules = [
        'coupon_code' => 'required|max_length[25]',
        'coupon_type' => 'required|in_list[Dollar,Percentage]',
        'coupon_comp' => 'required|integer',
        'coupon_start_date' => 'required|valid_date[Y-m-d H:i:s]',
        'coupon_end_date' => 'required|valid_date[Y-m-d H:i:s]',
        'coupon_amount' => 'required|regex_match[/^[0-9]+$/]|greater_than[0]',
    ];
}
