<?php

namespace App\Models\Payments;

use CodeIgniter\Model;
use App\Entities\Payments\EntryPayment;

class EntryPaymentModel extends Model
{
    protected $table = 'comp_entry_payments';
    protected $primaryKey = 'payment_id';
    protected $returnType = EntryPayment::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_id',
        'payment_phase',
        'payment_total',
        'coupon_code',
        'winner_circle',
        'spark_trophy',
        'spark_trophy_qty',
        'payment_receipt',
        'payment_date',
    ];

    protected $validationRules = [
        'entry_id' => 'required|max_length[35]',
        'payment_phase' => 'required|integer',
        'payment_total' => 'permit_empty|max_length[25]',
        'coupon_code' => 'permit_empty|max_length[25]',
        'winner_circle' => 'permit_empty|in_list[No,Yes]',
        'spark_trophy' => 'permit_empty|in_list[No,Yes]',
        'spark_trophy_qty' => 'permit_empty|integer',
        'payment_date' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
}