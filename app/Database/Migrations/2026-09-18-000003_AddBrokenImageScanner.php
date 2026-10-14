<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBrokenImageScanner extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11],
            'payload' => ['type' => 'LONGTEXT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('image_scan_job', true);
        $this->forge->addField([
            'photo_id' => ['type' => 'INT', 'constraint' => 11],
            'fingerprint' => ['type' => 'VARCHAR', 'constraint' => 64],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'run_id' => ['type' => 'VARCHAR', 'constraint' => 32],
            'payload' => ['type' => 'LONGTEXT'],
            'message' => ['type' => 'TEXT'],
        ]);
        $this->forge->addKey('photo_id', true);
        $this->forge->addKey(['run_id', 'status']);
        $this->forge->createTable('image_scan_items', true);
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'year' => ['type' => 'INT', 'constraint' => 4],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'path' => ['type' => 'TEXT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['year', 'name']);
        $this->forge->createTable('image_scan_files', true);
    }

    public function down()
    {
        foreach (['image_scan_files', 'image_scan_items', 'image_scan_job'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
