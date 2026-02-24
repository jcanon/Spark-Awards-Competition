<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecoveryCodes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('comp_user_recovery_codes')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 36],
            'code_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'used_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'used_at', 'expires_at'], false, false, 'idx_recovery_user_state');
        $this->forge->createTable('comp_user_recovery_codes', true);
    }

    public function down()
    {
        $this->forge->dropTable('comp_user_recovery_codes', true);
    }
}
