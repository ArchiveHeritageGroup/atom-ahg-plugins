--
-- AHG Preservation Plugin - Database Schema
-- Digital preservation features: checksums, fixity, PREMIS events, format registry
--

-- =============================================
-- CHECKSUM STORAGE
-- Stores cryptographic checksums for digital objects
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_checksum (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    digital_object_id INT NOT NULL,
    algorithm ENUM('md5', 'sha1', 'sha256', 'sha512') NOT NULL DEFAULT 'sha256',
    checksum_value VARCHAR(128) NOT NULL,
    file_size BIGINT UNSIGNED,
    generated_at DATETIME NOT NULL,
    verified_at DATETIME,
    verification_status ENUM('pending', 'valid', 'invalid', 'error') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_digital_object (digital_object_id),
    INDEX idx_algorithm (algorithm),
    INDEX idx_status (verification_status),
    INDEX idx_verified_at (verified_at),
    UNIQUE KEY uk_object_algorithm (digital_object_id, algorithm),

    FOREIGN KEY (digital_object_id) REFERENCES digital_object(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FIXITY CHECK LOG
-- Records all fixity verification runs
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_fixity_check (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    digital_object_id INT NOT NULL,
    checksum_id BIGINT UNSIGNED,
    algorithm ENUM('md5', 'sha1', 'sha256', 'sha512') NOT NULL,
    expected_value VARCHAR(128) NOT NULL,
    actual_value VARCHAR(128),
    status ENUM('pass', 'fail', 'error', 'missing') NOT NULL,
    error_message TEXT,
    checked_at DATETIME NOT NULL,
    checked_by VARCHAR(100) COMMENT 'user or system/cron',
    duration_ms INT UNSIGNED COMMENT 'Check duration in milliseconds',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_digital_object (digital_object_id),
    INDEX idx_status (status),
    INDEX idx_checked_at (checked_at),
    INDEX idx_checksum (checksum_id),

    FOREIGN KEY (digital_object_id) REFERENCES digital_object(id) ON DELETE CASCADE,
    FOREIGN KEY (checksum_id) REFERENCES preservation_checksum(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PREMIS EVENTS
-- Preservation metadata events (PREMIS standard)
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    digital_object_id INT,
    information_object_id INT,
    event_type ENUM(
        'creation', 'capture', 'ingestion', 'validation',
        'fixity_check', 'virus_check', 'format_identification',
        'normalization', 'migration', 'replication',
        'deletion', 'deaccession', 'modification',
        'metadata_modification', 'access', 'dissemination'
    ) NOT NULL,
    event_datetime DATETIME NOT NULL,
    event_detail TEXT,
    event_outcome ENUM('success', 'failure', 'warning', 'unknown') DEFAULT 'unknown',
    event_outcome_detail TEXT,
    linking_agent_type ENUM('user', 'system', 'software', 'organization') DEFAULT 'system',
    linking_agent_value VARCHAR(255),
    linking_object_type VARCHAR(100),
    linking_object_value VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_digital_object (digital_object_id),
    INDEX idx_information_object (information_object_id),
    INDEX idx_event_type (event_type),
    INDEX idx_event_datetime (event_datetime),
    INDEX idx_outcome (event_outcome),

    FOREIGN KEY (digital_object_id) REFERENCES digital_object(id) ON DELETE CASCADE,
    FOREIGN KEY (information_object_id) REFERENCES information_object(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FORMAT REGISTRY
-- Tracks file formats and their preservation risk
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_format (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    puid VARCHAR(50) COMMENT 'PRONOM Unique Identifier',
    mime_type VARCHAR(100) NOT NULL,
    format_name VARCHAR(255) NOT NULL,
    format_version VARCHAR(50),
    extension VARCHAR(20),
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    risk_notes TEXT,
    preservation_action ENUM('none', 'monitor', 'migrate', 'normalize') DEFAULT 'monitor',
    migration_target_id BIGINT UNSIGNED COMMENT 'Target format for migration',
    is_preservation_format TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_puid (puid),
    INDEX idx_mime_type (mime_type),
    INDEX idx_risk_level (risk_level),
    UNIQUE KEY uk_mime_version (mime_type, format_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DIGITAL OBJECT FORMAT IDENTIFICATION
-- Links digital objects to identified formats
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_object_format (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    digital_object_id INT NOT NULL,
    format_id BIGINT UNSIGNED,
    mime_type VARCHAR(100),
    format_name VARCHAR(255),
    format_version VARCHAR(50),
    identification_tool VARCHAR(100) COMMENT 'e.g., DROID, file, finfo',
    identification_date DATETIME NOT NULL,
    confidence ENUM('low', 'medium', 'high', 'certain') DEFAULT 'medium',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_digital_object (digital_object_id),
    INDEX idx_format (format_id),
    INDEX idx_mime_type (mime_type),

    FOREIGN KEY (digital_object_id) REFERENCES digital_object(id) ON DELETE CASCADE,
    FOREIGN KEY (format_id) REFERENCES preservation_format(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PRESERVATION POLICIES
-- Defines preservation rules and schedules
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_policy (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    policy_type ENUM('fixity', 'format', 'retention', 'replication') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    schedule_cron VARCHAR(100) COMMENT 'Cron expression for scheduled runs',
    last_run_at DATETIME,
    next_run_at DATETIME,
    config JSON COMMENT 'Policy-specific configuration',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (policy_type),
    INDEX idx_active (is_active),
    INDEX idx_next_run (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PRESERVATION STATISTICS (for dashboard)
-- =============================================
CREATE TABLE IF NOT EXISTS preservation_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL,
    total_objects INT UNSIGNED DEFAULT 0,
    total_size_bytes BIGINT UNSIGNED DEFAULT 0,
    objects_with_checksum INT UNSIGNED DEFAULT 0,
    fixity_checks_run INT UNSIGNED DEFAULT 0,
    fixity_failures INT UNSIGNED DEFAULT 0,
    formats_at_risk INT UNSIGNED DEFAULT 0,
    events_logged INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA: Common format registry entries
-- =============================================
INSERT IGNORE INTO preservation_format (puid, mime_type, format_name, format_version, extension, risk_level, is_preservation_format, preservation_action) VALUES
-- Images (preservation formats)
('fmt/353', 'image/tiff', 'Tagged Image File Format', '6.0', 'tif', 'low', 1, 'none'),
('fmt/44', 'image/jpeg', 'JPEG File Interchange Format', '1.02', 'jpg', 'low', 0, 'monitor'),
('fmt/11', 'image/png', 'Portable Network Graphics', '1.0', 'png', 'low', 1, 'none'),
('fmt/41', 'image/gif', 'Graphics Interchange Format', '89a', 'gif', 'medium', 0, 'monitor'),
('fmt/645', 'image/webp', 'WebP', '', 'webp', 'medium', 0, 'monitor'),

-- Documents (preservation formats)
('fmt/95', 'application/pdf', 'PDF', '1.4', 'pdf', 'low', 0, 'monitor'),
('fmt/354', 'application/pdf', 'PDF/A-1a', '1a', 'pdf', 'low', 1, 'none'),
('fmt/476', 'application/pdf', 'PDF/A-2b', '2b', 'pdf', 'low', 1, 'none'),

-- Office documents (higher risk)
('fmt/412', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Microsoft Word', '2007+', 'docx', 'medium', 0, 'migrate'),
('fmt/214', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Microsoft Excel', '2007+', 'xlsx', 'medium', 0, 'migrate'),

-- Audio (preservation formats)
('fmt/141', 'audio/x-wav', 'Waveform Audio', '', 'wav', 'low', 1, 'none'),
('fmt/134', 'audio/mpeg', 'MPEG Audio Layer 3', '', 'mp3', 'low', 0, 'monitor'),
('fmt/199', 'audio/flac', 'Free Lossless Audio Codec', '', 'flac', 'low', 1, 'none'),

-- Video
('fmt/199', 'video/mp4', 'MPEG-4 Video', '', 'mp4', 'medium', 0, 'monitor'),
('fmt/569', 'video/x-matroska', 'Matroska Video', '', 'mkv', 'medium', 0, 'monitor'),

-- Plain text (preservation)
('x-fmt/111', 'text/plain', 'Plain Text', '', 'txt', 'low', 1, 'none'),
('fmt/101', 'text/xml', 'XML', '1.0', 'xml', 'low', 1, 'none'),

-- Archives (monitor)
('x-fmt/263', 'application/zip', 'ZIP Archive', '', 'zip', 'low', 0, 'monitor'),
('fmt/289', 'application/x-tar', 'TAR Archive', '', 'tar', 'low', 0, 'monitor');

-- =============================================
-- DEFAULT PRESERVATION POLICIES
-- =============================================
INSERT IGNORE INTO preservation_policy (name, description, policy_type, is_active, schedule_cron, config) VALUES
('Daily Fixity Check', 'Verify checksums for a sample of digital objects daily', 'fixity', 1, '0 2 * * *', '{"sample_percentage": 5, "algorithm": "sha256"}'),
('Weekly Full Fixity', 'Full fixity verification weekly', 'fixity', 0, '0 3 * * 0', '{"sample_percentage": 100, "algorithm": "sha256"}'),
('Format Risk Monitor', 'Monitor objects with at-risk formats', 'format', 1, '0 4 * * 1', '{"risk_levels": ["high", "critical"]}');
