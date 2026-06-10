-- AI Cataloguer (#149 strand) — full-record AI draft storage. Idempotent.

CREATE TABLE IF NOT EXISTS ahg_catalog_draft (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    draft_json LONGTEXT NOT NULL,
    model VARCHAR(120) DEFAULT NULL,
    tokens_used INT DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, applied, discarded',
    applied_fields JSON DEFAULT NULL,
    created_by INT DEFAULT NULL,
    applied_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    applied_at DATETIME DEFAULT NULL,
    INDEX idx_catalog_object (object_id),
    INDEX idx_catalog_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
