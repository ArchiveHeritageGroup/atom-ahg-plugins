-- ahgRuntimePlugin schema - runtime tables ONLY.
-- Every other table belongs to the plugin that uses it.

-- ---------------------------------------------------------------------------
-- Runtime infrastructure tables.
--
-- These belong to the framework itself rather than to any one plugin, and the
-- framework's own code reads them on ordinary requests - AhgSettingsService and
-- PluginLoader both do. They are kept here, separate from the full
-- database/install.sql, because that file creates all 273 tables in the estate
-- regardless of what is installed (issue #269) and must not be shipped whole.
--
-- bin/build-runtime-plugin concatenates this with encryption_tables.sql to make
-- ahgRuntimePlugin's install.sql. Without ahg_settings the runtime loads and the
-- site stays up, but every settings read fails with "Table 'ahg_settings' doesn't
-- exist" - logged, swallowed, and easy to miss because nothing 500s.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ahg_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(49) COMMENT 'string, integer, boolean, json, float' DEFAULT 'string',
    setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
    description VARCHAR(500),
    is_sensitive TINYINT(1) DEFAULT 0,
    updated_by INT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_setting_group (setting_group),
    FOREIGN KEY (updated_by) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encryption support tables for AtoM Heratio
-- Issue 145: Encryption Layers

CREATE TABLE IF NOT EXISTS `ahg_encrypted_fields` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `table_name` VARCHAR(100) NOT NULL,
    `column_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `is_encrypted` TINYINT(1) DEFAULT 0,
    `encrypted_at` DATETIME NULL,
    `algorithm` VARCHAR(50) DEFAULT 'aes-256-gcm',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_table_column` (`table_name`, `column_name`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ahg_encryption_audit` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(45) COMMENT 'encrypt, decrypt, rotate, migrate' NOT NULL,
    `target_type` VARCHAR(23) COMMENT 'file, field' NOT NULL,
    `target_id` VARCHAR(255) DEFAULT NULL,
    `target_table` VARCHAR(100) DEFAULT NULL,
    `target_column` VARCHAR(100) DEFAULT NULL,
    `user_id` INT DEFAULT NULL,
    `status` VARCHAR(28) COMMENT 'success, failure' DEFAULT 'success',
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_action` (`action`),
    KEY `idx_target` (`target_type`, `target_id`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
