-- Run this once on an existing ShineRoster database; does not remove data.
USE shineroster;
CREATE TABLE IF NOT EXISTS auth_limits (
 account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_started DATETIME NOT NULL,
 KEY idx_auth_window (window_started)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS contact_messages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(20) NOT NULL DEFAULT '',
 subject VARCHAR(30) NOT NULL,
 message TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
