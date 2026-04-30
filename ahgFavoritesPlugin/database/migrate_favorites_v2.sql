-- =============================================================================
-- ahgFavoritesPlugin v1 -> v2 schema migration
-- =============================================================================
-- v1 installs only had user_id / archival_description_id / archival_description
-- / slug / notes / created_at / updated_at on `favorites`.
-- v2 added: object_type, reference_code, folder_id, completed_at,
-- last_viewed_at, plus the favorites_folder and favorites_share tables.
--
-- install.sql uses CREATE TABLE IF NOT EXISTS, so older installs that already
-- had `favorites` never picked up the new columns. This migration is
-- idempotent — re-running is a no-op.
-- =============================================================================

-- favorites.object_type
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'object_type');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `object_type` VARCHAR(50) DEFAULT 'information_object' AFTER `slug`", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites.reference_code
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'reference_code');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `reference_code` VARCHAR(255) DEFAULT NULL AFTER `object_type`", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites.folder_id
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'folder_id');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `folder_id` INT DEFAULT NULL AFTER `reference_code`", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites.completed_at
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'completed_at');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `completed_at` DATETIME DEFAULT NULL AFTER `folder_id`", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites.last_viewed_at
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'last_viewed_at');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `last_viewed_at` DATETIME DEFAULT NULL AFTER `completed_at`", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites.notes (some very-old installs may also be missing this)
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'notes');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `notes` TEXT AFTER `last_viewed_at`", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites.updated_at
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'updated_at');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- index on folder_id
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND INDEX_NAME = 'idx_folder');
SET @s := IF(@c = 0, "ALTER TABLE `favorites` ADD INDEX `idx_folder` (`folder_id`)", 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- favorites_folder + favorites_share — IF NOT EXISTS handles re-runs.
CREATE TABLE IF NOT EXISTS `favorites_folder` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(7) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `visibility` VARCHAR(35) COMMENT 'private, shared, public' DEFAULT 'private',
  `sort_order` INT DEFAULT 0,
  `parent_id` INT DEFAULT NULL,
  `share_token` VARCHAR(64) DEFAULT NULL,
  `share_expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_share_token` (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `favorites_share` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `folder_id` INT NOT NULL,
  `shared_with_user_id` INT DEFAULT NULL,
  `shared_via` VARCHAR(31) COMMENT 'link, email, direct' DEFAULT 'link',
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `accessed_at` DATETIME DEFAULT NULL,
  `access_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_folder` (`folder_id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_shared_with` (`shared_with_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
