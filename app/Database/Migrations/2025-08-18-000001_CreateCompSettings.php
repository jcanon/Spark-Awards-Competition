<?php

// app/Database/Migrations/2025-08-18-000001_CreateCompSettings.php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 255],
            'email_server' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email_username' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email_password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('comp_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('comp_settings');
    }
}
