-- ahgSecurityClearancePlugin: AtoM-native group ACL model (groups + permission matrix).
-- Additive; run on the live archive DB. No core-table changes.
-- acl_permission follows AtoM's QubitAclPermission: object_id NULL = root/class-level;
-- `constants` holds JSON scope (e.g. {"repository":"slug"}); class derived via object.class_name join.

CREATE TABLE IF NOT EXISTS `acl_group` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED NULL,
    `source_culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `serial_number` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acl_group_i18n` (
    `id` BIGINT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL,
    `name` VARCHAR(255),
    `description` TEXT,
    `serial_number` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`, `culture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acl_user_group` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `serial_number` INT NOT NULL DEFAULT 0,
    INDEX `idx_aug_user` (`user_id`),
    INDEX `idx_aug_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acl_permission` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `group_id` BIGINT UNSIGNED NULL,
    `object_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(40) NOT NULL,
    `grant_deny` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=grant, 0=deny',
    `conditional` TEXT NULL,
    `constants` TEXT NULL COMMENT 'JSON scope, e.g. {"repository":"slug"}',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL,
    `serial_number` INT NOT NULL DEFAULT 0,
    INDEX `idx_ap_group` (`group_id`),
    INDEX `idx_ap_user` (`user_id`),
    INDEX `idx_ap_object` (`object_id`),
    INDEX `idx_ap_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
