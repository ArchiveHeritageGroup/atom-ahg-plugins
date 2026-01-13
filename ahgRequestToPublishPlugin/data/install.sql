-- ============================================================
-- ahgRequestToPublishPlugin - Database Schema
-- ============================================================
-- Version: 1.0.0
-- Author: The Archive and Heritage Group
-- ============================================================

-- Request to Publish table (links to object table)
CREATE TABLE IF NOT EXISTS `request_to_publish` (
  `id` INT NOT NULL,
  `parent_id` VARCHAR(50) DEFAULT NULL,
  `rtp_type_id` INT DEFAULT NULL,
  `lft` INT NOT NULL DEFAULT 0,
  `rgt` INT NOT NULL DEFAULT 0,
  `source_culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`),
  INDEX `idx_rtp_type` (`rtp_type_id`),
  INDEX `idx_parent` (`parent_id`(50)),
  CONSTRAINT `requesttopublish_FK_1` FOREIGN KEY (`id`) REFERENCES `object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Request to Publish i18n table (translatable fields)
CREATE TABLE IF NOT EXISTS `request_to_publish_i18n` (
  `id` INT NOT NULL,
  `culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  `unique_identifier` VARCHAR(1024) DEFAULT NULL,
  `rtp_name` VARCHAR(50) DEFAULT NULL,
  `rtp_surname` VARCHAR(50) DEFAULT NULL,
  `rtp_phone` VARCHAR(50) DEFAULT NULL,
  `rtp_email` VARCHAR(50) DEFAULT NULL,
  `rtp_institution` VARCHAR(200) DEFAULT NULL,
  `rtp_motivation` TEXT,
  `rtp_planned_use` TEXT,
  `rtp_need_image_by` DATETIME DEFAULT NULL,
  `rtp_admin_notes` TEXT,
  `object_id` VARCHAR(50) DEFAULT NULL,
  `status_id` INT NOT NULL DEFAULT 299,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `culture`),
  INDEX `idx_status` (`status_id`),
  INDEX `idx_object` (`object_id`(50)),
  CONSTRAINT `requesttopublish_i18n_FK_1` FOREIGN KEY (`id`) REFERENCES `request_to_publish` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Request to Publish Type Taxonomy
-- ============================================================

-- Create taxonomy if not exists
INSERT IGNORE INTO taxonomy (id, parent_id, source_culture, `usage`)
SELECT COALESCE((SELECT MAX(id) FROM taxonomy), 0) + 1, NULL, 'en', 'request_to_publish_type'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM taxonomy WHERE `usage` = 'request_to_publish_type');

-- Insert taxonomy_i18n
INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note)
SELECT id, 'en', 'Request to Publish Type', 'Types of publication requests'
FROM taxonomy WHERE `usage` = 'request_to_publish_type';
