-- ahgAuditTrailPlugin Installation Schema
-- Version: 1.0.0

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
    entity_title VARCHAR(255) NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    session_id VARCHAR(128) NULL,
    module VARCHAR(100) NULL,
    controller VARCHAR(100) NULL,
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
    username VARCHAR(255) NULL,
    object_id INT NOT NULL,
    object_title VARCHAR(255) NULL,
    security_level VARCHAR(50) NULL,
    access_type ENUM('view', 'download', 'print', 'export') NOT NULL,
    access_granted TINYINT(1) DEFAULT 1,
    denial_reason VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_object (object_id),
    INDEX idx_security (security_level),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings table
CREATE TABLE IF NOT EXISTS ahg_audit_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Retention policy table
CREATE TABLE IF NOT EXISTS ahg_audit_retention_policy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_type VARCHAR(50) NOT NULL UNIQUE COMMENT 'log, authentication, access',
    retention_days INT DEFAULT 365,
    auto_archive TINYINT(1) DEFAULT 0,
    archive_path VARCHAR(500) NULL,
    last_cleanup DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT IGNORE INTO ahg_audit_settings (setting_key, setting_value, setting_type, description) VALUES
('audit_enabled', '1', 'boolean', 'Enable audit trail logging'),
('audit_views', '0', 'boolean', 'Log view actions (can impact performance)'),
('audit_authentication', '1', 'boolean', 'Log authentication events'),
('audit_downloads', '1', 'boolean', 'Log file downloads'),
('audit_exports', '1', 'boolean', 'Log export actions'),
('audit_searches', '0', 'boolean', 'Log search queries'),
('retention_days', '365', 'integer', 'Days to retain audit logs');

-- Default retention policies
INSERT IGNORE INTO ahg_audit_retention_policy (audit_type, retention_days, auto_archive) VALUES
('log', 365, 0),
('authentication', 730, 0),
('access', 1825, 0);
