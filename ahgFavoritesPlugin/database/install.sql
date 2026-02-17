-- ============================================================
-- ahgFavoritesPlugin - Database Schema
-- ============================================================
-- Version: 2.0.0
-- Author: The Archive and Heritage Group
-- ============================================================

-- Favorites table (user bookmarks for archival descriptions)
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(50) DEFAULT NULL,
  `archival_description_id` VARCHAR(50) DEFAULT NULL,
  `archival_description` VARCHAR(1024) DEFAULT NULL,
  `slug` VARCHAR(1024) DEFAULT NULL,
  `notes` TEXT,
  `object_type` VARCHAR(50) DEFAULT 'information_object',
  `reference_code` VARCHAR(255) DEFAULT NULL,
  `folder_id` INT DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_description` (`archival_description_id`),
  INDEX `idx_folder` (`folder_id`),
  UNIQUE KEY `unique_user_item` (`user_id`, `archival_description_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites folder table (organise bookmarks into folders)
CREATE TABLE IF NOT EXISTS `favorites_folder` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(7) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `visibility` ENUM('private','shared','public') DEFAULT 'private',
  `sort_order` INT DEFAULT 0,
  `parent_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
