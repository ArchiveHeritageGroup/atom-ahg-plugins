-- ============================================================
-- ahgFavoritesPlugin - Database Schema
-- ============================================================
-- Version: 1.0.0
-- Author: The Archive and Heritage Group
-- ============================================================

-- Favorites table (user bookmarks for archival descriptions)
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(50) DEFAULT NULL,
  `archival_description_id` VARCHAR(50) DEFAULT NULL,
  `archival_description` VARCHAR(1024) DEFAULT NULL,
  `slug` VARCHAR(1024) DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_description` (`archival_description_id`),
  UNIQUE KEY `unique_user_item` (`user_id`, `archival_description_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
