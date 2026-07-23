<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompetitionLatePaymentWindow extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('comp_competitions')) {
            return;
        }

        $fields = $this->db->getFieldData('comp_competitions');
        $fieldNames = array_map(static fn ($field): string => strtolower((string)($field->name ?? '')), $fields);

        if (!in_array('late_payment_open', $fieldNames, true)) {
            $this->db->query("ALTER TABLE `comp_competitions` ADD COLUMN `late_payment_open` DATETIME NULL AFTER `jury_phase_2_close`");
        }

        if (!in_array('late_payment_close', $fieldNames, true)) {
            $this->db->query("ALTER TABLE `comp_competitions` ADD COLUMN `late_payment_close` DATETIME NULL AFTER `late_payment_open`");
        }
    }

    public function down()
    {
        // Intentionally non-destructive.
    }
}
