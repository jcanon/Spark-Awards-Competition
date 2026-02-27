<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEntryCertificateRequests extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('comp_entry_certificate_requests')) {
            return;
        }

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `comp_entry_certificate_requests` (
                `certificate_request_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `entry_id` VARCHAR(36) NOT NULL,
                `user_id` VARCHAR(36) NOT NULL,
                `official_level` VARCHAR(50) NOT NULL DEFAULT '',
                `award_category` VARCHAR(120) NOT NULL DEFAULT '',
                `certificate_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
                `designer_names` VARCHAR(120) NOT NULL DEFAULT '',
                `additional_persons` VARCHAR(30) NOT NULL DEFAULT '',
                `printed_organization` VARCHAR(120) NOT NULL DEFAULT '',
                `contact_person` VARCHAR(120) NOT NULL DEFAULT '',
                `contact_phone` VARCHAR(25) NOT NULL DEFAULT '',
                `contact_email` VARCHAR(120) NOT NULL DEFAULT '',
                `shipping_method` VARCHAR(30) NOT NULL DEFAULT 'least_expensive',
                `shipping_company` VARCHAR(120) NOT NULL DEFAULT '',
                `shipping_address1` VARCHAR(150) NOT NULL DEFAULT '',
                `shipping_address2` VARCHAR(150) NOT NULL DEFAULT '',
                `shipping_city` VARCHAR(80) NOT NULL DEFAULT '',
                `shipping_state` VARCHAR(80) NOT NULL DEFAULT '',
                `shipping_postal_code` VARCHAR(30) NOT NULL DEFAULT '',
                `shipping_country` VARCHAR(80) NOT NULL DEFAULT '',
                `lamination_requested` TINYINT(1) NOT NULL DEFAULT 0,
                `lamination_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
                `special_instructions` TEXT NULL,
                `request_status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
                `admin_notes` TEXT NULL,
                `requested_at` DATETIME NULL,
                `reviewed_at` DATETIME NULL,
                `reviewed_by` VARCHAR(36) NOT NULL DEFAULT '',
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`certificate_request_id`),
                UNIQUE KEY `uniq_comp_entry_certificate_requests_entry` (`entry_id`),
                KEY `idx_comp_entry_certificate_requests_user` (`user_id`),
                KEY `idx_comp_entry_certificate_requests_status` (`request_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down()
    {
        // Intentionally non-destructive.
    }
}

