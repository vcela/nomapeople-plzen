-- schema.sql — run by hand via phpMyAdmin against the target MariaDB database.
-- utf8mb4 throughout for full Czech diacritics support.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS registrations (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(190) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  phone         VARCHAR(40)  NULL,
  note          TEXT NULL,
  gdpr_consent  TINYINT(1) NOT NULL DEFAULT 0,
  photo_consent TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_registrations_created_at (created_at),
  KEY idx_registrations_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_admin_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_remember_tokens (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id       INT UNSIGNED NOT NULL,
  selector       CHAR(24) NOT NULL,
  validator_hash CHAR(64) NOT NULL,
  expires_at     DATETIME NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_remember_selector (selector),
  KEY idx_remember_admin_id (admin_id),
  CONSTRAINT fk_remember_admin FOREIGN KEY (admin_id)
    REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(100) NOT NULL,
  setting_value TEXT NOT NULL,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: notification recipient (editable later via admin/settings.php)
INSERT INTO settings (setting_key, setting_value) VALUES
  ('notification_email', 'ondrej.huk@gmail.com')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Seed: one admin user. Password is '123456' (bcrypt hash below, verified working).
-- CHANGE THIS PASSWORD immediately after first login, via /admin/account.php.
INSERT INTO admin_users (email, password_hash) VALUES
  ('ondrej.huk@gmail.com', '$2y$10$hEKAx4px9CqiXMxg/mS0OOw33F1w1sPX4TgtrOnKQPZfymPIipEW6')
ON DUPLICATE KEY UPDATE email = email;
