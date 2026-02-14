-- ahgReportBuilderPlugin v2 Migration
-- Issue 148: Report Builder Enhancements
-- All new tables + ALTER statements for existing tables

-- ============================================================
-- NEW TABLES
-- ============================================================

-- 1. Report sections (drag-drop ordered content blocks)
CREATE TABLE IF NOT EXISTS report_section (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    section_type ENUM('narrative','table','chart','summary_card','image_gallery','links','sql_query') NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    content LONGTEXT DEFAULT NULL,
    position INT DEFAULT 0,
    config JSON DEFAULT NULL,
    clearance_level INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_position (report_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Report templates (reusable report structures)
CREATE TABLE IF NOT EXISTS report_template (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'custom',
    scope ENUM('system','institution','user') DEFAULT 'user',
    structure JSON NOT NULL,
    created_by INT DEFAULT NULL,
    repository_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_category (category),
    KEY idx_scope (scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Report links (external URLs + internal cross-references)
CREATE TABLE IF NOT EXISTS report_link (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED DEFAULT NULL,
    link_type ENUM('external','information_object','actor','repository','accession','digital_object') NOT NULL,
    url VARCHAR(2048) DEFAULT NULL,
    title VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    target_id INT DEFAULT NULL,
    target_slug VARCHAR(255) DEFAULT NULL,
    link_category VARCHAR(100) DEFAULT 'reference',
    og_image VARCHAR(2048) DEFAULT NULL,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_section (section_id),
    KEY idx_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Report versions (version history)
CREATE TABLE IF NOT EXISTS report_version (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    version_number INT NOT NULL,
    snapshot JSON NOT NULL,
    change_summary VARCHAR(500) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_version (report_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Report comments (reviewer annotations)
CREATE TABLE IF NOT EXISTS report_comment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED DEFAULT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_by INT DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_section (section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Report attachments (media and documents)
CREATE TABLE IF NOT EXISTS report_attachment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(1024) NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    file_size BIGINT UNSIGNED DEFAULT 0,
    thumbnail_path VARCHAR(1024) DEFAULT NULL,
    digital_object_id INT DEFAULT NULL,
    caption TEXT DEFAULT NULL,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_section (section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Report shares (public sharing with expiry)
CREATE TABLE IF NOT EXISTS report_share (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    share_token VARCHAR(64) NOT NULL UNIQUE,
    shared_by INT NOT NULL,
    expires_at DATETIME DEFAULT NULL,
    access_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    email_recipients TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Saved SQL queries (for raw SQL mode)
CREATE TABLE IF NOT EXISTS report_query (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED DEFAULT NULL,
    section_id BIGINT UNSIGNED DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    query_text TEXT NOT NULL,
    query_type ENUM('visual','raw_sql') DEFAULT 'visual',
    visual_config JSON DEFAULT NULL,
    parameters JSON DEFAULT NULL,
    row_limit INT DEFAULT 1000,
    timeout_seconds INT DEFAULT 30,
    created_by INT NOT NULL,
    is_shared TINYINT(1) DEFAULT 0,
    last_executed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    KEY idx_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ALTER EXISTING TABLES
-- MySQL 8 does NOT support ADD COLUMN IF NOT EXISTS.
-- Use a stored procedure to safely add columns.
-- ============================================================

DELIMITER //
DROP PROCEDURE IF EXISTS _rb_add_column//
CREATE PROCEDURE _rb_add_column(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition VARCHAR(255)
)
BEGIN
    SET @col_exists = (
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    );
    IF @col_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

-- custom_report: Add workflow, template, and data binding fields
CALL _rb_add_column('custom_report', 'status', "ENUM('draft','in_review','approved','published','archived') DEFAULT 'draft'");
CALL _rb_add_column('custom_report', 'template_id', 'BIGINT UNSIGNED DEFAULT NULL');
CALL _rb_add_column('custom_report', 'data_mode', "ENUM('live','snapshot') DEFAULT 'live'");
CALL _rb_add_column('custom_report', 'snapshot_data', 'JSON DEFAULT NULL');
CALL _rb_add_column('custom_report', 'snapshot_at', 'DATETIME DEFAULT NULL');
CALL _rb_add_column('custom_report', 'cover_config', 'JSON DEFAULT NULL');
CALL _rb_add_column('custom_report', 'version', 'INT DEFAULT 1');
CALL _rb_add_column('custom_report', 'workflow_id', 'BIGINT UNSIGNED DEFAULT NULL');

-- report_schedule: Add trigger-based scheduling
CALL _rb_add_column('report_schedule', 'schedule_type', "ENUM('recurring','trigger') DEFAULT 'recurring'");
CALL _rb_add_column('report_schedule', 'trigger_config', 'JSON DEFAULT NULL');

-- report_archive: Add share token for download links
CALL _rb_add_column('report_archive', 'download_token', 'VARCHAR(64) DEFAULT NULL');
CALL _rb_add_column('report_archive', 'download_count', 'INT DEFAULT 0');

-- Cleanup helper procedure
DROP PROCEDURE IF EXISTS _rb_add_column;
