-- heratio#146 — front-of-house space allocation (PSIS port migration, 2026-05-23).
-- Run once against existing installs. Idempotent CREATE TABLE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS `ahg_exhibition_space` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `space_type` VARCHAR(20) NOT NULL DEFAULT 'gallery'
      COMMENT 'one of: gallery, hall, display_case, plinth, vitrine',
    `building` VARCHAR(255) NULL,
    `floor` VARCHAR(50) NULL,
    `capacity_value` DECIMAL(12,2) NULL,
    `capacity_unit` VARCHAR(20) NOT NULL DEFAULT 'linear_wall_meters'
      COMMENT 'one of: linear_wall_meters, display_cases, plinths, square_meters',
    `lighting_lux_target` DECIMAL(8,2) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `uq_exh_space_slug` (`slug`),
    INDEX `ix_exh_space_name` (`name`),
    INDEX `ix_exh_space_type` (`space_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ahg_exhibition_placement` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `information_object_id` INT NOT NULL,
    `exhibition_space_id` BIGINT UNSIGNED NOT NULL,
    `exhibition_id` INT NULL,
    `size_units_used` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `starts_at` DATE NULL,
    `ends_at` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `ix_exh_placement_space` (`exhibition_space_id`),
    INDEX `ix_exh_placement_io` (`information_object_id`),
    INDEX `ix_exh_placement_dates` (`starts_at`, `ends_at`),
    CONSTRAINT `fk_exh_io` FOREIGN KEY (`information_object_id`) REFERENCES `information_object`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exh_sp` FOREIGN KEY (`exhibition_space_id`) REFERENCES `ahg_exhibition_space`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
