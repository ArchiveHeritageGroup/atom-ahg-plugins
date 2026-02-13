-- ============================================================
-- Issue 149 Phase 4: Notification Center
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_notification` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `type` ENUM('alert','invitation','comment','reply','system','reminder','collaboration') NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `message` TEXT,
  `link` VARCHAR(1000) DEFAULT NULL,
  `related_entity_type` VARCHAR(50) DEFAULT NULL,
  `related_entity_id` INT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_notification_preference` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `notification_type` VARCHAR(50) NOT NULL,
  `email_enabled` TINYINT(1) DEFAULT 1,
  `in_app_enabled` TINYINT(1) DEFAULT 1,
  `digest_frequency` ENUM('immediate','daily','weekly','none') DEFAULT 'immediate',
  UNIQUE KEY `uk_researcher_type` (`researcher_id`, `notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
