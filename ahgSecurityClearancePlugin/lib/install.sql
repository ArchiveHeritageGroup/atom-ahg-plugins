-- ==========================================================================
-- Security Clearance Plugin - Complete Database Schema
-- ==========================================================================
-- Features:
-- 1. Clearance expiry with renewal workflow
-- 2. Two-factor authentication for classified access
-- 3. Compartmentalised/project-based access
-- 4. Access request workflow with approval
-- 5. Declassification scheduling
-- 6. Comprehensive audit logging
-- 7. Dynamic watermarking tracking
-- ==========================================================================

-- Classification levels (already exists, but ensure complete)
CREATE TABLE IF NOT EXISTS security_classification (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    level TINYINT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT,
    color VARCHAR(20) DEFAULT '#666666',
    icon VARCHAR(50) DEFAULT 'fa-lock',
    requires_justification TINYINT(1) DEFAULT 0,
    requires_approval TINYINT(1) DEFAULT 0,
    requires_2fa TINYINT(1) DEFAULT 0,
    max_session_hours INT DEFAULT NULL,
    watermark_required TINYINT(1) DEFAULT 0,
    download_allowed TINYINT(1) DEFAULT 1,
    print_allowed TINYINT(1) DEFAULT 1,
    copy_allowed TINYINT(1) DEFAULT 1,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User clearances with expiry and renewal tracking
CREATE TABLE IF NOT EXISTS user_security_clearance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    classification_id INT UNSIGNED NOT NULL,
    granted_by INT UNSIGNED,
    granted_date DATE NOT NULL,
    expiry_date DATE,
    renewal_requested_date DATE,
    renewal_status ENUM('none', 'pending', 'approved', 'denied') DEFAULT 'none',
    renewal_notes TEXT,
    vetting_reference VARCHAR(100),
    vetting_date DATE,
    vetting_authority VARCHAR(255),
    two_factor_verified TINYINT(1) DEFAULT 0,
    two_factor_verified_at TIMESTAMP NULL,
    notes TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_classification (classification_id),
    INDEX idx_expiry (expiry_date, active),
    INDEX idx_renewal (renewal_status),
    FOREIGN KEY (classification_id) REFERENCES security_classification(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object security classifications with declassification scheduling
CREATE TABLE IF NOT EXISTS object_security_classification (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT UNSIGNED NOT NULL,
    classification_id INT UNSIGNED NOT NULL,
    classified_by INT UNSIGNED,
    classified_date DATE NOT NULL,
    review_date DATE,
    declassify_date DATE,
    declassify_to_id INT UNSIGNED,
    reason TEXT,
    handling_instructions TEXT,
    caveats VARCHAR(500),
    inherit_to_children TINYINT(1) DEFAULT 1,
    auto_declassify TINYINT(1) DEFAULT 0,
    declassify_event VARCHAR(255),
    retention_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_object (object_id),
    INDEX idx_classification (classification_id),
    INDEX idx_review (review_date),
    INDEX idx_declassify (declassify_date, auto_declassify),
    FOREIGN KEY (classification_id) REFERENCES security_classification(id) ON DELETE RESTRICT,
    FOREIGN KEY (declassify_to_id) REFERENCES security_classification(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compartments/Projects for compartmentalised access
CREATE TABLE IF NOT EXISTS security_compartment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    min_clearance_id INT UNSIGNED NOT NULL,
    requires_need_to_know TINYINT(1) DEFAULT 1,
    requires_briefing TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active),
    FOREIGN KEY (min_clearance_id) REFERENCES security_classification(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User compartment access (beyond hierarchy)
CREATE TABLE IF NOT EXISTS user_compartment_access (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    compartment_id INT UNSIGNED NOT NULL,
    granted_by INT UNSIGNED NOT NULL,
    granted_date DATE NOT NULL,
    expiry_date DATE,
    briefing_date DATE,
    briefing_reference VARCHAR(100),
    notes TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_compartment (user_id, compartment_id),
    INDEX idx_compartment (compartment_id),
    INDEX idx_expiry (expiry_date, active),
    FOREIGN KEY (compartment_id) REFERENCES security_compartment(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object compartment assignments
CREATE TABLE IF NOT EXISTS object_compartment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT UNSIGNED NOT NULL,
    compartment_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED,
    assigned_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_object_compartment (object_id, compartment_id),
    INDEX idx_compartment (compartment_id),
    FOREIGN KEY (compartment_id) REFERENCES security_compartment(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Access request workflow
CREATE TABLE IF NOT EXISTS security_access_request (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    object_id INT UNSIGNED,
    classification_id INT UNSIGNED,
    compartment_id INT UNSIGNED,
    request_type ENUM('view', 'download', 'print', 'clearance_upgrade', 'compartment_access', 'renewal') NOT NULL,
    justification TEXT NOT NULL,
    duration_hours INT,
    priority ENUM('normal', 'urgent', 'immediate') DEFAULT 'normal',
    status ENUM('pending', 'approved', 'denied', 'expired', 'cancelled') DEFAULT 'pending',
    reviewed_by INT UNSIGNED,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    access_granted_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_object (object_id),
    INDEX idx_status (status),
    INDEX idx_priority_status (priority, status, created_at),
    FOREIGN KEY (classification_id) REFERENCES security_classification(id) ON DELETE SET NULL,
    FOREIGN KEY (compartment_id) REFERENCES security_compartment(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comprehensive access audit log
CREATE TABLE IF NOT EXISTS security_access_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    object_id INT UNSIGNED,
    classification_id INT UNSIGNED,
    compartment_id INT UNSIGNED,
    action ENUM('view', 'download', 'print', 'export', 'classify', 'declassify', 'access_request', 'access_granted', 'access_denied', 'login', 'logout', '2fa_verified') NOT NULL,
    access_granted TINYINT(1) NOT NULL,
    denial_reason VARCHAR(255),
    justification TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    session_id VARCHAR(100),
    geo_location VARCHAR(255),
    device_fingerprint VARCHAR(255),
    risk_score DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action, created_at),
    INDEX idx_object (object_id, created_at),
    INDEX idx_granted (access_granted, created_at),
    INDEX idx_date (created_at),
    FOREIGN KEY (classification_id) REFERENCES security_classification(id) ON DELETE SET NULL,
    FOREIGN KEY (compartment_id) REFERENCES security_compartment(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clearance history for audit trail
CREATE TABLE IF NOT EXISTS security_clearance_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    previous_classification_id INT UNSIGNED,
    new_classification_id INT UNSIGNED,
    action ENUM('granted', 'upgraded', 'downgraded', 'revoked', 'renewed', 'expired', '2fa_enabled', '2fa_disabled') NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, created_at),
    FOREIGN KEY (previous_classification_id) REFERENCES security_classification(id) ON DELETE SET NULL,
    FOREIGN KEY (new_classification_id) REFERENCES security_classification(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Watermark tracking for downloads
CREATE TABLE IF NOT EXISTS security_watermark_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    object_id INT UNSIGNED NOT NULL,
    digital_object_id INT UNSIGNED,
    watermark_type ENUM('visible', 'invisible', 'both') NOT NULL DEFAULT 'visible',
    watermark_text VARCHAR(500) NOT NULL,
    watermark_code VARCHAR(100) NOT NULL,
    file_hash VARCHAR(64),
    file_name VARCHAR(255),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, created_at),
    INDEX idx_object (object_id),
    INDEX idx_code (watermark_code),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Two-factor authentication sessions
CREATE TABLE IF NOT EXISTS security_2fa_session (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    device_fingerprint VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_expires (expires_at),
    UNIQUE KEY unique_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Declassification schedule (for batch processing)
CREATE TABLE IF NOT EXISTS security_declassification_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    from_classification_id INT UNSIGNED NOT NULL,
    to_classification_id INT UNSIGNED,
    trigger_type ENUM('date', 'event', 'retention') NOT NULL DEFAULT 'date',
    trigger_event VARCHAR(255),
    processed TINYINT(1) DEFAULT 0,
    processed_at TIMESTAMP NULL,
    processed_by INT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scheduled (scheduled_date, processed),
    INDEX idx_object (object_id),
    FOREIGN KEY (from_classification_id) REFERENCES security_classification(id) ON DELETE RESTRICT,
    FOREIGN KEY (to_classification_id) REFERENCES security_classification(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default classification levels
INSERT INTO security_classification (code, name, level, color, icon, requires_justification, requires_approval, requires_2fa, watermark_required, download_allowed, print_allowed, copy_allowed, active) VALUES
('PUBLIC', 'Public', 0, '#28a745', 'fa-globe', 0, 0, 0, 0, 1, 1, 1, 1),
('INTERNAL', 'Internal Use', 1, '#17a2b8', 'fa-building', 0, 0, 0, 0, 1, 1, 1, 1),
('RESTRICTED', 'Restricted', 2, '#ffc107', 'fa-exclamation-triangle', 1, 0, 0, 0, 1, 1, 0, 1),
('CONFIDENTIAL', 'Confidential', 3, '#fd7e14', 'fa-shield-alt', 1, 0, 0, 1, 1, 0, 0, 1),
('SECRET', 'Secret', 4, '#dc3545', 'fa-lock', 1, 1, 1, 1, 0, 0, 0, 1),
('TOP_SECRET', 'Top Secret', 5, '#6f42c1', 'fa-user-secret', 1, 1, 1, 1, 0, 0, 0, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Sample compartments
INSERT INTO security_compartment (code, name, description, min_clearance_id, requires_need_to_know, requires_briefing) VALUES
('ORCON', 'Originator Controlled', 'Dissemination controlled by originating agency', 3, 1, 0),
('NOFORN', 'No Foreign Nationals', 'Not releasable to foreign nationals', 4, 1, 0),
('SPECIAL_PROJECT', 'Special Projects', 'Access to special project materials', 4, 1, 1),
('HISTORICAL', 'Historical Records', 'Historical sensitive records access', 2, 1, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);
