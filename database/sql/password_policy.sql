-- Password policy hardening (6-month expiration + password history)
-- Run once in the application database.

ALTER TABLE comp_users
    ADD COLUMN password_changed_at DATETIME NULL AFTER password;

CREATE TABLE comp_user_password_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(36) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(20) NOT NULL DEFAULT 'unknown',
    PRIMARY KEY (id),
    INDEX idx_password_history_user_changed (user_id, changed_at),
    INDEX idx_password_history_user (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Backfill password_changed_at for existing users
UPDATE comp_users
SET password_changed_at = COALESCE(last_updated, account_created, NOW())
WHERE password_changed_at IS NULL;

-- Seed history from current password hash
INSERT INTO comp_user_password_history (user_id, password_hash, changed_at, source)
SELECT u.user_id,
       u.password,
       COALESCE(u.password_changed_at, u.last_updated, u.account_created, NOW()),
       'bootstrap'
FROM comp_users u
WHERE u.password IS NOT NULL
  AND u.password <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM comp_user_password_history h
      WHERE h.user_id = u.user_id
        AND h.password_hash = u.password
  );

-- Optional: add FK after confirming parent table/column compatibility.
-- If this fails, comp_users.user_id definition (type/charset/collation) does not match.
-- ALTER TABLE comp_user_password_history
--   ADD CONSTRAINT fk_password_history_user
--   FOREIGN KEY (user_id) REFERENCES comp_users (user_id)
--   ON DELETE CASCADE;
