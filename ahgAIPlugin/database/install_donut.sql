-- ============================================================================
-- ahgAIPlugin - DONUT Document Understanding tables
-- ============================================================================
-- Stores structured document-parsing results returned by the DONUT model
-- (served by the ahg-ai python service, default host .115:5008).
-- No ENUM columns; no FOREIGN KEY to core AtoM tables; never touches
-- atom_plugin. Provenance rows are written best-effort into ahg_ai_inference
-- (owned by ahgProvenancePlugin) at runtime - that table is NOT created here.
-- ============================================================================

CREATE TABLE IF NOT EXISTS ahg_donut_extraction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    information_object_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'Linked IO id once finalised; NULL while pending',
    source_filename VARCHAR(255) DEFAULT NULL COMMENT 'Original document image filename',
    input_hash CHAR(64) DEFAULT NULL COMMENT 'sha256 of the source image bytes',
    doc_type VARCHAR(64) DEFAULT NULL COMMENT 'Classified document type as reported by DONUT',
    confidence DECIMAL(6,5) DEFAULT NULL COMMENT 'Normalised 0.0-1.0 confidence; NULL when not exposed',
    needs_review TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 when the model flags low confidence',
    fields_json JSON DEFAULT NULL COMMENT 'Flattened name => value structured field map',
    raw_json JSON DEFAULT NULL COMMENT 'Full raw gateway payload for audit/replay',
    model_name VARCHAR(255) DEFAULT NULL COMMENT 'Model identifier reported by the gateway',
    model_version VARCHAR(64) DEFAULT NULL COMMENT 'Model version string',
    service_url VARCHAR(255) DEFAULT NULL COMMENT 'Gateway base URL the call was made against',
    elapsed_ms INT DEFAULT NULL COMMENT 'Call latency in milliseconds',
    status VARCHAR(20) NOT NULL DEFAULT 'extracted' COMMENT 'extracted, needs_review, finalised, rejected',
    user_id INT DEFAULT NULL COMMENT 'Triggering user when known',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_donut_io (information_object_id),
    INDEX idx_donut_status (status),
    INDEX idx_donut_hash (input_hash),
    INDEX idx_donut_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings for the DONUT feature (idempotent).
INSERT INTO ahg_ai_settings (feature, setting_key, setting_value)
VALUES
    ('donut', 'enabled', '1'),
    ('donut', 'donut_service_url', 'http://192.168.0.115:5008'),
    ('donut', 'donut_timeout', '60')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
