<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSingleUseCoupons extends Migration
{
    public function up()
    {
        $this->forge->addColumn('comp_coupons', [
            'coupon_single_use' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'coupon_payment_id' => ['type' => 'INT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('comp_coupons', ['coupon_single_use', 'coupon_payment_id']);
    }
}
