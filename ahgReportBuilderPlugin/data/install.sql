-- ahgReportBuilderPlugin Database Schema
-- Custom Report Builder with drag-drop designer, charts, scheduling, and export

-- Custom report templates (user-created)
CREATE TABLE IF NOT EXISTS custom_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT,
    is_shared TINYINT(1) DEFAULT 0,
    is_public TINYINT(1) DEFAULT 0,
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
    INDEX idx_custom_report_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled report jobs
CREATE TABLE IF NOT EXISTS report_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_report_id BIGINT UNSIGNED NOT NULL,
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
    INDEX idx_report_archive_report (custom_report_id),
    INDEX idx_report_archive_schedule (schedule_id),
    INDEX idx_report_archive_generated (generated_at),
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
