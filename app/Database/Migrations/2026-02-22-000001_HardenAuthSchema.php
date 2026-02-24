<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenAuthSchema extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (strtolower((string)$db->DBDriver) !== 'mysqli') {
            return;
        }

        if ($db->tableExists('comp_users') && !$db->fieldExists('password_changed_at', 'comp_users')) {
            $this->forge->addColumn('comp_users', [
                'password_changed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'password',
                ],
            ]);
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS `comp_user_password_history` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` VARCHAR(36) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `source` VARCHAR(20) NOT NULL DEFAULT 'unknown',
                PRIMARY KEY (`id`),
                INDEX `idx_password_history_user_changed` (`user_id`, `changed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        if (!$this->hasForeignKey('comp_user_password_history', 'fk_password_history_user')
            && $db->tableExists('comp_users')
            && $db->fieldExists('user_id', 'comp_users')
        ) {
            try {
                $db->query(
                    "ALTER TABLE `comp_user_password_history`
                        ADD CONSTRAINT `fk_password_history_user`
                        FOREIGN KEY (`user_id`) REFERENCES `comp_users` (`user_id`)
                        ON DELETE CASCADE"
                );
            } catch (\Throwable $e) {
                log_message('warning', 'Skipping fk_password_history_user creation: {message}', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS `comp_password_resets` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` VARCHAR(36) NOT NULL,
                `token` VARCHAR(128) NOT NULL,
                `selector` VARCHAR(32) DEFAULT NULL,
                `token_hash` VARCHAR(255) DEFAULT NULL,
                `expires_at` DATETIME NOT NULL,
                `used_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        if (!$db->fieldExists('selector', 'comp_password_resets')) {
            $this->forge->addColumn('comp_password_resets', [
                'selector' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                    'after' => 'token',
                ],
            ]);
        }
        if (!$db->fieldExists('token_hash', 'comp_password_resets')) {
            $this->forge->addColumn('comp_password_resets', [
                'token_hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'selector',
                ],
            ]);
        }
        if (!$db->fieldExists('used_at', 'comp_password_resets')) {
            $this->forge->addColumn('comp_password_resets', [
                'used_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'expires_at',
                ],
            ]);
        }
        if (!$db->fieldExists('created_at', 'comp_password_resets')) {
            $this->forge->addColumn('comp_password_resets', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'used_at',
                ],
            ]);
        }

        $db->query(
            "UPDATE `comp_password_resets`
                SET `token` = SUBSTRING(SHA2(CONCAT('legacy-token:', `id`, ':', COALESCE(`user_id`, '')), 256), 1, 64)
              WHERE `token` IS NULL OR `token` = ''"
        );
        $db->query(
            "UPDATE `comp_password_resets`
                SET `selector` = SUBSTRING(SHA2(CONCAT('legacy-selector:', `id`, ':', `token`), 256), 1, 32)
              WHERE `selector` IS NULL OR `selector` = ''"
        );
        $db->query("UPDATE `comp_password_resets` SET `token_hash` = '' WHERE `token_hash` IS NULL");

        if (!$this->hasIndex('comp_password_resets', 'uniq_comp_password_resets_token')) {
            $db->query("ALTER TABLE `comp_password_resets` ADD UNIQUE KEY `uniq_comp_password_resets_token` (`token`)");
        }
        if (!$this->hasIndex('comp_password_resets', 'uniq_comp_password_resets_selector')) {
            $db->query("ALTER TABLE `comp_password_resets` ADD UNIQUE KEY `uniq_comp_password_resets_selector` (`selector`)");
        }
        if (!$this->hasIndex('comp_password_resets', 'idx_comp_password_resets_user_used_expires')) {
            $db->query("ALTER TABLE `comp_password_resets` ADD INDEX `idx_comp_password_resets_user_used_expires` (`user_id`, `used_at`, `expires_at`)");
        }

        $db->query("ALTER TABLE `comp_password_resets` MODIFY `selector` VARCHAR(32) NOT NULL");
        $db->query("ALTER TABLE `comp_password_resets` MODIFY `token_hash` VARCHAR(255) NOT NULL");
    }

    public function down()
    {
        // Intentionally non-destructive.
        // This migration hardens schema expected by auth flows.
    }

    private function hasIndex(string $table, string $index): bool
    {
        $row = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index])->getRowArray();
        return (bool)$row;
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        $row = $this->db->query(
            "SELECT 1
               FROM information_schema.TABLE_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND CONSTRAINT_NAME = ?
              LIMIT 1",
            [$table, $constraint]
        )->getRowArray();

        return (bool)$row;
    }
}
