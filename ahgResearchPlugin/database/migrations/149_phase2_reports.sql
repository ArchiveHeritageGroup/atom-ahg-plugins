-- ============================================================
-- Issue 149 Phase 2: Research Reports + Templates
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_report` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `researcher_id` INT NOT NULL,
  `project_id` INT DEFAULT NULL,
  `title` VARCHAR(500) NOT NULL,
  `template_type` ENUM('research_summary','genealogical','historical',
                       'source_analysis','finding_aid','custom') DEFAULT 'custom',
  `description` TEXT,
  `status` ENUM('draft','in_progress','review','completed','archived') DEFAULT 'draft',
  `metadata` JSON,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_report_section` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_id` INT NOT NULL,
  `section_type` ENUM('title_page','toc','heading','text','bibliography',
                      'collection_list','annotation_list','timeline','custom') DEFAULT 'text',
  `title` VARCHAR(500),
  `content` TEXT,
  `content_format` ENUM('text','html') DEFAULT 'html',
  `bibliography_id` INT DEFAULT NULL,
  `collection_id` INT DEFAULT NULL,
  `settings` JSON,
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_report` (`report_id`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_report_template` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `sections_config` JSON NOT NULL,
  `is_system` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed system templates
INSERT INTO `research_report_template` (`name`, `code`, `description`, `sections_config`, `is_system`) VALUES
('Research Summary', 'research_summary', 'General research summary report',
 '["title_page","toc","heading:Introduction","text:Background","text:Methodology","text:Findings","text:Conclusion","bibliography"]', 1),
('Genealogical Report', 'genealogical', 'Family history research report',
 '["title_page","toc","heading:Family Overview","text:Origins","text:Family Timeline","text:Notable Members","collection_list","bibliography"]', 1),
('Historical Analysis', 'historical', 'Historical research analysis',
 '["title_page","toc","heading:Historical Context","text:Primary Sources","text:Analysis","text:Interpretation","annotation_list","bibliography"]', 1),
('Source Analysis', 'source_analysis', 'Archival source analysis report',
 '["title_page","toc","heading:Source Description","text:Provenance","text:Content Analysis","text:Reliability Assessment","annotation_list","bibliography"]', 1),
('Finding Aid', 'finding_aid', 'Collection finding aid',
 '["title_page","toc","heading:Collection Overview","text:Administrative History","text:Scope and Content","collection_list","text:Access Conditions"]', 1),
('Custom Report', 'custom', 'Blank report with no predefined sections',
 '["title_page","text:Content"]', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
