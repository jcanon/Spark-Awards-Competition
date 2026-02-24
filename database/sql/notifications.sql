-- Spark Awards Notifications Schema
-- Run this on your MySQL database.

CREATE TABLE IF NOT EXISTS `comp_notifications` (
  `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `level` ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `audience` ENUM('all','admin','editor','judge','user') NOT NULL DEFAULT 'all',
  `link_url` VARCHAR(255) DEFAULT NULL,
  `link_text` VARCHAR(60) DEFAULT NULL,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_by` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_comp_notifications_active` (`is_active`),
  KEY `idx_comp_notifications_audience` (`audience`),
  KEY `idx_comp_notifications_window` (`starts_at`,`ends_at`),
  KEY `idx_comp_notifications_sort` (`sort_order`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional demo seed rows
INSERT INTO `comp_notifications`
(`title`, `message`, `level`, `audience`, `link_url`, `link_text`, `starts_at`, `ends_at`, `is_active`, `sort_order`, `created_by`, `created_at`, `updated_at`)
VALUES
('Scheduled Maintenance', 'System maintenance is scheduled for Sunday at 11:00 PM ET.', 'info', 'all', NULL, NULL, NOW(), NULL, 1, 100, NULL, NOW(), NOW()),
('Payment Sync Healthy', 'Phase 1 payment confirmations are syncing normally.', 'success', 'admin', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 1, 90, NULL, NOW(), NOW()),
('Deadline Reminder', 'Review submission deadlines for currently active competitions.', 'warning', 'all', NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, 1, 80, NULL, NOW(), NOW());
