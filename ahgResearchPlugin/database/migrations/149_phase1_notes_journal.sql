-- ============================================================
-- Issue 149 Phase 1: Rich Text Notes + Journal
-- Enhances annotations for rich text, adds research journal
-- ============================================================

-- Enhance annotations for rich text and visibility
ALTER TABLE `research_annotation`
  ADD COLUMN `visibility` ENUM('private','shared','public') DEFAULT 'private' AFTER `is_private`,
  ADD COLUMN `content_format` ENUM('text','html') DEFAULT 'text' AFTER `content`;

ALTER TABLE `research_annotation`
  ADD FULLTEXT INDEX `idx_annotation_fulltext` (`title`, `content`);

-- New journal table
CREATE TABLE IF NOT EXISTS `research_journal_entry` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `project_id` INT DEFAULT NULL,
  `entry_date` DATE NOT NULL,
  `title` VARCHAR(500),
  `content` TEXT NOT NULL,
  `content_format` ENUM('text','html') DEFAULT 'html',
  `entry_type` ENUM('manual','auto_booking','auto_material','auto_annotation',
                    'auto_search','auto_collection','reflection','milestone') DEFAULT 'manual',
  `time_spent_minutes` INT DEFAULT NULL,
  `tags` VARCHAR(500) DEFAULT NULL,
  `is_private` TINYINT(1) DEFAULT 1,
  `related_entity_type` VARCHAR(50) DEFAULT NULL,
  `related_entity_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_date` (`entry_date`),
  FULLTEXT INDEX `idx_journal_fulltext` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
