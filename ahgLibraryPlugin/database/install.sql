-- =====================================================
-- Library Plugin Install
-- =====================================================

SET @library_exists = (SELECT COUNT(*) FROM term WHERE code = 'library' AND taxonomy_id = 70);

INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @library_exists = 0;

SET @library_id = LAST_INSERT_ID();

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT @library_id, 70, 'library', 'en' FROM DUAL WHERE @library_exists = 0 AND @library_id > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT @library_id, 'en', 'Library (MARC-inspired)' FROM DUAL WHERE @library_exists = 0 AND @library_id > 0;

-- =====================================================
-- Library Level of Description Terms (taxonomy_id = 34)
-- =====================================================

-- Book
SET @book_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Book' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @book_exists IS NULL;
SET @book_id = IF(@book_exists IS NULL, LAST_INSERT_ID(), @book_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @book_id, 34, 'en' FROM DUAL WHERE @book_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @book_id, 'en', 'Book' FROM DUAL WHERE @book_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@book_id, 'level-book');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@book_id, 'library', 10);

-- Monograph
SET @mono_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Monograph' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @mono_exists IS NULL;
SET @mono_id = IF(@mono_exists IS NULL, LAST_INSERT_ID(), @mono_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @mono_id, 34, 'en' FROM DUAL WHERE @mono_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @mono_id, 'en', 'Monograph' FROM DUAL WHERE @mono_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@mono_id, 'level-monograph');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@mono_id, 'library', 20);

-- Periodical
SET @peri_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Periodical' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @peri_exists IS NULL;
SET @peri_id = IF(@peri_exists IS NULL, LAST_INSERT_ID(), @peri_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @peri_id, 34, 'en' FROM DUAL WHERE @peri_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @peri_id, 'en', 'Periodical' FROM DUAL WHERE @peri_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@peri_id, 'level-periodical');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@peri_id, 'library', 30);

-- Journal
SET @jour_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Journal' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @jour_exists IS NULL;
SET @jour_id = IF(@jour_exists IS NULL, LAST_INSERT_ID(), @jour_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @jour_id, 34, 'en' FROM DUAL WHERE @jour_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @jour_id, 'en', 'Journal' FROM DUAL WHERE @jour_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@jour_id, 'level-journal');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@jour_id, 'library', 40);

-- Article
SET @arti_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Article' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @arti_exists IS NULL;
SET @arti_id = IF(@arti_exists IS NULL, LAST_INSERT_ID(), @arti_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @arti_id, 34, 'en' FROM DUAL WHERE @arti_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @arti_id, 'en', 'Article' FROM DUAL WHERE @arti_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@arti_id, 'level-article');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@arti_id, 'library', 45);

-- Manuscript
SET @manu_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Manuscript' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @manu_exists IS NULL;
SET @manu_id = IF(@manu_exists IS NULL, LAST_INSERT_ID(), @manu_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @manu_id, 34, 'en' FROM DUAL WHERE @manu_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @manu_id, 'en', 'Manuscript' FROM DUAL WHERE @manu_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@manu_id, 'level-manuscript');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@manu_id, 'library', 50);

-- Document (shared with DAM)
SET @doc_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Document' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @doc_exists IS NULL;
SET @doc_id = IF(@doc_exists IS NULL, LAST_INSERT_ID(), @doc_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @doc_id, 34, 'en' FROM DUAL WHERE @doc_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @doc_id, 'en', 'Document' FROM DUAL WHERE @doc_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@doc_id, 'level-document');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@doc_id, 'library', 60);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@doc_id, 'dam', 50);

-- =====================================================
-- Subject Authority Tables (Issue #55)
-- =====================================================

-- Subject Authority - stores controlled subject headings with usage tracking
CREATE TABLE IF NOT EXISTS library_subject_authority (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heading VARCHAR(500) NOT NULL COMMENT 'The subject heading text',
    heading_normalized VARCHAR(500) NOT NULL COMMENT 'Normalized form for matching',
    heading_type VARCHAR(68) COMMENT 'topical, personal, corporate, geographic, genre, meeting' DEFAULT 'topical',
    source VARCHAR(50) DEFAULT 'lcsh' COMMENT 'Source vocabulary (lcsh, mesh, local, etc.)',
    lcsh_id VARCHAR(100) COMMENT 'Authority record ID (e.g., sh85034652)',
    lcsh_uri VARCHAR(500) COMMENT 'Full URI to authority record',
    suggested_dewey VARCHAR(50) COMMENT 'Suggested Dewey classification for this subject',
    suggested_lcc VARCHAR(50) COMMENT 'Suggested LCC classification for this subject',
    broader_terms JSON COMMENT 'Parent/broader subject terms',
    narrower_terms JSON COMMENT 'Child/narrower subject terms',
    related_terms JSON COMMENT 'Related subject terms',
    usage_count INT UNSIGNED DEFAULT 1 COMMENT 'Number of times used in catalog',
    first_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_heading (heading_normalized, heading_type, source),
    INDEX idx_usage (usage_count DESC),
    INDEX idx_type (heading_type),
    INDEX idx_source (source),
    FULLTEXT INDEX ft_heading (heading)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entity-Subject Map - bridges NER entities to subject authorities
CREATE TABLE IF NOT EXISTS library_entity_subject_map (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL COMMENT 'NER entity type (PERSON, ORG, GPE, etc.)',
    entity_value VARCHAR(500) NOT NULL COMMENT 'Original entity value',
    entity_normalized VARCHAR(500) NOT NULL COMMENT 'Normalized entity value for matching',
    subject_authority_id BIGINT UNSIGNED NOT NULL COMMENT 'FK to subject authority',
    co_occurrence_count INT UNSIGNED DEFAULT 1 COMMENT 'Times this entity appeared with this subject',
    confidence DECIMAL(5,4) DEFAULT 1.0000 COMMENT 'Confidence score for the mapping',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_normalized),
    INDEX idx_authority (subject_authority_id),
    INDEX idx_confidence (confidence DESC),
    FOREIGN KEY (subject_authority_id) REFERENCES library_subject_authority(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alter existing library_item_subject table to add authority link fields
-- Note: These ALTER statements are idempotent (safe to run multiple times)
-- Check if columns exist before adding to avoid errors on re-run

-- Add lcsh_id column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND COLUMN_NAME = 'lcsh_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE library_item_subject ADD COLUMN lcsh_id VARCHAR(100) AFTER uri',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add authority_id column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND COLUMN_NAME = 'authority_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE library_item_subject ADD COLUMN authority_id BIGINT UNSIGNED AFTER lcsh_id',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add dewey_number column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND COLUMN_NAME = 'dewey_number');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE library_item_subject ADD COLUMN dewey_number VARCHAR(50) AFTER authority_id',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add lcc_number column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND COLUMN_NAME = 'lcc_number');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE library_item_subject ADD COLUMN lcc_number VARCHAR(50) AFTER dewey_number',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add subdivisions JSON column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND COLUMN_NAME = 'subdivisions');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE library_item_subject ADD COLUMN subdivisions JSON AFTER lcc_number',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add FK constraint to authority table (only if column exists and constraint doesn't)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND CONSTRAINT_NAME = 'fk_item_subject_authority');
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_subject' AND COLUMN_NAME = 'authority_id');
SET @sql = IF(@fk_exists = 0 AND @col_exists > 0,
    'ALTER TABLE library_item_subject ADD CONSTRAINT fk_item_subject_authority FOREIGN KEY (authority_id) REFERENCES library_subject_authority(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Link library_item_creator to actor (Authority Record). Nullable so the
-- existing free-text path still works when no matching actor exists yet;
-- LibraryService::resolveOrCreateActor upserts the actor on save and
-- populates this. Backfill stale rows with: php symfony library:backfill-authors
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_creator' AND COLUMN_NAME = 'actor_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE library_item_creator ADD COLUMN actor_id INT UNSIGNED NULL AFTER name, ADD INDEX idx_library_item_creator_actor (actor_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- KBART Vendor Management (atom-ahg-plugins#97)
-- =====================================================

-- KBART Vendor configuration table
CREATE TABLE IF NOT EXISTS library_kbart_vendor (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Human-readable vendor name',
    feed_url VARCHAR(1000) NOT NULL COMMENT 'URL to the KBART TSV feed',
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_fetch_at DATETIME NULL,
    last_row_count INT UNSIGNED NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_feed_url (feed_url(768)),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KBART import log (per-fetch audit trail)
CREATE TABLE IF NOT EXISTS library_kbart_import_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT UNSIGNED NOT NULL,
    fetched_at DATETIME NOT NULL,
    row_count INT UNSIGNED DEFAULT 0,
    new_count INT UNSIGNED DEFAULT 0,
    removed_count INT UNSIGNED DEFAULT 0,
    error TEXT NULL,
    INDEX idx_vendor (vendor_id),
    INDEX idx_fetched (fetched_at DESC),
    FOREIGN KEY (vendor_id) REFERENCES library_kbart_vendor(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Z39.50 server (parity) (registered for fresh installs)
-- ============================================================
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
