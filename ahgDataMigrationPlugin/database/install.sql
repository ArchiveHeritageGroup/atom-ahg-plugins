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
    status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
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
CREATE TABLE IF NOT EXISTS atom_migration_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    row_number INT,
    source_identifier VARCHAR(255),
    target_type VARCHAR(100),
    target_id INT COMMENT 'AtoM object ID',
    target_slug VARCHAR(255),
    action ENUM('created', 'updated', 'skipped', 'failed') NOT NULL,
    source_data JSON,
    mapped_data JSON,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_job_id (job_id),
    INDEX idx_action (action),
    FOREIGN KEY (job_id) REFERENCES atom_migration_job(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Validation rules configuration
CREATE TABLE IF NOT EXISTS atom_validation_rule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector_code VARCHAR(50) NOT NULL COMMENT 'archive, museum, library, gallery, dam',
    rule_type ENUM('required', 'type', 'pattern', 'enum', 'range', 'length', 'referential', 'custom') NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    rule_config JSON NOT NULL COMMENT 'Rule parameters: pattern, values, min/max, etc.',
    error_message VARCHAR(500) COMMENT 'Custom error message',
    severity ENUM('error', 'warning', 'info') DEFAULT 'error',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sector (sector_code),
    INDEX idx_field (field_name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Validation results log
CREATE TABLE IF NOT EXISTS atom_validation_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED,
    row_number INT,
    column_name VARCHAR(255),
    rule_type VARCHAR(50),
    severity ENUM('error', 'warning', 'info'),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_job (job_id),
    INDEX idx_severity (severity),
    INDEX idx_row (row_number),
    FOREIGN KEY (job_id) REFERENCES atom_migration_job(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns to atom_data_mapping for sharing profiles
ALTER TABLE atom_data_mapping
ADD COLUMN IF NOT EXISTS is_shared TINYINT(1) DEFAULT 0 COMMENT 'Whether profile is shared with other users',
ADD COLUMN IF NOT EXISTS shared_by INT UNSIGNED COMMENT 'User ID who shared the profile',
ADD COLUMN IF NOT EXISTS sector_code VARCHAR(50) COMMENT 'Sector this mapping is designed for';
