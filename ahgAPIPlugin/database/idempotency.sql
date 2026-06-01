-- ahgAPIPlugin — Idempotency-Key request dedup
-- AtoM/Symfony-1.x port of Heratio ahg-api idempotency (issue #652).
--
-- Stores Idempotency-Key replay records for mutating API requests
-- (POST/PUT/PATCH). On a repeat with the same key + body + route within the
-- TTL window, the cached response is replayed. Pruned by:
--   php bin/atom api:prune-idempotency
--
-- Conventions: CREATE TABLE IF NOT EXISTS, no ENUM, no FK to core tables.

CREATE TABLE IF NOT EXISTS ahg_api_idempotency_key (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idem_key VARCHAR(64) NOT NULL COMMENT 'Client-supplied Idempotency-Key header value',
    user_id INT NOT NULL DEFAULT 0 COMMENT 'Authenticated user id (0 = anonymous)',
    route VARCHAR(255) NOT NULL COMMENT 'Request path, e.g. api/v2/descriptions',
    request_hash CHAR(64) NOT NULL COMMENT 'sha256 of the raw request body',
    response_status SMALLINT UNSIGNED NOT NULL COMMENT 'Cached HTTP status code',
    response_body MEDIUMTEXT NULL COMMENT 'Cached response body to replay',
    response_headers TEXT NULL COMMENT 'JSON map of cached response headers',
    expires_at DATETIME NOT NULL COMMENT 'Replay window end (created_at + 24h)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_key (user_id, idem_key),
    KEY idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
