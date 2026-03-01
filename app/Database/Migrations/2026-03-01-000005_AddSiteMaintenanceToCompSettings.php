<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSiteMaintenanceToCompSettings extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('site_maintenance', 'comp_settings')) {
            $fields['site_maintenance'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'after' => 'title',
            ];
        }

        if (!$this->db->fieldExists('site_maintenance_message', 'comp_settings')) {
            $fields['site_maintenance_message'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'site_maintenance',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('comp_settings', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('site_maintenance_message', 'comp_settings')) {
            $this->forge->dropColumn('comp_settings', 'site_maintenance_message');
        }

        if ($this->db->fieldExists('site_maintenance', 'comp_settings')) {
            $this->forge->dropColumn('comp_settings', 'site_maintenance');
        }
    }
}
