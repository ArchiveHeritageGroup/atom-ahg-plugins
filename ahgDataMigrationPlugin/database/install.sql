-- =====================================================
-- ahgDataMigrationPlugin Database Schema
-- =====================================================

-- Saved field mappings
CREATE TABLE IF NOT EXISTS atom_data_mapping (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    target_type VARCHAR(100) NOT NULL COMMENT 'information_object, repository, accession, actor, subject, place, event',
    description TEXT,
    field_mappings JSON NOT NULL COMMENT 'Array of field mapping objects',
    source_template VARCHAR(100) COMMENT 'archivesspace, vernon, dbtextworks, etc.',
    is_default TINYINT(1) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_name_type (name, target_type),
    INDEX idx_target_type (target_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration jobs tracking
CREATE TABLE IF NOT EXISTS atom_migration_job (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    target_type VARCHAR(100) NOT NULL,
    source_file VARCHAR(500),
    source_format VARCHAR(50) COMMENT 'csv, xml, json',
    mapping_id BIGINT UNSIGNED,
    mapping_snapshot JSON COMMENT 'Copy of mapping used',
    import_options JSON COMMENT 'Match field, update mode, etc.',
    status VARCHAR(58) COMMENT 'pending, running, completed, failed, cancelled' DEFAULT 'pending',
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    imported_records INT DEFAULT 0,
    updated_records INT DEFAULT 0,
    skipped_records INT DEFAULT 0,
    error_count INT DEFAULT 0,
    error_log JSON,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_target_type (target_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import log for rollback and audit
CREATE TABLE IF NOT EXISTS `atom_migration_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `row_number` int DEFAULT NULL,
  `source_identifier` varchar(255) DEFAULT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int DEFAULT NULL COMMENT 'AtoM object ID',
  `target_slug` varchar(255) DEFAULT NULL,
  `action` varchar(40) NOT NULL COMMENT 'created, updated, skipped, failed',
  `source_data` json DEFAULT NULL,
  `mapped_data` json DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_action` (`action`),
  CONSTRAINT `atom_migration_log_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `atom_migration_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Validation rules configuration
CREATE TABLE IF NOT EXISTS atom_validation_rule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector_code VARCHAR(50) NOT NULL COMMENT 'archive, museum, library, gallery, dam',
    rule_type VARCHAR(77) COMMENT 'required, type, pattern, enum, range, length, referential, custom' NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    rule_config JSON NOT NULL COMMENT 'Rule parameters: pattern, values, min/max, etc.',
    error_message VARCHAR(500) COMMENT 'Custom error message',
    severity VARCHAR(32) COMMENT 'error, warning, info' DEFAULT 'error',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sector (sector_code),
    INDEX idx_field (field_name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Validation results log
CREATE TABLE IF NOT EXISTS `atom_validation_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned DEFAULT NULL,
  `row_number` int DEFAULT NULL,
  `column_name` varchar(255) DEFAULT NULL,
  `rule_type` varchar(50) DEFAULT NULL,
  `severity` varchar(28) DEFAULT NULL COMMENT 'error, warning, info',
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_job` (`job_id`),
  KEY `idx_severity` (`severity`),
  KEY `idx_row` (`row_number`),
  CONSTRAINT `atom_validation_log_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `atom_migration_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Add columns to atom_data_mapping for sharing profiles
-- Uses procedure to safely add columns (MySQL 8 does not support ADD COLUMN IF NOT EXISTS)
SET @dbname = DATABASE();
SET @tablename = 'atom_data_mapping';

SELECT COUNT(*) INTO @col_exists FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'is_shared';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE atom_data_mapping ADD COLUMN is_shared TINYINT(1) DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'shared_by';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE atom_data_mapping ADD COLUMN shared_by INT UNSIGNED', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'sector_code';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE atom_data_mapping ADD COLUMN sector_code VARCHAR(50)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
