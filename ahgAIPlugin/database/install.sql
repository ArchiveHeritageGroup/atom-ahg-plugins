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
-- LLM DESCRIPTION SUGGESTION TABLES
-- ============================================================================

-- LLM Provider Configurations (Ollama, OpenAI, Anthropic)
CREATE TABLE IF NOT EXISTS ahg_llm_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,              -- 'ollama', 'openai', 'anthropic'
    name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    endpoint_url VARCHAR(500),                  -- 'http://localhost:11434'
    api_key_encrypted TEXT,                     -- Encrypted API key (NULL for Ollama)
    model VARCHAR(100) NOT NULL,                -- 'llama3.1:8b', 'gpt-4o-mini', 'claude-3-haiku-20240307'
    max_tokens INT DEFAULT 2000,
    temperature DECIMAL(3,2) DEFAULT 0.70,
    timeout_seconds INT DEFAULT 120,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_llm_config_provider (provider),
    INDEX idx_llm_config_active (is_active),
    INDEX idx_llm_config_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prompt Templates for different description contexts
CREATE TABLE IF NOT EXISTS ahg_prompt_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    system_prompt TEXT NOT NULL,
    user_prompt_template TEXT NOT NULL,         -- Contains {title}, {ocr_text}, etc.
    level_of_description VARCHAR(50),           -- NULL=all, or 'fonds','series','file','item'
    repository_id INT,                          -- NULL=global
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    include_ocr TINYINT(1) DEFAULT 1,
    max_ocr_chars INT DEFAULT 8000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prompt_template_level (level_of_description),
    INDEX idx_prompt_template_repo (repository_id),
    INDEX idx_prompt_template_default (is_default),
    INDEX idx_prompt_template_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Description Suggestions with review workflow
CREATE TABLE IF NOT EXISTS ahg_description_suggestion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    suggested_text TEXT NOT NULL,
    existing_text TEXT,
    prompt_template_id INT UNSIGNED,
    llm_config_id INT UNSIGNED,
    source_data JSON,                           -- {has_ocr: true, fields: [...]}
    status ENUM('pending','approved','rejected','edited') DEFAULT 'pending',
    edited_text TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT,
    generation_time_ms INT,
    tokens_used INT,
    model_used VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_suggestion_object (object_id),
    INDEX idx_suggestion_status (status),
    INDEX idx_suggestion_created (created_at),
    INDEX idx_suggestion_template (prompt_template_id),
    INDEX idx_suggestion_llm (llm_config_id),
    FOREIGN KEY (prompt_template_id) REFERENCES ahg_prompt_template(id) ON DELETE SET NULL,
    FOREIGN KEY (llm_config_id) REFERENCES ahg_llm_config(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DEFAULT LLM CONFIGURATIONS
-- ============================================================================

-- Default Ollama configuration (local)
INSERT INTO ahg_llm_config (provider, name, is_active, is_default, endpoint_url, model, max_tokens, temperature, timeout_seconds)
VALUES ('ollama', 'Local Ollama (llama3.1:8b)', 1, 1, 'http://localhost:11434', 'llama3.1:8b', 2000, 0.70, 120)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- OpenAI placeholder (disabled by default, needs API key)
INSERT INTO ahg_llm_config (provider, name, is_active, is_default, endpoint_url, model, max_tokens, temperature, timeout_seconds)
VALUES ('openai', 'OpenAI GPT-4o-mini', 0, 0, 'https://api.openai.com/v1', 'gpt-4o-mini', 2000, 0.70, 60)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Anthropic placeholder (disabled by default, needs API key)
INSERT INTO ahg_llm_config (provider, name, is_active, is_default, endpoint_url, model, max_tokens, temperature, timeout_seconds)
VALUES ('anthropic', 'Anthropic Claude Haiku', 0, 0, 'https://api.anthropic.com/v1', 'claude-3-haiku-20240307', 2000, 0.70, 60)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- DEFAULT PROMPT TEMPLATES
-- ============================================================================

-- Standard Archival Description Template
INSERT INTO ahg_prompt_template (name, slug, system_prompt, user_prompt_template, level_of_description, is_default, is_active, include_ocr, max_ocr_chars)
VALUES (
    'Standard Archival Description',
    'standard-archival',
    'You are an expert archivist creating scope and content descriptions for archival records. Write professional, objective descriptions following ISAD(G) standards. Focus on:
- What the materials document
- The activities, functions, or transactions they record
- Any significant persons, organizations, places, or events mentioned
- The date range and extent of materials
- The arrangement and organization

Write in third person, past tense. Be concise but comprehensive. Do not include subjective assessments or opinions.',
    'Create a scope and content description for the following archival record:

Title: {title}
Reference Code: {identifier}
Level of Description: {level_of_description}
Date Range: {date_range}
Creator: {creator}
Repository: {repository}

{existing_metadata}

{ocr_section}

Based on the above information, write a professional scope and content description (2-4 paragraphs).',
    NULL,
    1,
    1,
    1,
    8000
)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Item-Level OCR Focus Template
INSERT INTO ahg_prompt_template (name, slug, system_prompt, user_prompt_template, level_of_description, is_default, is_active, include_ocr, max_ocr_chars)
VALUES (
    'Item-Level with OCR',
    'item-ocr',
    'You are an expert archivist creating item-level descriptions based primarily on OCR text extracted from documents. Your task is to:
- Summarize the main content and purpose of the document
- Identify key persons, organizations, and places mentioned
- Note significant dates and events
- Describe the document type and any notable features

Write in third person, past tense. Be concise and accurate. If the OCR text is fragmentary, acknowledge limitations.',
    'Create a scope and content description for this item:

Title: {title}
Reference Code: {identifier}
Date: {date_range}
Document Type: {extent_and_medium}

The following OCR text was extracted from the document:
---
{ocr_text}
---

Based on the document content, write a scope and content description (1-2 paragraphs) summarizing what this document contains and its significance.',
    'item',
    0,
    1,
    1,
    12000
)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Photograph/Image Description Template
INSERT INTO ahg_prompt_template (name, slug, system_prompt, user_prompt_template, level_of_description, is_default, is_active, include_ocr, max_ocr_chars)
VALUES (
    'Photograph Description',
    'photograph',
    'You are an expert archivist creating descriptions for historical photographs. Focus on:
- The subject matter and scene depicted
- Identifiable persons, places, and events
- The photographic technique and format
- Historical context and significance

Write in third person, past tense. Be objective and descriptive. Note any inscriptions, captions, or annotations.',
    'Create a scope and content description for this photograph:

Title: {title}
Reference Code: {identifier}
Date: {date_range}
Physical Description: {extent_and_medium}
Creator: {creator}

{existing_metadata}

{ocr_section}

Based on the available information, write a scope and content description (1-2 paragraphs) for this photograph.',
    'item',
    0,
    1,
    1,
    4000
)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- DESCRIPTION SUGGESTION SETTINGS
-- ============================================================================

INSERT INTO ahg_ai_settings (feature, setting_key, setting_value) VALUES
    ('suggest', 'enabled', '1'),
    ('suggest', 'require_review', '1'),
    ('suggest', 'auto_expire_days', '30'),
    ('suggest', 'default_llm_config', '1'),
    ('suggest', 'default_template', '1'),
    ('suggest', 'max_pending_per_object', '3')
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
