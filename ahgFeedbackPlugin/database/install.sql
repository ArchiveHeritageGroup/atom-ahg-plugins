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
  `rgt` INT NOT NULL DEFAULT 1,
  `source_culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`),
  INDEX `idx_feed_type` (`feed_type_id`),
  INDEX `idx_parent` (`parent_id`(50))
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
  `status_id` INT NOT NULL DEFAULT 1030,
  `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, completed',
  PRIMARY KEY (`id`, `culture`),
  INDEX `idx_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_feedback_status.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =============================================================================
-- ahgFeedbackPlugin: add `status` (varchar) column to feedback_i18n
-- =============================================================================
-- Older deployments only have `status_id` (int), but the application code
-- (browseAction, viewAction, editAction) reads/writes `feedback_i18n.status`
-- as a string ('pending' / 'completed').
--
-- install.sql uses CREATE TABLE IF NOT EXISTS, so existing tables never picked
-- up the new column. This migration is idempotent.
-- =============================================================================

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback_i18n' AND COLUMN_NAME = 'status');
SET @s := IF(@c = 0, "ALTER TABLE `feedback_i18n` ADD COLUMN `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, completed'", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
