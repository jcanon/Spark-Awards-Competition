-- Auth security hardening
-- Run in this order in your application database.

-- 1) Password reset selector/verifier support (hashed reset tokens at rest)
ALTER TABLE comp_password_resets
    ADD COLUMN selector VARCHAR(32) NULL AFTER token,
    ADD COLUMN token_hash VARCHAR(255) NULL AFTER selector;

ALTER TABLE comp_password_resets
    ADD INDEX idx_comp_password_resets_selector (selector),
    ADD INDEX idx_comp_password_resets_user_used (user_id, used_at),
    ADD INDEX idx_comp_password_resets_expires (expires_at);

-- Optional cleanup after all legacy reset links have expired:
-- ALTER TABLE comp_password_resets DROP COLUMN token;

