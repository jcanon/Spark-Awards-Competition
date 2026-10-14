<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCouponPhase extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('coupon_phase', 'comp_coupons')) {
            $this->forge->addColumn('comp_coupons', [
                'coupon_phase' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('comp_coupons', 'coupon_phase');
    }
}
