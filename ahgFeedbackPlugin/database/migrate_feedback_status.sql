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
