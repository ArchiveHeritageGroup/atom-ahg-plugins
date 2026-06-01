-- Migration: Z39.50 SERVER mode (raw binary ISO 23950 daemon)
-- ahgLibraryPlugin — PSIS parity with Heratio ahg-z3950 server half.
--
-- PSIS already has: library_z3950_target (client), library_sru_log (SRU/HTTP
-- server), library_z3950_import_log. This adds the raw Z39.50 *server* tables:
-- daemon config + an APDU request log.
--
-- No ENUM columns (VARCHAR + COMMENT). No FOREIGN KEY to core AtoM tables.

-- 1. Server daemon configuration (key/value)
CREATE TABLE IF NOT EXISTS library_z3950_server_config (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  option_key   VARCHAR(64)   NOT NULL UNIQUE COMMENT 'host, port, timeout, max_result_set, enabled, default_element_set',
  option_value TEXT          NULL,
  category     VARCHAR(32)   NOT NULL DEFAULT 'server' COMMENT 'server | bib1 | limits',
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_z3950srv_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Incoming APDU request log (one row per INIT/SEARCH/PRESENT/CLOSE etc.)
CREATE TABLE IF NOT EXISTS library_z3950_server_request (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_addr    VARCHAR(45)   NOT NULL DEFAULT '' COMMENT 'IPv4/IPv6 address of client',
  apdu_type      VARCHAR(32)   NOT NULL DEFAULT '' COMMENT 'init_request, search_request, present_request, close, delete_result_set, unknown, error',
  bytes_received INT UNSIGNED  NOT NULL DEFAULT 0,
  result_count   INT UNSIGNED  NULL COMMENT 'For search APDUs: hit count',
  elapsed_ms     INT UNSIGNED  NULL COMMENT 'APDU processing time in milliseconds',
  error_detail   TEXT          NULL,
  created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_z3950req_client (client_addr),
  INDEX idx_z3950req_type (apdu_type),
  INDEX idx_z3950req_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed sensible server defaults (idempotent).
INSERT INTO library_z3950_server_config (option_key, option_value, category)
SELECT 'enabled', '0', 'server' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM library_z3950_server_config WHERE option_key = 'enabled');

INSERT INTO library_z3950_server_config (option_key, option_value, category)
SELECT 'host', '0.0.0.0', 'server' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM library_z3950_server_config WHERE option_key = 'host');

INSERT INTO library_z3950_server_config (option_key, option_value, category)
SELECT 'port', '9210', 'server' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM library_z3950_server_config WHERE option_key = 'port');

INSERT INTO library_z3950_server_config (option_key, option_value, category)
SELECT 'timeout', '30', 'server' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM library_z3950_server_config WHERE option_key = 'timeout');

INSERT INTO library_z3950_server_config (option_key, option_value, category)
SELECT 'max_result_set', '1000', 'limits' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM library_z3950_server_config WHERE option_key = 'max_result_set');
