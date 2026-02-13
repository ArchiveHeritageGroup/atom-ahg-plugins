-- ============================================================
-- Issue 149 Phase 6: Inter-Institutional Sharing
-- ============================================================

CREATE TABLE IF NOT EXISTS `research_institution` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(500) NOT NULL,
  `code` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `url` VARCHAR(1000) DEFAULT NULL,
  `contact_name` VARCHAR(255) DEFAULT NULL,
  `contact_email` VARCHAR(255) DEFAULT NULL,
  `logo_path` VARCHAR(500) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_institutional_share` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `institution_id` INT DEFAULT NULL,
  `share_token` VARCHAR(64) NOT NULL UNIQUE,
  `share_type` ENUM('view','contribute','full') DEFAULT 'view',
  `shared_by` INT NOT NULL,
  `accepted_by` INT DEFAULT NULL,
  `status` ENUM('pending','active','revoked','expired') DEFAULT 'pending',
  `message` TEXT,
  `permissions` JSON,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_project` (`project_id`),
  KEY `idx_institution` (`institution_id`),
  KEY `idx_token` (`share_token`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_external_collaborator` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `share_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `institution` VARCHAR(500) DEFAULT NULL,
  `orcid_id` VARCHAR(50) DEFAULT NULL,
  `access_token` VARCHAR(64) NOT NULL UNIQUE,
  `role` ENUM('viewer','contributor') DEFAULT 'viewer',
  `last_accessed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_share` (`share_id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link researcher to institution
ALTER TABLE `research_researcher`
  ADD COLUMN `institution_id` INT DEFAULT NULL AFTER `institution`;
