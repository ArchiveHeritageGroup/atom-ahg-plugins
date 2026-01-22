-- ahgSemanticSearchPlugin Database Schema
-- Semantic search, thesaurus management, and vector embeddings
-- Version: 1.0.0

-- =====================================================
-- THESAURUS / SYNONYMS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS semantic_synonym (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Term identification
    term VARCHAR(255) NOT NULL,
    synonym VARCHAR(255) NOT NULL,
    language VARCHAR(10) DEFAULT 'en',

    -- Relationship
    relation_type ENUM('synonym', 'broader', 'narrower', 'related', 'use_for') DEFAULT 'synonym',
    weight DECIMAL(3,2) DEFAULT 1.00,

    -- Classification
    domain VARCHAR(50) DEFAULT 'general',
    source VARCHAR(50) DEFAULT 'local',
    source_id VARCHAR(255),

    -- Vector embedding (for semantic similarity)
    embedding BLOB,
    embedding_model VARCHAR(100),
    embedding_updated_at DATETIME,

    -- Metadata
    is_approved TINYINT(1) DEFAULT 1,
    usage_count INT UNSIGNED DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY idx_term_synonym_lang (term(100), synonym(100), language),
    INDEX idx_synonym_term (term),
    INDEX idx_synonym_synonym (synonym),
    INDEX idx_synonym_domain (domain),
    INDEX idx_synonym_source (source),
    INDEX idx_synonym_relation (relation_type),
    INDEX idx_synonym_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EMBEDDINGS TABLE (for semantic search)
-- =====================================================

CREATE TABLE IF NOT EXISTS semantic_embedding (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Reference to content
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,

    -- Embedding data
    embedding BLOB NOT NULL,
    embedding_model VARCHAR(100) NOT NULL,
    embedding_dimensions INT UNSIGNED,

    -- Text that was embedded
    text_hash VARCHAR(64),
    text_preview VARCHAR(500),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY idx_entity (entity_type, entity_id),
    INDEX idx_embedding_model (embedding_model),
    INDEX idx_embedding_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SETTINGS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS ahg_semantic_search_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- QUERY EXPANSION LOG (for analytics)
-- =====================================================

CREATE TABLE IF NOT EXISTS semantic_query_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    original_query VARCHAR(500) NOT NULL,
    expanded_query TEXT,
    expansion_terms TEXT,

    user_id INT,
    result_count INT UNSIGNED,
    clicked_result_id INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_query_date (created_at),
    INDEX idx_query_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT SETTINGS
-- =====================================================

INSERT INTO ahg_semantic_search_settings (setting_key, setting_value, setting_type) VALUES
('wordnet_enabled', 'true', 'boolean'),
('wikidata_enabled', 'true', 'boolean'),
('embedding_enabled', 'true', 'boolean'),
('embedding_model', 'all-MiniLM-L6-v2', 'string'),
('ollama_endpoint', 'http://localhost:11434', 'string'),
('expansion_limit', '5', 'integer'),
('min_similarity_weight', '0.6', 'float'),
('elasticsearch_synonyms_path', '/etc/elasticsearch/synonyms/ahg_synonyms.txt', 'string'),
('sync_interval_days', '7', 'integer'),
('last_cron_sync', '0', 'integer')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- =====================================================
-- REGISTER PLUGIN
-- =====================================================

INSERT INTO atom_plugin (name, class_name, version, description, category, is_enabled, is_core, is_locked, load_order)
VALUES ('ahgSemanticSearchPlugin', 'ahgSemanticSearchPluginConfiguration', '1.0.0', 'Semantic search with thesaurus, WordNet/Wikidata sync, and vector embeddings', 'search', 1, 0, 0, 60)
ON DUPLICATE KEY UPDATE version = VALUES(version), description = VALUES(description);
