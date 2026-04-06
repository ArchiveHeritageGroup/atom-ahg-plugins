-- ============================================================
-- ahgDiscoveryPlugin - PageIndex Tables (Phase 2)
-- Version: 1.0.0
-- ============================================================

-- Tree index storage — hierarchical JSON tree per document
CREATE TABLE IF NOT EXISTS ahg_pageindex_tree (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id       INT NOT NULL COMMENT 'information_object.id or external doc ID',
    object_type     VARCHAR(20) NOT NULL COMMENT 'ead, pdf, rico',
    tree_json       LONGTEXT NOT NULL COMMENT 'Hierarchical JSON tree built by LLM',
    status          VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, building, ready, error',
    error_message   TEXT NULL COMMENT 'Error details if status=error',
    indexed_at      TIMESTAMP NULL COMMENT 'When the tree was last built',
    model_used      VARCHAR(100) NULL COMMENT 'LLM model that built the tree',
    node_count      INT NOT NULL DEFAULT 0 COMMENT 'Number of nodes in the tree',
    source_hash     VARCHAR(64) NULL COMMENT 'SHA-256 of source content for change detection',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_object (object_id, object_type),
    INDEX idx_status (status),
    INDEX idx_object_type (object_type),
    INDEX idx_indexed_at (indexed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Query log — tracks PageIndex queries and matched nodes
CREATE TABLE IF NOT EXISTS ahg_pageindex_query_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query_text      TEXT NOT NULL,
    tree_id         BIGINT UNSIGNED NULL COMMENT 'ahg_pageindex_tree.id searched',
    matched_node_ids TEXT NULL COMMENT 'JSON array of matched node IDs from tree',
    result_count    INT NOT NULL DEFAULT 0,
    reasoning_text  TEXT NULL COMMENT 'LLM reasoning explanation for the match',
    model_used      VARCHAR(100) NULL,
    response_ms     INT NULL COMMENT 'LLM response time in milliseconds',
    user_id         INT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tree (tree_id),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
