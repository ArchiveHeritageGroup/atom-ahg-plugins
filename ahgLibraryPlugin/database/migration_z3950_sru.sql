-- Migration: Z39.50 Client + SRU HTTP Server
-- Issue #92 — ahgLibraryPlugin

-- 1. Target config table
CREATE TABLE IF NOT EXISTS library_z3950_target (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(255)     NOT NULL  COMMENT 'Human-readable target name',
  host            VARCHAR(255)     NOT NULL  COMMENT 'Z39.50 host or SRU base URL',
  port            INT UNSIGNED     NOT NULL  DEFAULT 210  COMMENT 'Z39.50 port (default 210)',
  `database`      VARCHAR(255)     NOT NULL  COMMENT 'Target database / collection name',
  syntax          VARCHAR(50)      DEFAULT 'marc21'  COMMENT 'marc21 | usmarc | xml',
  username        VARCHAR(255)     NULL,
  password_hash   VARCHAR(64)      NULL  COMMENT 'SHA-256 of the password',
  timeout         INT UNSIGNED     DEFAULT 15  COMMENT 'Connection timeout in seconds',
  is_active       TINYINT(1)        DEFAULT 1,
  created_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_host_port (host, port),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SRU query log (for audit / analytics)
CREATE TABLE IF NOT EXISTS library_sru_log (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  query           TEXT            NULL,
  cql_query       TEXT            NULL  COMMENT 'The parsed/converted CQL query',
  result_count    INT UNSIGNED    DEFAULT 0,
  duration_ms     DECIMAL(10,1)   NULL,
  error           TEXT            NULL,
  remote_addr     VARCHAR(45)     NULL,
  api_key_hint    VARCHAR(64)     NULL  COMMENT 'SHA-256 prefix of API key used (not the key itself)',
  created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_created_at (created_at),
  INDEX idx_result_count (result_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Z39.50 import log (for tracking imports from remote targets)
CREATE TABLE IF NOT EXISTS library_z3950_import_log (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  target_id         BIGINT UNSIGNED NULL,
  query             VARCHAR(500)    NULL,
  records_received  INT UNSIGNED     DEFAULT 0,
  records_imported INT UNSIGNED     DEFAULT 0,
  records_skipped  INT UNSIGNED     DEFAULT 0,
  records_errors   INT UNSIGNED     DEFAULT 0,
  duration_ms      DECIMAL(10,1)   NULL,
  error            TEXT            NULL,
  created_by       BIGINT UNSIGNED  NULL,
  created_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (target_id) REFERENCES library_z3950_target(id) ON DELETE SET NULL,
  INDEX idx_target_id (target_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;