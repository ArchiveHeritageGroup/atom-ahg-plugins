-- ============================================================
-- ahgDiscoveryPlugin - Database Schema
-- Version: 0.2.0
-- ============================================================

-- Result cache (avoid re-running identical queries)
CREATE TABLE IF NOT EXISTS ahg_discovery_cache (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query_hash      VARCHAR(64) NOT NULL,
    query_text      TEXT NOT NULL,
    expanded_json   TEXT NULL,
    result_json     LONGTEXT NOT NULL,
    result_count    INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at      TIMESTAMP NOT NULL,
    UNIQUE KEY uq_query_hash (query_hash),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search analytics (what people ask, what they click)
CREATE TABLE IF NOT EXISTS ahg_discovery_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NULL,
    query_text      TEXT NOT NULL,
    expanded_terms  TEXT NULL,
    result_count    INT NOT NULL DEFAULT 0,
    clicked_object  INT NULL,
    response_ms     INT NULL,
    session_id      VARCHAR(64) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
