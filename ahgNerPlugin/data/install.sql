-- ============================================================================
-- ahgNerPlugin - Database Installation Script
-- Version: 1.0.0
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: ahg_ner_extraction
-- Tracks each NER extraction run on a record
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ahg_ner_extraction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'FK to information_object.id',
    backend_used VARCHAR(50) DEFAULT 'local' COMMENT 'local, openai, google',
    status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, completed, failed',
    entity_count INT DEFAULT 0,
    processing_time_ms INT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    extracted_by INT DEFAULT NULL COMMENT 'FK to user.id',
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ner_extraction_object (object_id),
    INDEX idx_ner_extraction_status (status),
    INDEX idx_ner_extraction_date (extracted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: ahg_ner_entity
-- Individual extracted entities with review status
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ahg_ner_entity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    extraction_id BIGINT UNSIGNED NOT NULL COMMENT 'FK to ahg_ner_extraction.id',
    object_id INT NOT NULL COMMENT 'FK to information_object.id',
    entity_type VARCHAR(50) NOT NULL COMMENT 'PERSON, ORG, GPE, DATE',
    entity_value VARCHAR(500) NOT NULL,
    entity_label VARCHAR(100) DEFAULT NULL COMMENT 'Normalized label',
    confidence DECIMAL(5,4) DEFAULT NULL COMMENT '0.0000 to 1.0000',
    context TEXT DEFAULT NULL COMMENT 'Surrounding text for context',
    status ENUM('pending', 'approved', 'rejected', 'linked', 'skipped') DEFAULT 'pending',
    linked_actor_id INT DEFAULT NULL COMMENT 'FK to actor.id when linked',
    linked_term_id INT DEFAULT NULL COMMENT 'FK to term.id for subjects',
    reviewed_by INT DEFAULT NULL COMMENT 'FK to user.id',
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ner_entity_extraction (extraction_id),
    INDEX idx_ner_entity_object (object_id),
    INDEX idx_ner_entity_status (status),
    INDEX idx_ner_entity_type (entity_type),
    INDEX idx_ner_entity_value (entity_value(100)),
    INDEX idx_ner_entity_linked_actor (linked_actor_id),
    CONSTRAINT fk_ner_entity_extraction 
        FOREIGN KEY (extraction_id) REFERENCES ahg_ner_extraction(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: ahg_ner_settings
-- Plugin configuration settings
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ahg_ner_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Default Settings
-- ----------------------------------------------------------------------------
INSERT INTO ahg_ner_settings (setting_key, setting_value, setting_type, description) VALUES
    ('api_url', 'http://192.168.0.112:5002/ner/v1', 'string', 'NER API endpoint URL'),
    ('api_key', 'ner_demo_ahg_internal_2026', 'string', 'API authentication key'),
    ('api_timeout', '30', 'integer', 'API request timeout in seconds'),
    ('auto_link_exact', '0', 'boolean', 'Automatically link exact matches'),
    ('confidence_threshold', '0.85', 'string', 'Minimum confidence for auto-approve'),
    ('enabled_entity_types', '["PERSON","ORG","GPE","DATE"]', 'json', 'Entity types to extract'),
    ('max_text_length', '100000', 'integer', 'Maximum text length to process')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- ----------------------------------------------------------------------------
-- Table: ahg_ner_usage
-- API usage tracking (for future paid API)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ahg_ner_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    api_key VARCHAR(100) DEFAULT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_size INT DEFAULT 0 COMMENT 'Characters processed',
    response_time_ms INT DEFAULT NULL,
    status_code INT DEFAULT 200,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ner_usage_user (user_id),
    INDEX idx_ner_usage_date (created_at),
    INDEX idx_ner_usage_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
