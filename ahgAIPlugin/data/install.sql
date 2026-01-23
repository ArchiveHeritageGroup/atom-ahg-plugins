-- ============================================================================
-- ahgAIPlugin Database Tables
-- Version: 2.0.0
-- Last Updated: 2026-01-23
--
-- Consolidated AI Plugin: NER, Translation, Summarization, Spellcheck
-- ============================================================================

-- ============================================================================
-- SHARED TABLES (used by all AI features)
-- ============================================================================

-- AI Settings table (replaces ahg_ner_settings)
CREATE TABLE IF NOT EXISTS ahg_ai_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature VARCHAR(50) NOT NULL DEFAULT 'general',
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_feature_key (feature, setting_key),
    INDEX idx_ai_settings_feature (feature)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API usage tracking table
CREATE TABLE IF NOT EXISTS ahg_ai_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature VARCHAR(50) NOT NULL,
    user_id INT DEFAULT NULL,
    api_key VARCHAR(100) DEFAULT NULL,
    endpoint VARCHAR(100) NOT NULL,
    request_size INT DEFAULT 0,
    response_time_ms INT DEFAULT NULL,
    status_code INT DEFAULT 200,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_usage_feature (feature),
    INDEX idx_ai_usage_user (user_id),
    INDEX idx_ai_usage_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NER (Named Entity Recognition) TABLES
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

-- Entity linking to AtoM actors
CREATE TABLE IF NOT EXISTS ahg_ner_entity_link (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_id BIGINT UNSIGNED NOT NULL,
    actor_id INT NOT NULL,
    link_type ENUM('exact', 'fuzzy', 'manual') DEFAULT 'manual',
    confidence DECIMAL(5,4) DEFAULT 1.0000,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ner_link_entity (entity_id),
    INDEX idx_ner_link_actor (actor_id),
    FOREIGN KEY (entity_id) REFERENCES ahg_ner_entity(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TRANSLATION TABLES
-- ============================================================================

-- Translation queue for batch jobs
CREATE TABLE IF NOT EXISTS ahg_translation_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    source_culture VARCHAR(10) NOT NULL,
    target_culture VARCHAR(10) NOT NULL,
    fields TEXT NOT NULL COMMENT 'JSON array of fields to translate',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_translation_queue_status (status),
    INDEX idx_translation_queue_object (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Translation log/audit
CREATE TABLE IF NOT EXISTS ahg_translation_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    source_culture VARCHAR(10) NOT NULL,
    target_culture VARCHAR(10) NOT NULL,
    source_text TEXT DEFAULT NULL,
    translated_text TEXT DEFAULT NULL,
    translation_engine VARCHAR(50) DEFAULT 'argos',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_translation_log_object (object_id),
    INDEX idx_translation_log_cultures (source_culture, target_culture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DEFAULT SETTINGS
-- ============================================================================

INSERT INTO ahg_ai_settings (feature, setting_key, setting_value) VALUES
    -- General AI settings
    ('general', 'api_url', 'http://192.168.0.112:5004/ai/v1'),
    ('general', 'api_key', 'ahg_ai_demo_internal_2026'),
    ('general', 'api_timeout', '60'),

    -- NER settings
    ('ner', 'enabled', '1'),
    ('ner', 'auto_link_exact', '0'),
    ('ner', 'confidence_threshold', '0.85'),
    ('ner', 'enabled_entity_types', '["PERSON","ORG","GPE","DATE"]'),

    -- Summarization settings
    ('summarize', 'enabled', '1'),
    ('summarize', 'max_length', '1000'),
    ('summarize', 'min_length', '100'),
    ('summarize', 'target_field', 'scope_and_content'),

    -- Translation settings
    ('translate', 'enabled', '1'),
    ('translate', 'engine', 'argos'),
    ('translate', 'supported_languages', '["en","af","fr","nl","pt","es","de"]'),
    ('translate', 'auto_install_packages', '0'),

    -- Spellcheck settings
    ('spellcheck', 'enabled', '1'),
    ('spellcheck', 'language', 'en'),
    ('spellcheck', 'ignore_capitalized', '1')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- MIGRATION: Move data from old ahg_ner_settings if exists
-- ============================================================================

INSERT IGNORE INTO ahg_ai_settings (feature, setting_key, setting_value)
SELECT
    CASE
        WHEN setting_key LIKE 'summarizer_%' THEN 'summarize'
        ELSE 'ner'
    END as feature,
    CASE
        WHEN setting_key = 'summarizer_max_length' THEN 'max_length'
        WHEN setting_key = 'summarizer_min_length' THEN 'min_length'
        ELSE setting_key
    END as setting_key,
    setting_value
FROM ahg_ner_settings
WHERE setting_key NOT IN ('api_url', 'api_key', 'api_timeout');
