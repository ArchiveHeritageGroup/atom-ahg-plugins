-- AHG Theme B5 Plugin - Install SQL
-- Tables specific to theme functionality
-- Version: 1.0.0
-- 
-- NOTE: Run atom-framework/database/install.sql FIRST for core tables

-- Level of Description Sector mapping (shared by all sector plugins)
-- Each sector plugin populates its own entries
CREATE TABLE IF NOT EXISTS level_of_description_sector (
    id INT AUTO_INCREMENT PRIMARY KEY,
    term_id INT NOT NULL,
    sector VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_term_sector (term_id, sector),
    INDEX idx_sector (sector),
    INDEX idx_term_id (term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Register theme plugin
INSERT IGNORE INTO atom_plugin (name, is_enabled, load_order, version) VALUES
('arAHGThemeB5Plugin', 1, 5, '1.0.0');

-- TIFF to PDF Merge Job table
CREATE TABLE IF NOT EXISTS tiff_pdf_merge_job (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    information_object_id INT NULL,
    user_id INT NOT NULL,
    job_name VARCHAR(255) NOT NULL,
    status ENUM('pending','queued','processing','completed','failed') DEFAULT 'pending',
    total_files INT DEFAULT 0,
    processed_files INT DEFAULT 0,
    output_filename VARCHAR(255) NULL,
    output_path VARCHAR(1024) NULL,
    output_digital_object_id INT NULL,
    pdf_standard ENUM('pdf','pdfa-1b','pdfa-2b','pdfa-3b') DEFAULT 'pdfa-2b',
    compression_quality INT DEFAULT 85,
    page_size ENUM('auto','a4','letter','legal','a3') DEFAULT 'auto',
    orientation ENUM('auto','portrait','landscape') DEFAULT 'auto',
    dpi INT DEFAULT 300,
    preserve_originals TINYINT(1) DEFAULT 1,
    attach_to_record TINYINT(1) DEFAULT 1,
    error_message TEXT NULL,
    notes TEXT NULL,
    options JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_information_object (information_object_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TIFF to PDF Merge File table
CREATE TABLE IF NOT EXISTS tiff_pdf_merge_file (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    merge_job_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(1024) NOT NULL,
    file_size BIGINT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT 'image/tiff',
    width INT NULL,
    height INT NULL,
    bit_depth INT NULL,
    color_space VARCHAR(50) NULL,
    page_order INT DEFAULT 0,
    status ENUM('uploaded','processing','processed','failed') DEFAULT 'uploaded',
    error_message TEXT NULL,
    checksum_md5 VARCHAR(32) NULL,
    metadata JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_merge_job (merge_job_id),
    CONSTRAINT fk_tiff_merge_job FOREIGN KEY (merge_job_id) REFERENCES tiff_pdf_merge_job(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
