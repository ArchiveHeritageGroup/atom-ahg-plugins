-- ahgAuditTrailPlugin Installation Schema
-- Version: 1.0.1

-- Main audit log table
CREATE TABLE IF NOT EXISTS ahg_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL,
    user_id INT NULL,
    username VARCHAR(255) NULL,
    user_email VARCHAR(255) NULL,
    action VARCHAR(50) NOT NULL COMMENT 'create, update, delete, view, login, logout, download, export',
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    entity_slug VARCHAR(255) NULL,
    entity_title VARCHAR(255) NULL,
    module VARCHAR(100) NULL,
    action_name VARCHAR(100) NULL,
    controller VARCHAR(100) NULL,
    request_method VARCHAR(10) NULL,
    request_uri VARCHAR(500) NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    changed_fields JSON NULL,
    status VARCHAR(50) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    session_id VARCHAR(128) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uuid (uuid),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Authentication events (separate for security reporting)
CREATE TABLE IF NOT EXISTS ahg_audit_authentication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(255) NULL,
    event_type ENUM('login', 'logout', 'failed_login', 'password_change', 'password_reset', 'locked', 'unlocked') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    failure_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_event (event_type),
    INDEX idx_created (created_at),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Access log for sensitive records (security clearance related)
CREATE TABLE IF NOT EXISTS ahg_audit_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    access_type ENUM('view', 'download', 'print', 'export') NOT NULL,
    security_level INT NULL,
    granted BOOLEAN DEFAULT TRUE,
    denial_reason VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit settings
CREATE TABLE IF NOT EXISTS ahg_audit_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO ahg_audit_settings (setting_key, setting_value) VALUES
('audit_enabled', '1'),
('log_views', '0'),
('log_searches', '0'),
('log_downloads', '1'),
('log_exports', '1'),
('log_auth', '1'),
('retention_days', '365')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Retention policy table
CREATE TABLE IF NOT EXISTS ahg_audit_retention_policy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    retention_days INT NOT NULL DEFAULT 365,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default retention policies
INSERT INTO ahg_audit_retention_policy (action_type, retention_days) VALUES
('view', 90),
('search', 30),
('create', 365),
('update', 365),
('delete', 730),
('login', 180),
('download', 365),
('export', 365)
ON DUPLICATE KEY UPDATE action_type = action_type;
