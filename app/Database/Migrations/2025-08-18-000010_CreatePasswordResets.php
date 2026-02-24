<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordResets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'auto_increment' => true, 'unsigned' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 40],
            'token' => ['type' => 'VARCHAR', 'constraint' => 128],
            'expires_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('token', false, true);
        $this->forge->createTable('password_resets', true);
    }

    public function down()
    {
        $this->forge->dropTable('password_resets');
    }
}
