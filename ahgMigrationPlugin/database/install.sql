-- =============================================================================
-- ahgMigrationPlugin Database Schema
-- Universal Data Migration Tool with Sector-Based Mapping
-- =============================================================================

-- Migration Jobs - Track import sessions
CREATE TABLE IF NOT EXISTS atom_migration_job (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    
    -- Source information
    source_system VARCHAR(100) NOT NULL COMMENT 'vernon, archivesspace, dbtextworks, custom',
    source_format VARCHAR(50) NOT NULL COMMENT 'csv, xml, ead',
    source_file VARCHAR(500),
    source_file_hash VARCHAR(64),
    source_headers JSON COMMENT 'Detected source field names',
    
    -- Destination information
    destination_sector VARCHAR(100) NOT NULL COMMENT 'archives, museum, library, gallery, dam',
    destination_repository_id INT UNSIGNED,
    destination_parent_id INT UNSIGNED,
    
    -- Mapping configuration
    template_id BIGINT UNSIGNED NULL,
    field_mappings JSON NOT NULL COMMENT 'Source field to destination field mappings',
    transformations JSON COMMENT 'Field transformation rules',
    default_values JSON COMMENT 'Default values for unmapped required fields',
    
    -- Output options
    output_mode ENUM('direct', 'export', 'both') DEFAULT 'direct' COMMENT 'Import directly or export CSV',
    export_file VARCHAR(500) COMMENT 'Path to exported CSV if applicable',
    
    -- Import options
    import_options JSON COMMENT 'Match existing, update mode, etc.',
    
    -- Status tracking
    status ENUM('pending', 'mapping', 'validating', 'validated', 'importing', 'exporting', 'completed', 'failed', 'cancelled', 'rollback') DEFAULT 'pending',
    total_records INT UNSIGNED DEFAULT 0,
    processed_records INT UNSIGNED DEFAULT 0,
    imported_records INT UNSIGNED DEFAULT 0,
    updated_records INT UNSIGNED DEFAULT 0,
    skipped_records INT UNSIGNED DEFAULT 0,
    error_count INT UNSIGNED DEFAULT 0,
    validation_errors JSON,
    
    -- Timestamps
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_source_system (source_system),
    INDEX idx_destination_sector (destination_sector),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration Templates - Reusable field mapping configurations
CREATE TABLE IF NOT EXISTS atom_migration_template (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    
    -- Source/Destination pairing
    source_system VARCHAR(100) NOT NULL COMMENT 'vernon, archivesspace, dbtextworks, custom',
    source_format VARCHAR(50) NOT NULL COMMENT 'csv, xml, ead',
    destination_sector VARCHAR(100) NOT NULL COMMENT 'archives, museum, library, gallery, dam',
    
    -- Mapping configuration
    field_mappings JSON NOT NULL COMMENT 'Source to destination field mappings',
    transformations JSON COMMENT 'Field transformation rules',
    hierarchy_config JSON COMMENT 'How to build parent-child relationships',
    default_values JSON COMMENT 'Default values for unmapped fields',
    
    -- Metadata
    is_system TINYINT(1) DEFAULT 0 COMMENT 'Built-in templates cannot be deleted',
    is_enabled TINYINT(1) DEFAULT 1,
    usage_count INT UNSIGNED DEFAULT 0,
    version VARCHAR(20) DEFAULT '1.0.0',
    
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_source_dest (source_system, destination_sector),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration Log - Detailed import tracking for rollback
CREATE TABLE IF NOT EXISTS atom_migration_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    `row_number` INT UNSIGNED,
    source_id VARCHAR(255) COMMENT 'Original system identifier',
    
    -- What was created/updated
    record_type VARCHAR(100) NOT NULL COMMENT 'information_object, actor, term, digital_object, etc.',
    atom_object_id INT UNSIGNED COMMENT 'Created/updated object.id',
    atom_slug VARCHAR(255),
    
    -- Action taken
    action ENUM('created', 'updated', 'skipped', 'error') NOT NULL,
    
    -- Hierarchy tracking
    parent_source_id VARCHAR(255),
    
    -- Data snapshots
    source_data JSON COMMENT 'Original row data',
    mapped_data JSON COMMENT 'Transformed data',
    
    -- Messages
    error_message TEXT,
    warning_message TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_job_id (job_id),
    INDEX idx_source_id (source_id),
    INDEX idx_atom_object (record_type, atom_object_id),
    INDEX idx_action (action),
    FOREIGN KEY (job_id) REFERENCES atom_migration_job(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staged Records - Preview/validation before import
CREATE TABLE IF NOT EXISTS atom_migration_staged (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    `row_number` INT UNSIGNED NOT NULL,
    source_id VARCHAR(255),
    
    -- Record classification
    record_type VARCHAR(100) DEFAULT 'information_object',
    parent_source_id VARCHAR(255),
    hierarchy_level INT UNSIGNED DEFAULT 0,
    sort_order INT UNSIGNED DEFAULT 0,
    
    -- Data
    source_data JSON NOT NULL COMMENT 'Original parsed data',
    mapped_data JSON COMMENT 'After field mapping applied',
    
    -- Validation
    validation_status ENUM('pending', 'valid', 'warning', 'error') DEFAULT 'pending',
    validation_messages JSON,
    
    -- Import tracking
    import_status ENUM('pending', 'imported', 'skipped', 'error') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_job_id (job_id),
    INDEX idx_validation (job_id, validation_status),
    INDEX idx_hierarchy (job_id, hierarchy_level, sort_order),
    FOREIGN KEY (job_id) REFERENCES atom_migration_job(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
