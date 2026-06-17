<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEntryPaymentStatuses extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('comp_entry_payments')) {
            return;
        }

        $fields = $this->db->getFieldData('comp_entry_payments');
        $fieldNames = array_map(static fn ($field): string => strtolower((string)($field->name ?? '')), $fields);

        if (!in_array('payment_status', $fieldNames, true)) {
            $this->db->query("ALTER TABLE `comp_entry_payments` ADD COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER `payment_receipt`");
        }

        if (!in_array('payment_transaction_id', $fieldNames, true)) {
            $this->db->query("ALTER TABLE `comp_entry_payments` ADD COLUMN `payment_transaction_id` VARCHAR(40) NOT NULL DEFAULT '' AFTER `payment_status`");
        }

        if (!in_array('payment_status_message', $fieldNames, true)) {
            $this->db->query("ALTER TABLE `comp_entry_payments` ADD COLUMN `payment_status_message` TEXT NULL AFTER `payment_transaction_id`");
        }

        if (!in_array('payment_status_updated_at', $fieldNames, true)) {
            $this->db->query("ALTER TABLE `comp_entry_payments` ADD COLUMN `payment_status_updated_at` DATETIME NULL AFTER `payment_status_message`");
        }

        $this->db->query(
            "UPDATE `comp_entry_payments`
             SET `payment_status` = CASE
                 WHEN COALESCE(`payment_receipt`, '') <> '' THEN 'paid'
                 ELSE 'pending'
             END,
             `payment_status_updated_at` = COALESCE(`payment_date`, `payment_status_updated_at`, NOW())
             WHERE COALESCE(`payment_status`, '') = '' OR `payment_status` = 'pending'"
        );
    }

    public function down()
    {
        // Intentionally non-destructive.
    }
}
