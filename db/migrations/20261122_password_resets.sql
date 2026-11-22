-- Additive, safe to run more than once. Existing accounts are unchanged.
CREATE TABLE IF NOT EXISTS uie_password_resets (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY uq_reset_token (token_hash),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES uie_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS uie_password_reset_limits (
    bucket_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    expires_at DATETIME NOT NULL,
    KEY ix_reset_limit_expiry (expires_at)
) ENGINE=InnoDB;
