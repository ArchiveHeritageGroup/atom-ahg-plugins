-- ============================================================
-- ahgFeedbackPlugin - Database Schema
-- ============================================================
-- Version: 1.0.0
-- Author: The Archive and Heritage Group
-- ============================================================

-- Feedback table (links to object table)
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` INT NOT NULL,
  `feed_name` VARCHAR(50) DEFAULT NULL,
  `feed_surname` VARCHAR(50) DEFAULT NULL,
  `feed_phone` VARCHAR(50) DEFAULT NULL,
  `feed_email` VARCHAR(50) DEFAULT NULL,
  `feed_relationship` TEXT,
  `parent_id` VARCHAR(50) DEFAULT NULL,
  `feed_type_id` INT DEFAULT NULL,
  `lft` INT NOT NULL DEFAULT 0,
  `rgt` INT NOT NULL DEFAULT 0,
  `source_culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`),
  INDEX `idx_feed_type` (`feed_type_id`),
  INDEX `idx_parent` (`parent_id`(50)),
  CONSTRAINT `feedback_FK_1` FOREIGN KEY (`id`) REFERENCES `object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feedback i18n table (translatable fields)
CREATE TABLE IF NOT EXISTS `feedback_i18n` (
  `id` INT NOT NULL,
  `culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  `name` VARCHAR(1024) DEFAULT NULL,
  `unique_identifier` VARCHAR(1024) DEFAULT NULL,
  `remarks` TEXT,
  `object_id` TEXT,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_id` INT NOT NULL DEFAULT 299,
  PRIMARY KEY (`id`, `culture`),
  INDEX `idx_status` (`status_id`),
  CONSTRAINT `feedback_i18n_FK_1` FOREIGN KEY (`id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Feedback Type Taxonomy
-- ============================================================

-- Create taxonomy if not exists
INSERT IGNORE INTO taxonomy (id, parent_id, source_culture, `usage`)
SELECT COALESCE((SELECT MAX(id) FROM taxonomy), 0) + 1, NULL, 'en', 'feedback_type'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM taxonomy WHERE `usage` = 'feedback_type');

-- Insert taxonomy_i18n
INSERT IGNORE INTO taxonomy_i18n (id, culture, name, note)
SELECT id, 'en', 'Feedback Type', 'Types of feedback submissions'
FROM taxonomy WHERE `usage` = 'feedback_type';
