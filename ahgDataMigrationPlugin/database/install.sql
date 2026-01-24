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
