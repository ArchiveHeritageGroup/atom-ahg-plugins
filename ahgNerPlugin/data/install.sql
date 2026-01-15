-- ============================================================================
-- ahgNerPlugin Database Tables
-- Version: 1.2.0
-- Last Updated: 2026-01-15
-- 
-- Tables:
--   ahg_ner_extraction - Tracks extraction jobs per information object
--   ahg_ner_entity     - Stores extracted entities with review workflow
--   ahg_ner_settings   - Plugin configuration settings
--   ahg_ner_usage      - API usage tracking and audit
-- ============================================================================

-- Extraction jobs table
CREATE TABLE IF NOT EXISTS ahg_ner_extraction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    backend_used VARCHAR(50) DEFAULT 'local',
    status VARCHAR(50) DEFAULT 'pending',
    entity_count INT DEFAULT 0,
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ner_extraction_object (object_id),
    INDEX idx_ner_extraction_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extracted entities table with review workflow
CREATE TABLE IF NOT EXISTS ahg_ner_entity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    extraction_id BIGINT UNSIGNED NULL,
    object_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_value VARCHAR(500) NOT NULL,
    original_value VARCHAR(500) DEFAULT NULL,
    original_type VARCHAR(50) DEFAULT NULL,
    correction_type ENUM('none', 'value_edit', 'type_change', 'both', 'rejected', 'approved') DEFAULT 'none',
    training_exported TINYINT(1) DEFAULT 0,
    confidence DECIMAL(5,4) DEFAULT 1.0000,
    status VARCHAR(50) DEFAULT 'pending',
    linked_actor_id INT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ner_entity_extraction (extraction_id),
    INDEX idx_ner_entity_object (object_id),
    INDEX idx_ner_entity_status (status),
    INDEX idx_ner_entity_type (entity_type),
    INDEX idx_ner_entity_correction (correction_type),
    INDEX idx_ner_entity_training (training_exported)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugin settings table
CREATE TABLE IF NOT EXISTS ahg_ner_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API usage tracking table
CREATE TABLE IF NOT EXISTS ahg_ner_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    api_key VARCHAR(100) DEFAULT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_size INT DEFAULT 0,
    response_time_ms INT DEFAULT NULL,
    status_code INT DEFAULT 200,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ner_usage_user (user_id),
    INDEX idx_ner_usage_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO ahg_ner_settings (setting_key, setting_value) VALUES
    ('api_url', 'http://192.168.0.112:5004/ai/v1'),
    ('api_key', 'ahg_ai_demo_internal_2026'),
    ('api_timeout', '60'),
    ('auto_link_exact', '0'),
    ('confidence_threshold', '0.85'),
    ('enabled_entity_types', '["PERSON","ORG","GPE","DATE"]'),
    ('summarizer_max_length', '1000'),
    ('summarizer_min_length', '100')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
