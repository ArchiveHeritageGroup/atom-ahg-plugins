-- ============================================================
-- Issue 149 Phase 7: Collaboration Enhancements
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_comment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `entity_type` ENUM('report','report_section','annotation','journal_entry','collection') NOT NULL,
  `entity_id` INT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `content` TEXT NOT NULL,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `resolved_by` INT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_peer_review` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_id` INT NOT NULL,
  `requested_by` INT NOT NULL,
  `reviewer_id` INT NOT NULL,
  `status` ENUM('pending','in_progress','completed','declined') DEFAULT 'pending',
  `feedback` TEXT,
  `rating` INT DEFAULT NULL,
  `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  KEY `idx_report` (`report_id`),
  KEY `idx_reviewer` (`reviewer_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
