-- ahgReportBuilderPlugin Database Schema
-- Enterprise Report Builder v2.0: rich text (Quill.js), Word/PDF/XLSX/CSV export,
-- sections, templates, workflow, SQL queries, sharing, scheduling
-- Issue 148: Report Builder Enhancements

-- ============================================================
-- CORE TABLES (v1)
-- ============================================================

-- Custom reports (user-created)
CREATE TABLE IF NOT EXISTS custom_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT,
    is_shared TINYINT(1) DEFAULT 0,
    is_public TINYINT(1) DEFAULT 0,
    status ENUM('draft','in_review','approved','published','archived') DEFAULT 'draft',
    template_id BIGINT UNSIGNED DEFAULT NULL,
    data_mode ENUM('live','snapshot') DEFAULT 'live',
    snapshot_data JSON DEFAULT NULL,
    snapshot_at DATETIME DEFAULT NULL,
    cover_config JSON DEFAULT NULL,
    version INT DEFAULT 1,
    workflow_id BIGINT UNSIGNED DEFAULT NULL,
    layout JSON NOT NULL,
    data_source VARCHAR(100) NOT NULL,
    columns JSON NOT NULL,
    filters JSON,
    charts JSON,
    sort_config JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_custom_report_user (user_id),
    INDEX idx_custom_report_shared (is_shared),
    INDEX idx_custom_report_public (is_public),
    INDEX idx_custom_report_status (status),
    INDEX idx_custom_report_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled report jobs
CREATE TABLE IF NOT EXISTS report_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_report_id BIGINT UNSIGNED NOT NULL,
    schedule_type ENUM('recurring','trigger') DEFAULT 'recurring',
    trigger_config JSON DEFAULT NULL,
    frequency ENUM('daily','weekly','monthly','quarterly') NOT NULL,
    day_of_week TINYINT,
    day_of_month TINYINT,
    time_of_day TIME DEFAULT '08:00:00',
    output_format ENUM('pdf','xlsx','csv') DEFAULT 'pdf',
    email_recipients TEXT,
    last_run DATETIME,
    next_run DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (custom_report_id) REFERENCES custom_report(id) ON DELETE CASCADE,
    INDEX idx_report_schedule_next_run (next_run),
    INDEX idx_report_schedule_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated report archive
CREATE TABLE IF NOT EXISTS report_archive (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_report_id BIGINT UNSIGNED,
    schedule_id BIGINT UNSIGNED,
    file_path VARCHAR(500) NOT NULL,
    file_format VARCHAR(10) NOT NULL,
    file_size INT UNSIGNED,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    parameters JSON,
    download_token VARCHAR(64) DEFAULT NULL,
    download_count INT DEFAULT 0,
    INDEX idx_report_archive_report (custom_report_id),
    INDEX idx_report_archive_schedule (schedule_id),
    INDEX idx_report_archive_generated (generated_at),
    INDEX idx_report_archive_download_token (download_token),
    FOREIGN KEY (custom_report_id) REFERENCES custom_report(id) ON DELETE SET NULL,
    FOREIGN KEY (schedule_id) REFERENCES report_schedule(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard widgets
CREATE TABLE IF NOT EXISTS dashboard_widget (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    custom_report_id BIGINT UNSIGNED,
    widget_type ENUM('table','chart','stat','count') NOT NULL,
    title VARCHAR(255),
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    width INT DEFAULT 4,
    height INT DEFAULT 2,
    config JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (custom_report_id) REFERENCES custom_report(id) ON DELETE CASCADE,
    INDEX idx_dashboard_widget_user (user_id),
    INDEX idx_dashboard_widget_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ENHANCED TABLES (v2 - Issue 148)
-- ============================================================

-- Report sections (drag-drop ordered content blocks)
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
    KEY idx_report_section_report (report_id),
    KEY idx_report_section_position (report_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report templates (reusable report structures)
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
    KEY idx_report_template_category (category),
    KEY idx_report_template_scope (scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report links (external URLs + internal cross-references)
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
    KEY idx_report_link_report (report_id),
    KEY idx_report_link_section (section_id),
    KEY idx_report_link_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report versions (version history)
CREATE TABLE IF NOT EXISTS report_version (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    version_number INT NOT NULL,
    snapshot JSON NOT NULL,
    change_summary VARCHAR(500) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report_version_report (report_id),
    KEY idx_report_version_number (report_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report comments (reviewer annotations)
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
    KEY idx_report_comment_report (report_id),
    KEY idx_report_comment_section (section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report attachments (media and documents)
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
    KEY idx_report_attachment_report (report_id),
    KEY idx_report_attachment_section (section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report shares (public sharing with expiry)
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
    KEY idx_report_share_report (report_id),
    KEY idx_report_share_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved SQL queries (for raw SQL mode)
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
    KEY idx_report_query_report (report_id),
    KEY idx_report_query_creator (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
