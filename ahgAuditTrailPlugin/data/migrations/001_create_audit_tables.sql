SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(255) NULL,
    `user_email` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `session_id` VARCHAR(128) NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `entity_slug` VARCHAR(255) NULL,
    `entity_title` VARCHAR(500) NULL,
    `module` VARCHAR(100) NULL,
    `action_name` VARCHAR(100) NULL,
    `request_method` VARCHAR(10) NULL,
    `request_uri` VARCHAR(2000) NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `changed_fields` JSON NULL,
    `metadata` JSON NULL,
    `security_classification` VARCHAR(50) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'success',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `culture_id` INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_audit_uuid` (`uuid`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_entity_type` (`entity_type`),
    KEY `idx_audit_entity_id` (`entity_id`),
    KEY `idx_audit_created` (`created_at`),
    KEY `idx_audit_status` (`status`),
    KEY `idx_audit_ip` (`ip_address`),
    KEY `idx_audit_security` (`security_classification`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_user_time` (`user_id`, `created_at`),
    CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_authentication` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `session_id` VARCHAR(128) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'success',
    `failure_reason` VARCHAR(255) NULL,
    `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_auth_uuid` (`uuid`),
    KEY `idx_auth_user` (`user_id`),
    KEY `idx_auth_event` (`event_type`),
    KEY `idx_auth_ip` (`ip_address`),
    KEY `idx_auth_created` (`created_at`),
    CONSTRAINT `fk_audit_auth_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_access` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `access_type` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `entity_slug` VARCHAR(255) NULL,
    `entity_title` VARCHAR(500) NULL,
    `security_classification` VARCHAR(50) NULL,
    `security_clearance_level` INT UNSIGNED NULL,
    `clearance_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `file_path` VARCHAR(1000) NULL,
    `file_name` VARCHAR(255) NULL,
    `file_mime_type` VARCHAR(100) NULL,
    `file_size` BIGINT UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'success',
    `denial_reason` VARCHAR(255) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_access_uuid` (`uuid`),
    KEY `idx_access_user` (`user_id`),
    KEY `idx_access_type` (`access_type`),
    KEY `idx_access_entity` (`entity_type`, `entity_id`),
    KEY `idx_access_security` (`security_classification`),
    KEY `idx_access_created` (`created_at`),
    CONSTRAINT `fk_audit_access_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_retention_policy` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `log_type` VARCHAR(50) NOT NULL,
    `retention_days` INT UNSIGNED NOT NULL DEFAULT 2555,
    `archive_before_delete` TINYINT(1) NOT NULL DEFAULT 1,
    `archive_path` VARCHAR(500) NULL,
    `last_cleanup_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_retention_type` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_retention_policy` (`log_type`, `retention_days`, `archive_before_delete`) VALUES
    ('audit_log', 2555, 1),
    ('audit_authentication', 2555, 1),
    ('audit_access', 2555, 1)
ON DUPLICATE KEY UPDATE `retention_days` = VALUES(`retention_days`);

CREATE TABLE IF NOT EXISTS `audit_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `setting_type` VARCHAR(20) NOT NULL DEFAULT 'string',
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
    ('audit_enabled', '1', 'boolean', 'Enable/disable audit logging'),
    ('audit_views', '0', 'boolean', 'Log view/read actions'),
    ('audit_searches', '0', 'boolean', 'Log search queries'),
    ('audit_api_requests', '1', 'boolean', 'Log API requests'),
    ('audit_sensitive_access', '1', 'boolean', 'Log access to classified content'),
    ('audit_downloads', '1', 'boolean', 'Log file downloads'),
    ('audit_authentication', '1', 'boolean', 'Log authentication events'),
    ('audit_mask_sensitive', '1', 'boolean', 'Mask sensitive data in logs'),
    ('audit_ip_anonymize', '0', 'boolean', 'Anonymize IP addresses')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'Audit Trail tables created successfully' AS status;
