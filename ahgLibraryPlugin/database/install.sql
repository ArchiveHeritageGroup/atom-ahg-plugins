-- =====================================================
-- Library Plugin Install
-- =====================================================

SET @library_exists = (SELECT COUNT(*) FROM term WHERE code = 'library' AND taxonomy_id = 70);

INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @library_exists = 0;

SET @library_id = LAST_INSERT_ID();

INSERT INTO term (id, taxonomy_id, code, source_culture, parent_id)
SELECT @library_id, 70, 'library', 'en', 110 FROM DUAL WHERE @library_exists = 0 AND @library_id > 0;

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

-- =========================================================================
-- FRBR work-set clustering (folded from migration_frbr_clustering.sql, #95).
-- library_item is created by the framework install.sql WITHOUT these columns,
-- but OpacService/FrbrService query library_item.frbr_work_key /
-- frbr_override_type, so a fresh install must add them here. Idempotent
-- (INFORMATION_SCHEMA guards); VARCHAR not ENUM per AHG standards.
-- =========================================================================

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_item' AND COLUMN_NAME='frbr_work_key');
SET @s := IF(@c=0, "ALTER TABLE library_item ADD COLUMN frbr_work_key VARCHAR(64) NULL COMMENT 'SHA-256 work identifier, first 20 chars'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_item' AND COLUMN_NAME='frbr_override_type');
SET @s := IF(@c=0, "ALTER TABLE library_item ADD COLUMN frbr_override_type VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'none, force_group, force_split'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_item' AND INDEX_NAME='idx_library_item_frbr_work_key');
SET @s := IF(@c=0, 'CREATE INDEX idx_library_item_frbr_work_key ON library_item (frbr_work_key)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_item' AND INDEX_NAME='idx_library_item_frbr_override');
SET @s := IF(@c=0, 'CREATE INDEX idx_library_item_frbr_override ON library_item (frbr_override_type)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS library_item_frbr_override (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  library_item_id  BIGINT UNSIGNED NOT NULL,
  target_work_key  VARCHAR(64)  NULL COMMENT 'force_group: merge this item INTO the target work key',
  forced_split     TINYINT(1)   DEFAULT 0 COMMENT 'force_split: do NOT cluster this item with any other',
  reason           VARCHAR(500) NULL,
  created_by       BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_frbr_override_target (target_work_key),
  INDEX idx_frbr_override_item (library_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_usage_event');
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_usage_event' AND COLUMN_NAME='frbr_work_key');
SET @s := IF(@t=1 AND @c=0, 'ALTER TABLE library_usage_event ADD COLUMN frbr_work_key VARCHAR(64) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_usage_event' AND INDEX_NAME='idx_library_usage_event_work_key');
SET @s := IF(@t=1 AND @i=0, 'CREATE INDEX idx_library_usage_event_work_key ON library_usage_event (frbr_work_key)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =========================================================================
-- FULL LIBRARY SCHEMA — folded from the plugin's migration_*.sql files so a
-- fresh install builds the complete library schema. bin/install runs only
-- this install.sql (never the migration_*.sql), so on a clean DB /library
-- 500'd for missing columns/tables. All statements idempotent
-- (INFORMATION_SCHEMA guards / CREATE TABLE IF NOT EXISTS).
-- =========================================================================

-- --- #214 full library circulation system (migration_full_library.sql) ------
-- ============================================================================
-- ahgLibraryPlugin — Full Library System Migration
-- Issue #214: Extend Heratio to be a full library system
-- Date: 2026-03-08
-- ============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- 1. Heritage Accounting columns on library_item
-- ============================================================================

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'heritage_asset_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN heritage_asset_id INT UNSIGNED NULL COMMENT ''FK to heritage_asset'' AFTER updated_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_method');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_method VARCHAR(50) NULL COMMENT ''purchase, donation, gift, bequest, exchange, deposit'' AFTER heritage_asset_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_date');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_date DATE NULL AFTER acquisition_method', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_cost');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_cost DECIMAL(15,2) NULL AFTER acquisition_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_currency');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_currency VARCHAR(3) DEFAULT ''ZAR'' AFTER acquisition_cost', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'replacement_value');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN replacement_value DECIMAL(15,2) NULL AFTER acquisition_currency', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'insurance_value');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN insurance_value DECIMAL(15,2) NULL AFTER replacement_value', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'insurance_policy');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN insurance_policy VARCHAR(100) NULL AFTER insurance_value', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'insurance_expiry');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN insurance_expiry DATE NULL AFTER insurance_policy', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'asset_class_code');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN asset_class_code VARCHAR(20) NULL COMMENT ''heritage_asset_class.code'' AFTER insurance_expiry', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'recognition_status');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN recognition_status VARCHAR(30) NULL DEFAULT ''pending'' AFTER asset_class_code', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'valuation_date');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN valuation_date DATE NULL AFTER recognition_status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'valuation_method');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN valuation_method VARCHAR(50) NULL AFTER valuation_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'valuation_notes');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN valuation_notes TEXT NULL AFTER valuation_method', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'donor_name');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN donor_name VARCHAR(255) NULL AFTER valuation_notes', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'donor_restrictions');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN donor_restrictions TEXT NULL AFTER donor_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'condition_grade');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN condition_grade VARCHAR(30) NULL AFTER donor_restrictions', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'conservation_priority');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN conservation_priority VARCHAR(20) NULL AFTER condition_grade', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 2. Library Copy (individual physical copies)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_copy (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL,
    copy_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    barcode VARCHAR(50) NULL,
    accession_number VARCHAR(50) NULL,
    call_number_suffix VARCHAR(20) NULL COMMENT 'e.g. c.2, v.3',
    shelf_location VARCHAR(100) NULL,
    branch VARCHAR(100) NULL COMMENT 'Library branch/location',
    status VARCHAR(30) NOT NULL DEFAULT 'available',
    condition_grade VARCHAR(30) NULL,
    condition_notes TEXT NULL,
    acquisition_method VARCHAR(50) NULL,
    acquisition_date DATE NULL,
    acquisition_cost DECIMAL(15,2) NULL,
    acquisition_source VARCHAR(255) NULL COMMENT 'vendor or donor',
    withdrawal_date DATE NULL,
    withdrawal_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_barcode (barcode),
    KEY idx_item (library_item_id),
    KEY idx_status (status),
    KEY idx_branch (branch),
    KEY idx_accession (accession_number),
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. Library Patron (borrowers)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_patron (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT UNSIGNED NULL COMMENT 'FK to actor table',
    card_number VARCHAR(50) NOT NULL,
    patron_type VARCHAR(30) NOT NULL DEFAULT 'public',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    institution VARCHAR(255) NULL,
    department VARCHAR(100) NULL,
    id_number VARCHAR(50) NULL COMMENT 'National ID or student number',
    date_of_birth DATE NULL,
    membership_start DATE NOT NULL,
    membership_expiry DATE NULL,
    max_checkouts SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    max_renewals SMALLINT UNSIGNED NOT NULL DEFAULT 2,
    max_holds SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    borrowing_status VARCHAR(20) NOT NULL DEFAULT 'active',
    suspension_reason TEXT NULL,
    suspension_until DATE NULL,
    total_fines_owed DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_fines_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_checkouts INT UNSIGNED NOT NULL DEFAULT 0,
    last_activity_date DATE NULL,
    photo_url VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_card (card_number),
    KEY idx_actor (actor_id),
    KEY idx_type (patron_type),
    KEY idx_status (borrowing_status),
    KEY idx_name (last_name, first_name),
    KEY idx_email (email),
    KEY idx_expiry (membership_expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. Library Checkout (circulation transactions)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_checkout (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    copy_id BIGINT UNSIGNED NOT NULL,
    patron_id BIGINT UNSIGNED NOT NULL,
    checkout_date DATETIME NOT NULL,
    due_date DATE NOT NULL,
    return_date DATETIME NULL,
    renewed_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    checkout_notes TEXT NULL,
    return_notes TEXT NULL,
    return_condition VARCHAR(30) NULL,
    checked_out_by INT UNSIGNED NULL COMMENT 'Staff user_id',
    checked_in_by INT UNSIGNED NULL COMMENT 'Staff user_id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_copy (copy_id),
    KEY idx_patron (patron_id),
    KEY idx_status (status),
    KEY idx_due (due_date),
    KEY idx_checkout_date (checkout_date),
    FOREIGN KEY (copy_id) REFERENCES library_copy(id) ON DELETE RESTRICT,
    FOREIGN KEY (patron_id) REFERENCES library_patron(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. Library Hold (reservation queue)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_hold (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL,
    patron_id BIGINT UNSIGNED NOT NULL,
    hold_date DATETIME NOT NULL,
    expiry_date DATE NULL COMMENT 'Hold expires if not picked up',
    pickup_branch VARCHAR(100) NULL,
    queue_position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    notification_sent TINYINT(1) NOT NULL DEFAULT 0,
    notification_date DATETIME NULL,
    fulfilled_date DATETIME NULL,
    cancelled_date DATETIME NULL,
    cancel_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_item (library_item_id),
    KEY idx_patron (patron_id),
    KEY idx_status (status),
    KEY idx_queue (library_item_id, queue_position),
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE,
    FOREIGN KEY (patron_id) REFERENCES library_patron(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. Library Fine (fees & payments)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_fine (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patron_id BIGINT UNSIGNED NOT NULL,
    checkout_id BIGINT UNSIGNED NULL,
    fine_type VARCHAR(30) NOT NULL DEFAULT 'overdue',
    amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'ZAR',
    status VARCHAR(20) NOT NULL DEFAULT 'outstanding',
    description TEXT NULL,
    fine_date DATE NOT NULL,
    payment_date DATETIME NULL,
    payment_method VARCHAR(30) NULL,
    payment_reference VARCHAR(100) NULL,
    waived_by INT UNSIGNED NULL COMMENT 'Staff user_id who waived',
    waived_date DATETIME NULL,
    waive_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_patron (patron_id),
    KEY idx_checkout (checkout_id),
    KEY idx_status (status),
    KEY idx_type (fine_type),
    KEY idx_date (fine_date),
    FOREIGN KEY (patron_id) REFERENCES library_patron(id) ON DELETE RESTRICT,
    FOREIGN KEY (checkout_id) REFERENCES library_checkout(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. Library Subscription (serial management)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_subscription (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL COMMENT 'Parent serial/periodical',
    vendor_id INT UNSIGNED NULL COMMENT 'FK to vendor',
    subscription_number VARCHAR(100) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    renewal_date DATE NULL,
    frequency VARCHAR(30) NULL COMMENT 'From ahg_dropdown',
    issues_per_year SMALLINT UNSIGNED NULL,
    cost_per_year DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',
    budget_code VARCHAR(50) NULL,
    routing_list JSON NULL COMMENT 'Ordered list of staff for routing',
    delivery_method VARCHAR(30) NULL COMMENT 'mail, electronic, both',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_item (library_item_id),
    KEY idx_status (status),
    KEY idx_renewal (renewal_date),
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. Library Serial Issue (individual issue tracking)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_serial_issue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id BIGINT UNSIGNED NOT NULL,
    library_item_id BIGINT UNSIGNED NOT NULL,
    volume VARCHAR(20) NULL,
    issue_number VARCHAR(20) NULL,
    part VARCHAR(20) NULL,
    supplement VARCHAR(50) NULL,
    issue_date DATE NULL,
    expected_date DATE NULL,
    received_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'expected',
    claim_date DATE NULL,
    claim_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    barcode VARCHAR(50) NULL,
    shelf_location VARCHAR(100) NULL,
    bound_volume_id BIGINT UNSIGNED NULL COMMENT 'FK to bound volume record',
    notes TEXT NULL,
    checked_in_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_subscription (subscription_id),
    KEY idx_item (library_item_id),
    KEY idx_status (status),
    KEY idx_expected (expected_date),
    KEY idx_volume (volume, issue_number),
    UNIQUE KEY uk_barcode (barcode),
    FOREIGN KEY (subscription_id) REFERENCES library_subscription(id) ON DELETE CASCADE,
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. Library Order (acquisitions / purchase orders)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_order (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    vendor_id INT UNSIGNED NULL,
    vendor_name VARCHAR(255) NULL,
    order_date DATE NOT NULL,
    expected_date DATE NULL,
    received_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    order_type VARCHAR(30) NOT NULL DEFAULT 'purchase',
    budget_code VARCHAR(50) NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    shipping DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'ZAR',
    invoice_number VARCHAR(100) NULL,
    invoice_date DATE NULL,
    payment_status VARCHAR(30) NULL DEFAULT 'unpaid',
    shipping_address TEXT NULL,
    notes TEXT NULL,
    approved_by INT UNSIGNED NULL,
    approved_date DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_order_number (order_number),
    KEY idx_vendor (vendor_id),
    KEY idx_status (status),
    KEY idx_date (order_date),
    KEY idx_budget (budget_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. Library Order Line (PO line items)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_order_line (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    library_item_id BIGINT UNSIGNED NULL COMMENT 'Link to catalog record if exists',
    title VARCHAR(500) NOT NULL,
    isbn VARCHAR(17) NULL,
    issn VARCHAR(9) NULL,
    author VARCHAR(255) NULL,
    publisher VARCHAR(255) NULL,
    edition VARCHAR(100) NULL,
    material_type VARCHAR(50) NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    quantity_received SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    received_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'ordered',
    budget_code VARCHAR(50) NULL,
    fund_code VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_order (order_id),
    KEY idx_item (library_item_id),
    KEY idx_isbn (isbn),
    KEY idx_status (status),
    FOREIGN KEY (order_id) REFERENCES library_order(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. Library Budget (fund allocation)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_budget (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) NOT NULL,
    fund_name VARCHAR(255) NOT NULL,
    fiscal_year VARCHAR(9) NOT NULL COMMENT 'e.g. 2025/2026',
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    committed_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'On order',
    spent_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Received/invoiced',
    currency VARCHAR(3) DEFAULT 'ZAR',
    category VARCHAR(50) NULL COMMENT 'monographs, serials, electronic, etc.',
    department VARCHAR(100) NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_code_year (budget_code, fiscal_year),
    KEY idx_year (fiscal_year),
    KEY idx_status (status),
    KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. Interlibrary Loan Requests
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_ill_request (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL,
    direction VARCHAR(20) NOT NULL COMMENT 'borrowing or lending',
    patron_id BIGINT UNSIGNED NULL COMMENT 'Borrowing patron',
    partner_library VARCHAR(255) NOT NULL,
    partner_contact VARCHAR(255) NULL,
    partner_email VARCHAR(255) NULL,
    title VARCHAR(500) NOT NULL,
    author VARCHAR(255) NULL,
    isbn VARCHAR(17) NULL,
    issn VARCHAR(9) NULL,
    publisher VARCHAR(255) NULL,
    publication_year VARCHAR(10) NULL,
    volume_issue VARCHAR(100) NULL,
    pages VARCHAR(50) NULL,
    library_item_id BIGINT UNSIGNED NULL COMMENT 'Our item (if lending)',
    copy_id BIGINT UNSIGNED NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'requested',
    request_date DATE NOT NULL,
    needed_by DATE NULL,
    shipped_date DATE NULL,
    received_date DATE NULL,
    due_date DATE NULL,
    return_date DATE NULL,
    shipping_method VARCHAR(50) NULL,
    tracking_number VARCHAR(100) NULL,
    cost DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_request_number (request_number),
    KEY idx_patron (patron_id),
    KEY idx_status (status),
    KEY idx_direction (direction),
    KEY idx_date (request_date),
    KEY idx_partner (partner_library),
    KEY idx_item (library_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. Library Circulation Settings (per material type loan rules)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_loan_rule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_type VARCHAR(50) NOT NULL,
    patron_type VARCHAR(30) NOT NULL DEFAULT '*' COMMENT '* = all patron types',
    loan_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 14,
    renewal_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 14,
    max_renewals SMALLINT UNSIGNED NOT NULL DEFAULT 2,
    fine_per_day DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    fine_cap DECIMAL(10,2) NULL COMMENT 'Max fine for this type',
    grace_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_loanable TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_type_patron (material_type, patron_type),
    KEY idx_material (material_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. Seed default loan rules
-- ============================================================================

INSERT IGNORE INTO library_loan_rule (material_type, patron_type, loan_period_days, renewal_period_days, max_renewals, fine_per_day, fine_cap, grace_period_days, is_loanable) VALUES
('monograph', '*', 21, 21, 2, 1.00, 50.00, 1, 1),
('serial', '*', 7, 7, 1, 2.00, 50.00, 0, 1),
('volume', '*', 21, 21, 2, 1.00, 50.00, 1, 1),
('issue', '*', 7, 7, 0, 2.00, 30.00, 0, 1),
('article', '*', 7, 7, 1, 1.00, 30.00, 0, 1),
('manuscript', '*', 1, 0, 0, 10.00, 100.00, 0, 0),
('map', '*', 7, 7, 1, 2.00, 50.00, 0, 1),
('pamphlet', '*', 14, 14, 2, 0.50, 20.00, 1, 1),
('score', '*', 14, 14, 2, 1.00, 50.00, 1, 1),
('electronic', '*', 0, 0, 0, 0.00, NULL, 0, 0),
('chapter', '*', 7, 7, 1, 1.00, 30.00, 0, 1);

-- ============================================================================
-- 15. Dropdown seed data for new taxonomies
-- ============================================================================

-- Patron types
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('patron_type', 'Patron Type', 'core', 'student', 'Student', 10, 0, 1),
('patron_type', 'Patron Type', 'core', 'staff', 'Staff', 20, 0, 1),
('patron_type', 'Patron Type', 'core', 'faculty', 'Faculty', 30, 0, 1),
('patron_type', 'Patron Type', 'core', 'public', 'Public', 40, 1, 1),
('patron_type', 'Patron Type', 'core', 'researcher', 'Researcher', 50, 0, 1),
('patron_type', 'Patron Type', 'core', 'institutional', 'Institutional', 60, 0, 1),
('patron_type', 'Patron Type', 'core', 'child', 'Child (Under 18)', 70, 0, 1),
('patron_type', 'Patron Type', 'core', 'honorary', 'Honorary Member', 80, 0, 1);

-- Borrowing status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('borrowing_status', 'Borrowing Status', 'core', 'active', 'Active', '#4caf50', 10, 1, 1),
('borrowing_status', 'Borrowing Status', 'core', 'suspended', 'Suspended', '#ff9800', 20, 0, 1),
('borrowing_status', 'Borrowing Status', 'core', 'expired', 'Expired', '#9e9e9e', 30, 0, 1),
('borrowing_status', 'Borrowing Status', 'core', 'blocked', 'Blocked (Fines)', '#f44336', 40, 0, 1),
('borrowing_status', 'Borrowing Status', 'core', 'inactive', 'Inactive', '#607d8b', 50, 0, 1);

-- Checkout status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('checkout_status', 'Checkout Status', 'core', 'active', 'Checked Out', '#2196f3', 10, 1, 1),
('checkout_status', 'Checkout Status', 'core', 'returned', 'Returned', '#4caf50', 20, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'overdue', 'Overdue', '#f44336', 30, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'lost', 'Lost', '#9c27b0', 40, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'claimed_returned', 'Claimed Returned', '#ff9800', 50, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'damaged', 'Returned Damaged', '#795548', 60, 0, 1);

-- Hold status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('hold_status', 'Hold Status', 'core', 'pending', 'Pending', '#ff9800', 10, 1, 1),
('hold_status', 'Hold Status', 'core', 'available', 'Available for Pickup', '#4caf50', 20, 0, 1),
('hold_status', 'Hold Status', 'core', 'fulfilled', 'Fulfilled', '#2196f3', 30, 0, 1),
('hold_status', 'Hold Status', 'core', 'expired', 'Expired', '#9e9e9e', 40, 0, 1),
('hold_status', 'Hold Status', 'core', 'cancelled', 'Cancelled', '#607d8b', 50, 0, 1);

-- Fine type
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('fine_type', 'Fine Type', 'finance', 'overdue', 'Overdue Fine', 10, 1, 1),
('fine_type', 'Fine Type', 'finance', 'lost_item', 'Lost Item Replacement', 20, 0, 1),
('fine_type', 'Fine Type', 'finance', 'damaged', 'Damage Fee', 30, 0, 1),
('fine_type', 'Fine Type', 'finance', 'processing', 'Processing Fee', 40, 0, 1),
('fine_type', 'Fine Type', 'finance', 'replacement_card', 'Card Replacement', 50, 0, 1),
('fine_type', 'Fine Type', 'finance', 'ill_fee', 'ILL Service Fee', 60, 0, 1);

-- Fine status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('fine_status', 'Fine Status', 'finance', 'outstanding', 'Outstanding', '#f44336', 10, 1, 1),
('fine_status', 'Fine Status', 'finance', 'paid', 'Paid', '#4caf50', 20, 0, 1),
('fine_status', 'Fine Status', 'finance', 'partial', 'Partially Paid', '#ff9800', 30, 0, 1),
('fine_status', 'Fine Status', 'finance', 'waived', 'Waived', '#9e9e9e', 40, 0, 1),
('fine_status', 'Fine Status', 'finance', 'referred', 'Referred to Collections', '#9c27b0', 50, 0, 1);

-- Copy status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('copy_status', 'Copy Status', 'core', 'available', 'Available', '#4caf50', 10, 1, 1),
('copy_status', 'Copy Status', 'core', 'checked_out', 'Checked Out', '#2196f3', 20, 0, 1),
('copy_status', 'Copy Status', 'core', 'on_hold', 'On Hold', '#ff9800', 30, 0, 1),
('copy_status', 'Copy Status', 'core', 'in_transit', 'In Transit', '#00bcd4', 40, 0, 1),
('copy_status', 'Copy Status', 'core', 'in_processing', 'In Processing', '#795548', 50, 0, 1),
('copy_status', 'Copy Status', 'core', 'in_repair', 'In Repair', '#e91e63', 60, 0, 1),
('copy_status', 'Copy Status', 'core', 'missing', 'Missing', '#9c27b0', 70, 0, 1),
('copy_status', 'Copy Status', 'core', 'lost', 'Lost', '#f44336', 80, 0, 1),
('copy_status', 'Copy Status', 'core', 'withdrawn', 'Withdrawn', '#9e9e9e', 90, 0, 1),
('copy_status', 'Copy Status', 'core', 'reference', 'Reference Only', '#3f51b5', 100, 0, 1),
('copy_status', 'Copy Status', 'core', 'restricted', 'Restricted Access', '#ff5722', 110, 0, 1);

-- Order status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('library_order_status', 'Order Status', 'finance', 'draft', 'Draft', '#9e9e9e', 10, 1, 1),
('library_order_status', 'Order Status', 'finance', 'submitted', 'Submitted', '#2196f3', 20, 0, 1),
('library_order_status', 'Order Status', 'finance', 'approved', 'Approved', '#4caf50', 30, 0, 1),
('library_order_status', 'Order Status', 'finance', 'ordered', 'Ordered', '#00bcd4', 40, 0, 1),
('library_order_status', 'Order Status', 'finance', 'partial', 'Partially Received', '#ff9800', 50, 0, 1),
('library_order_status', 'Order Status', 'finance', 'received', 'Received', '#4caf50', 60, 0, 1),
('library_order_status', 'Order Status', 'finance', 'cancelled', 'Cancelled', '#f44336', 70, 0, 1);

-- Order type
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('library_order_type', 'Order Type', 'finance', 'purchase', 'Purchase', 10, 1, 1),
('library_order_type', 'Order Type', 'finance', 'standing_order', 'Standing Order', 20, 0, 1),
('library_order_type', 'Order Type', 'finance', 'gift', 'Gift/Donation', 30, 0, 1),
('library_order_type', 'Order Type', 'finance', 'exchange', 'Exchange', 40, 0, 1),
('library_order_type', 'Order Type', 'finance', 'deposit', 'Deposit', 50, 0, 1),
('library_order_type', 'Order Type', 'finance', 'approval', 'Approval Plan', 60, 0, 1);

-- Serial issue status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('serial_issue_status', 'Serial Issue Status', 'core', 'expected', 'Expected', '#9e9e9e', 10, 1, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'received', 'Received', '#4caf50', 20, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'missing', 'Missing', '#f44336', 30, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'claimed', 'Claimed', '#ff9800', 40, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'damaged', 'Damaged', '#795548', 50, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'bound', 'Bound', '#3f51b5', 60, 0, 1);

-- Subscription status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('subscription_status', 'Subscription Status', 'core', 'active', 'Active', '#4caf50', 10, 1, 1),
('subscription_status', 'Subscription Status', 'core', 'pending', 'Pending Renewal', '#ff9800', 20, 0, 1),
('subscription_status', 'Subscription Status', 'core', 'cancelled', 'Cancelled', '#f44336', 30, 0, 1),
('subscription_status', 'Subscription Status', 'core', 'expired', 'Expired', '#9e9e9e', 40, 0, 1),
('subscription_status', 'Subscription Status', 'core', 'suspended', 'Suspended', '#795548', 50, 0, 1);

-- ILL status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('ill_status', 'ILL Status', 'core', 'requested', 'Requested', '#9e9e9e', 10, 1, 1),
('ill_status', 'ILL Status', 'core', 'approved', 'Approved', '#2196f3', 20, 0, 1),
('ill_status', 'ILL Status', 'core', 'shipped', 'Shipped', '#00bcd4', 30, 0, 1),
('ill_status', 'ILL Status', 'core', 'received', 'Received', '#4caf50', 40, 0, 1),
('ill_status', 'ILL Status', 'core', 'in_use', 'In Use', '#ff9800', 50, 0, 1),
('ill_status', 'ILL Status', 'core', 'returned', 'Returned', '#8bc34a', 60, 0, 1),
('ill_status', 'ILL Status', 'core', 'overdue', 'Overdue', '#f44336', 70, 0, 1),
('ill_status', 'ILL Status', 'core', 'cancelled', 'Cancelled', '#607d8b', 80, 0, 1),
('ill_status', 'ILL Status', 'core', 'denied', 'Denied', '#9c27b0', 90, 0, 1);

-- ILL direction
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('ill_direction', 'ILL Direction', 'core', 'borrowing', 'Borrowing', 10, 1, 1),
('ill_direction', 'ILL Direction', 'core', 'lending', 'Lending', 20, 0, 1);

-- Payment method
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('payment_method', 'Payment Method', 'finance', 'cash', 'Cash', 10, 1, 1),
('payment_method', 'Payment Method', 'finance', 'card', 'Card Payment', 20, 0, 1),
('payment_method', 'Payment Method', 'finance', 'eft', 'EFT/Bank Transfer', 30, 0, 1),
('payment_method', 'Payment Method', 'finance', 'online', 'Online Payment', 40, 0, 1),
('payment_method', 'Payment Method', 'finance', 'deduction', 'Salary Deduction', 50, 0, 1);

-- Library acquisition method (for items)
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'purchase', 'Purchase', 10, 1, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'donation', 'Donation', 20, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'gift', 'Gift', 30, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'bequest', 'Bequest', 40, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'exchange', 'Exchange', 50, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'deposit', 'Legal Deposit', 60, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'transfer', 'Transfer', 70, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'unknown', 'Unknown', 80, 0, 1);

-- Budget category
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('budget_category', 'Budget Category', 'finance', 'monographs', 'Monographs', 10, 1, 1),
('budget_category', 'Budget Category', 'finance', 'serials', 'Serials & Periodicals', 20, 0, 1),
('budget_category', 'Budget Category', 'finance', 'electronic', 'Electronic Resources', 30, 0, 1),
('budget_category', 'Budget Category', 'finance', 'special_collections', 'Special Collections', 40, 0, 1),
('budget_category', 'Budget Category', 'finance', 'binding', 'Binding & Repair', 50, 0, 1),
('budget_category', 'Budget Category', 'finance', 'ill', 'Interlibrary Loan', 60, 0, 1),
('budget_category', 'Budget Category', 'finance', 'media', 'Audio/Visual Media', 70, 0, 1),
('budget_category', 'Budget Category', 'finance', 'general', 'General', 80, 0, 1);

-- ============================================================================
-- 16. Add column mapping to ahg_dropdown_column_map
-- (ahg_dropdown_column_map registration intentionally omitted here: that table
--  belongs to the dropdown/custom-fields system, not ahgLibraryPlugin.)

-- --- reconcile (migration_library_reconcile_20260528.sql) -------------------
-- ============================================================================
-- Migration: Library schema reconcile (2026-05-28)
-- ============================================================================
-- Brings a lagging AtoM instance (e.g. WDB) up to the column/table state the
-- ahgLibraryPlugin code now requires. Idempotent: guarded ALTERs (MySQL has no
-- ADD COLUMN IF NOT EXISTS) + CREATE TABLE IF NOT EXISTS. Safe to re-run.
--
-- This file covers the additions that are NOT already in a clean standalone
-- migration. To fully reconcile an instance, ALSO run (all idempotent):
--   * migration_frbr_clustering.sql      (FRBR columns + override table — superseded by this file)
--   * migration_counter_sushi.sql        (library_usage_event, library_counter_settings)
--   * migration_sushi_access_log.sql     (library_sushi_access_log)
--   * migration_z3950_sru.sql            (library_z3950_target, library_sru_log, library_z3950_import_log)
--   * the library_kbart_vendor / library_kbart_import_log blocks from install.sql
-- ============================================================================

-- ── library_item: FRBR work-clustering + description ─────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'frbr_work_key');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN frbr_work_key VARCHAR(64) NULL COMMENT ''SHA-256 work identifier, first 20 chars'' AFTER material_type', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'frbr_override_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN frbr_override_type VARCHAR(20) NOT NULL DEFAULT ''none'' COMMENT ''none, force_group, force_split'' AFTER frbr_work_key', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'description');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN description TEXT NULL AFTER frbr_override_type', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- indexes for FRBR (guarded)
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND INDEX_NAME = 'idx_library_item_frbr_work_key');
SET @sql = IF(@idx = 0, 'CREATE INDEX idx_library_item_frbr_work_key ON library_item (frbr_work_key)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND INDEX_NAME = 'idx_library_item_frbr_override');
SET @sql = IF(@idx = 0, 'CREATE INDEX idx_library_item_frbr_override ON library_item (frbr_override_type)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── library_item_creator: primary-creator flag ──────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item_creator' AND COLUMN_NAME = 'is_primary');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item_creator ADD COLUMN is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER name', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── FRBR override table ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS library_item_frbr_override (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id  BIGINT UNSIGNED NOT NULL,
    target_work_key  VARCHAR(64)  NULL COMMENT 'force_group: merge this item INTO the target work key',
    forced_split     TINYINT(1)   DEFAULT 0 COMMENT 'force_split: do NOT cluster this item with any other',
    reason           VARCHAR(500) NULL,
    created_by       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE,
    INDEX idx_target_work_key (target_work_key),
    INDEX idx_library_item_id (library_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --- RDA/authority/EDI (migration_library_rda_authority_edi_20260529.sql) --
-- ============================================================================
-- Migration: Library RDA carrier fields + Authority Control + ILL EDI / Trading
--            Partners  (2026-05-29)
-- ============================================================================
-- Ports the schema deltas from the Heratio (Laravel) ahg-library work of
-- 2026-05-27..29 into the Symfony ahgLibraryPlugin:
--   * library_item            : RDA carrier fields (336/337/338)
--   * library_subject_authority: complete the authority record (lc/rda labels,
--                                 subject_type, vocab_uri/code, uri, linked_count,
--                                 notes, updated_at)
--   * library_item_authority_link : NEW pivot (6XX subject linkage, source_tag)
--   * library_trading_partner : NEW EDI/EANCOM trading-partner registry
--   * library_ill_request     : EDI / ILL-EDI request columns
--
-- Idempotent: guarded ALTERs (MySQL 8 has no ADD COLUMN IF NOT EXISTS) +
-- CREATE TABLE IF NOT EXISTS. Safe to re-run. ENUMs are rendered as
-- VARCHAR(N) + COMMENT per project rule #5 (no ENUM columns).
-- Source (reference): /usr/share/nginx/heratio/packages/ahg-library/database/migrations/
--   2026_05_30_000000_add_rda_carrier_fields_to_library_item.php
--   2026_05_30_000001_create_library_authority_tables.php
--   2026_05_30_000003_create_library_trading_partners_table.php
--   2026_05_30_000004_add_edi_fields_to_library_ill_request_table.php
-- ============================================================================

-- ── library_item: RDA carrier / content type (336$a / 337$a / 338$a) ─────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'content_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN content_type VARCHAR(100) NULL COMMENT ''RDA 336$a content type''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'carrier_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN carrier_type VARCHAR(100) NULL COMMENT ''RDA 337$a carrier type''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'instance_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN instance_type VARCHAR(100) NULL COMMENT ''RDA 338$a media/instance type''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── library_subject_authority: complete to current authority schema ──────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'lc_label');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN lc_label VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'rda_label');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN rda_label VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'authorized_form');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN authorized_form VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'subject_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN subject_type VARCHAR(50) NOT NULL DEFAULT ''topic'' COMMENT ''topic, name, geographic, temporal, genre, title''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'vocab_uri');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN vocab_uri VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'vocab_code');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN vocab_code VARCHAR(50) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'uri');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN uri VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'linked_count');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN linked_count INT UNSIGNED NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'notes');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN notes TEXT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@col = 0, 'ALTER TABLE library_subject_authority ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_subject_authority' AND INDEX_NAME = 'idx_auth_subject_type');
SET @sql = IF(@idx = 0, 'CREATE INDEX idx_auth_subject_type ON library_subject_authority (subject_type)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── library_item_authority_link: 6XX subject linkage pivot (NEW) ─────────────
CREATE TABLE IF NOT EXISTS library_item_authority_link (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id  BIGINT UNSIGNED NOT NULL,
    authority_id     BIGINT UNSIGNED NOT NULL,
    source_tag       VARCHAR(10) NOT NULL DEFAULT '650' COMMENT 'MARC 6XX tag the link came from (600/610/650/651/655...)',
    created_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY item_authority (library_item_id, authority_id),
    INDEX idx_link_authority (authority_id),
    CONSTRAINT fk_lial_item FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE,
    CONSTRAINT fk_lial_authority FOREIGN KEY (authority_id) REFERENCES library_subject_authority(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── library_trading_partner: EDI/EANCOM partner registry (NEW) ───────────────
CREATE TABLE IF NOT EXISTS library_trading_partner (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id                BIGINT UNSIGNED NULL,
    edi_partner_code         VARCHAR(20) NOT NULL,
    edi_type                 VARCHAR(20) NOT NULL DEFAULT 'EANCOM' COMMENT 'EANCOM, X12, UN/EDIFACT, CUSTOM',
    message_profile          VARCHAR(20) NOT NULL DEFAULT 'EANCOM_S93' COMMENT 'EANCOM_S93, EANCOM_S94, X12_850, CUSTOM',
    endpoint_type            VARCHAR(20) NOT NULL DEFAULT 'SFTP' COMMENT 'SFTP, AS2, HTTP_HTTPS, EMAIL, MANUAL',
    endpoint_config          JSON NULL,
    outbound_directory       VARCHAR(255) NOT NULL DEFAULT '/outbox/',
    inbound_directory        VARCHAR(255) NOT NULL DEFAULT '/inbox/',
    acknowledgement_required TINYINT(1) NOT NULL DEFAULT 1,
    test_mode                TINYINT(1) NOT NULL DEFAULT 1,
    last_inbound_at          TIMESTAMP NULL DEFAULT NULL,
    last_outbound_at         TIMESTAMP NULL DEFAULT NULL,
    last_error_at            TIMESTAMP NULL DEFAULT NULL,
    last_error_message       TEXT NULL,
    is_active                TINYINT(1) NOT NULL DEFAULT 1,
    notes                    TEXT NULL,
    created_at               TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_tp_partner_code (edi_partner_code),
    INDEX idx_tp_vendor (vendor_id),
    INDEX idx_tp_edi_active (edi_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── library_ill_request: EDI / ILL-EDI columns ──────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'request_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN request_type VARCHAR(20) NOT NULL DEFAULT ''BORROW'' COMMENT ''BORROW, SUPPLY, PHOTOCOPY, LOAN_RENEWAL, STATUS_CHECK''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'borrowing_protocol');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN borrowing_protocol VARCHAR(10) NOT NULL DEFAULT ''AARC'' COMMENT ''AARC, IFM, BLDSS, RLG, CUSTOM''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'material_type');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN material_type VARCHAR(20) NOT NULL DEFAULT ''BOOK'' COMMENT ''BOOK, SERIAL_ISSUE, CONFERENCE_PAPER, THESIS, PATENT, REPORT, OTHER''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'responder_library_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN responder_library_id BIGINT UNSIGNED NULL COMMENT ''lending library (library_vendors.id when present)''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'trading_partner_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN trading_partner_id BIGINT UNSIGNED NULL COMMENT ''library_trading_partner.id — EDI partner used''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'responder_note');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN responder_note TEXT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'citation');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN citation VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'lender_string');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN lender_string TEXT NULL COMMENT ''Raw ISO-ILL / bibliographic data string from lender''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'edi_message_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN edi_message_id VARCHAR(50) NULL COMMENT ''Cross-ref to EDI interchange sent/received''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'needed_by_date');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN needed_by_date DATE NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'shipping_method');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN shipping_method VARCHAR(50) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'max_renewals');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN max_renewals TINYINT UNSIGNED NOT NULL DEFAULT 2', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'closed_at');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND COLUMN_NAME = 'closed_reason');
SET @sql = IF(@col = 0, 'ALTER TABLE library_ill_request ADD COLUMN closed_reason VARCHAR(200) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_ill_request' AND INDEX_NAME = 'idx_ill_trading_partner');
SET @sql = IF(@idx = 0, 'CREATE INDEX idx_ill_trading_partner ON library_ill_request (trading_partner_id)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
-- --- MARC control fields (migration_marc_control_fields_20260529.sql) ------
-- ============================================================================
-- MARC control-field preservation (#111) — 2026-05-29
-- ============================================================================
-- Preserve the original leader / 005 (last transaction) / 008 (fixed-length
-- data) from imported MARC so export round-trips them instead of regenerating
-- from material_type only. Idempotent guarded ALTERs.
-- ============================================================================

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'marc_leader');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN marc_leader VARCHAR(24) NULL COMMENT ''Preserved MARC leader''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'marc_005');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN marc_005 VARCHAR(16) NULL COMMENT ''Preserved MARC 005 (last transaction date/time)''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'marc_008');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN marc_008 VARCHAR(40) NULL COMMENT ''Preserved MARC 008 (fixed-length data elements)''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
-- --- serial bindery (migration_serial_bindery_20260529.sql) ---------------
-- ============================================================================
-- Serials bindery workflow (#105) — 2026-05-29
-- ============================================================================
-- A bindery batch groups received serial issues sent out for binding, tracked
-- from send to return. library_serial_issue gains bindery_batch_id (the
-- existing bound_volume_id still records the resulting bound volume).
-- Idempotent: CREATE TABLE IF NOT EXISTS + guarded ALTER.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `library_bindery_batch` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_number`  VARCHAR(40) NOT NULL,
  `vendor_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT 'bindery vendor (ahg_vendors.id)',
  `status`        VARCHAR(20) NOT NULL DEFAULT 'sent' COMMENT 'sent, returned, cancelled',
  `sent_date`     DATE DEFAULT NULL,
  `returned_date` DATE DEFAULT NULL,
  `item_count`    INT UNSIGNED NOT NULL DEFAULT 0,
  `notes`         TEXT DEFAULT NULL,
  `created_by`    INT DEFAULT NULL,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bindery_batch_number` (`batch_number`),
  KEY `idx_bindery_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_serial_issue' AND COLUMN_NAME = 'bindery_batch_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_serial_issue ADD COLUMN bindery_batch_id BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_serial_issue' AND INDEX_NAME = 'idx_serial_bindery_batch');
SET @sql = IF(@idx = 0, 'CREATE INDEX idx_serial_bindery_batch ON library_serial_issue (bindery_batch_id)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
-- --- order line/fund (migration_order_line_fund_20260529.sql) -------------
-- ============================================================================
-- Acquisitions fund-split (#104) — 2026-05-29
-- ============================================================================
-- Allocate a single order line across multiple funds. library_order_line keeps
-- its primary fund_code; this table records the split when one is supplied.
-- Idempotent.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `library_order_line_fund` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_line_id` BIGINT UNSIGNED NOT NULL,
  `fund_code`     VARCHAR(50) NOT NULL,
  `amount`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_olf_line` (`order_line_id`),
  KEY `idx_olf_fund` (`fund_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --- ILL status history (migration_ill_status_history_20260529.sql) -------
-- ============================================================================
-- ILL status history (#106) — 2026-05-29
-- ============================================================================
-- Audit trail of ISO 10160/10161 ILL transaction state transitions.
-- Idempotent.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `library_ill_status_history` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ill_request_id` BIGINT UNSIGNED NOT NULL,
  `from_status`    VARCHAR(30) DEFAULT NULL,
  `to_status`      VARCHAR(30) NOT NULL,
  `notes`          TEXT DEFAULT NULL,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_illh_request` (`ill_request_id`),
  KEY `idx_illh_to` (`to_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --- serials/ILL clone (migration_heratio_serials_ill_clone.sql) ----------
-- ahgLibraryPlugin — clone of Heratio's serials / ILL schema (parity).
--
-- Mirrors Heratio packages/ahg-library migrations:
--   2026_06_01_000100 serial_subscription, _000101 prediction,
--   _000102 claim, _000103 binding, _000104 serial_issue binding fields,
--   2026_06_02_000104 library_ill_request (rich), 2026_05_30_000004 EDI fields.
--
-- New tables use CREATE TABLE IF NOT EXISTS (idempotent). The ALTER blocks at
-- the bottom are RUN-ONCE (MySQL has no ADD COLUMN IF NOT EXISTS) — skip a
-- statement if the column already exists. Indexes (not hard FKs) match Heratio.

-- ── Serials ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `library_serial_subscription` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`          BIGINT UNSIGNED NOT NULL,
    `subscription_start` DATE NULL,
    `subscription_end`   DATE NULL,
    `subscription_cost`  DECIMAL(10,2) NULL,
    `notification_email` VARCHAR(255) NULL,
    `auto_claim_max`     TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `notes`              TEXT NULL,
    `created_at`         TIMESTAMP NULL,
    `updated_at`         TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `serial_id_unique` (`serial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_serial_prediction` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`     BIGINT UNSIGNED NOT NULL,
    `volume`        VARCHAR(32) NOT NULL DEFAULT '',
    `issue_number`  VARCHAR(32) NOT NULL DEFAULT '',
    `expected_date` DATE NULL,
    `days_until`    INT NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_serial_prediction_serial` (`serial_id`),
    KEY `idx_library_serial_prediction_expected` (`expected_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_claim` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`  BIGINT UNSIGNED NOT NULL,
    `issue_id`   BIGINT UNSIGNED NULL,
    `claimed_at` TIMESTAMP NULL,
    `claimed_by` VARCHAR(255) NULL,
    `reason`     TEXT NULL,
    `status`     VARCHAR(32) NOT NULL DEFAULT 'open' COMMENT 'open, sent, resolved, cancelled',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_claim_serial` (`serial_id`),
    KEY `idx_library_claim_issue` (`issue_id`),
    KEY `idx_library_claim_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_binding` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `serial_id`    BIGINT UNSIGNED NOT NULL,
    `volume_range` VARCHAR(120) NOT NULL DEFAULT '',
    `status`       VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending, at_bindery, bound, shelved',
    `bound_at`     DATE NULL,
    `location`     VARCHAR(255) NULL,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_binding_serial` (`serial_id`),
    KEY `idx_library_binding_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ILL ──────────────────────────────────────────────────────────────────────
-- NOTE: NOT cloned. On verification the PSIS ILLService is already functional —
-- it has its own complete ISO 10160/10161 state machine (start state
-- 'submitted'), status is plain VARCHAR(30) (no enum/FK), and every column it
-- writes (incl. needed_by_date) already exists. Cloning Heratio's ILL would
-- regress the richer PSIS implementation, so the ILL CREATE/ALTERs are dropped.
-- (The earlier audit over-flagged ILL by comparing to Heratio's vocabulary.)
-- The block below is retained only as documentation of Heratio's schema.
/*
CREATE TABLE IF NOT EXISTS `library_ill_request` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ill_number`           VARCHAR(50) NOT NULL,
    `type`                 VARCHAR(20) NOT NULL DEFAULT 'borrow' COMMENT 'borrow, lend',
    `request_type`         VARCHAR(20) NOT NULL DEFAULT 'BORROW',
    `borrowing_protocol`   VARCHAR(20) NOT NULL DEFAULT 'AARC',
    `material_type`        VARCHAR(30) NOT NULL DEFAULT 'BOOK',
    `title`                VARCHAR(500) NOT NULL DEFAULT '',
    `author`               VARCHAR(255) NOT NULL DEFAULT '',
    `isbn`                 VARCHAR(32) NULL,
    `issn`                 VARCHAR(32) NULL,
    `volume`               VARCHAR(64) NULL,
    `issue`                VARCHAR(64) NULL,
    `pages`                VARCHAR(64) NULL,
    `citation`             VARCHAR(500) NULL,
    `lender_string`        TEXT NULL,
    `edition`              VARCHAR(100) NULL,
    `publication_year`     VARCHAR(10) NULL,
    `library_name`         VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Counterparty library',
    `library_symbol`       VARCHAR(50) NULL,
    `requester_library_id` BIGINT UNSIGNED NULL,
    `responder_library_id` BIGINT UNSIGNED NULL,
    `trading_partner_id`   BIGINT UNSIGNED NULL,
    `patron_id`            BIGINT UNSIGNED NULL COMMENT 'FK library_patron (borrow direction)',
    `request_date`         DATE NULL,
    `needed_by_date`       DATE NULL,
    `due_date`             DATE NULL,
    `status`               VARCHAR(32) NOT NULL DEFAULT 'pending',
    `edi_message_id`       VARCHAR(50) NULL,
    `closed_at`            TIMESTAMP NULL,
    `closed_reason`        VARCHAR(200) NULL,
    `renewal_count`        INT UNSIGNED NOT NULL DEFAULT 0,
    `max_renewals`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `cost_amount`          DECIMAL(10,2) NULL,
    `cost_currency`        VARCHAR(3) NULL,
    `shipping_method`      VARCHAR(50) NULL,
    `tracking_number`      VARCHAR(100) NULL,
    `requester_note`       TEXT NULL,
    `responder_note`       TEXT NULL,
    `staff_note`           TEXT NULL,
    `notes`                TEXT NULL,
    `opac_suppress`        TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`           TIMESTAMP NULL,
    `updated_at`           TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_library_ill_number` (`ill_number`),
    KEY `idx_ill_status` (`status`),
    KEY `idx_ill_patron` (`patron_id`),
    KEY `idx_ill_partner` (`trading_partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

-- ── RUN-ONCE ALTERs (serials only) ───────────────────────────────────────────
-- library_serial_issue binding fields (Heratio _000104). On PSIS shelf_location
-- and bound_at already exist; only binding_id may be missing. Run individually;
-- ignore "Duplicate column" errors.
-- guarded binding_id (was raw non-idempotent ALTER at tail of the migration):
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_serial_issue' AND COLUMN_NAME='binding_id');
SET @s := IF(@c=0, 'ALTER TABLE library_serial_issue ADD COLUMN binding_id BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='library_serial_issue' AND INDEX_NAME='idx_library_serial_issue_binding');
SET @s := IF(@c=0, 'ALTER TABLE library_serial_issue ADD INDEX idx_library_serial_issue_binding (binding_id)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
-- --- COUNTER/SUSHI (migration_counter_sushi.sql) --------------------------
-- Migration: library_usage_event + SUSHI settings
-- COUNTER R5 + SUSHI 5.0 support (issue #96)
-- Adds usage event capture, report settings, and SUSHI configuration

-- ── Usage Event table ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS library_usage_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NULL,
    patron_id BIGINT UNSIGNED NULL,
    event_type ENUM('opac_view','link_click','ir_access','search','export') NOT NULL,
    metadata JSON DEFAULT NULL COMMENT 'e.g. {"search_terms":"...","result_position":1,"format":"pdf"}',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_item (library_item_id),
    INDEX idx_patron (patron_id),
    INDEX idx_created (created_at),
    INDEX idx_type_date (event_type, created_at),
    CONSTRAINT fk_usage_item FOREIGN KEY (library_item_id) REFERENCES library_item(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_usage_patron FOREIGN KEY (patron_id) REFERENCES library_patron(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── COUNTER / SUSHI Settings table ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS library_counter_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed SUSHI default keys (values set via admin UI / API)
INSERT INTO library_counter_settings (setting_key, setting_value) VALUES
    ('sushi_url',               NULL),
    ('sushi_api_key',          NULL),
    ('sushi_requestor_id',      NULL),
    ('sushi_customer_id',       NULL),
    ('sushi_requestor_name',   NULL),
    ('sushi_requestor_email',  NULL),
    ('counter_report_types',   'TR_J1,DR,PR,IR,TR_J3')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
-- --- SUSHI access log (migration_sushi_access_log.sql) --------------------
-- Migration: library_sushi_access_log
-- Tracks all SUSHI harvest requests (inbound from vendors + outbound to providers)
-- Powers the SUSHI settings admin UI access-log tab

CREATE TABLE IF NOT EXISTS library_sushi_access_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requestor_id    VARCHAR(255) NULL COMMENT 'SUSHI X-Requestor-Id header',
    customer_id     VARCHAR(255) NULL COMMENT 'SUSHI X-Customer-Id header',
    report_type     VARCHAR(20)  NOT NULL COMMENT 'TR_J1, DR, PR, IR, TR_J3',
    period_begin    DATE         NULL,
    period_end      DATE         NULL,
    status_code     SMALLINT    NULL COMMENT 'HTTP status code returned',
    records_returned INT UNSIGNED DEFAULT 0 COMMENT 'Number of usage records in response',
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sushi_log_report   (report_type),
    INDEX idx_sushi_log_customer (customer_id),
    INDEX idx_sushi_log_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;-- --- Z39.50 server (migration_z3950_server_20260601.sql) ------------------
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
-- --- Z39.50/SRU (migration_z3950_sru.sql) ---------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;-- --- ONIX ingest (onix_ingest.sql) ----------------------------------------
-- ahgLibraryPlugin — ONIX ingestion (clone of Heratio library_onix_ingest).
-- Parse + validate publisher ONIX feeds into a review queue before commit.

CREATE TABLE IF NOT EXISTS `library_onix_ingest` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename`       VARCHAR(255) NULL,
    `source`         VARCHAR(20) NOT NULL DEFAULT 'file' COMMENT 'file, api, paste',
    `onix_version`   VARCHAR(8) NULL COMMENT '3.0, 2.1',
    `status`         VARCHAR(20) NOT NULL DEFAULT 'parsed' COMMENT 'parsed, committed, failed',
    `record_count`   INT UNSIGNED NOT NULL DEFAULT 0,
    `valid_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `error_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `imported_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `order_id`       BIGINT UNSIGNED NULL,
    `notes`          TEXT NULL,
    `created_by`     INT UNSIGNED NULL,
    `created_at`     DATETIME NULL,
    `updated_at`     DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_onix_status` (`status`),
    KEY `idx_onix_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_onix_ingest_line` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ingest_id`       BIGINT UNSIGNED NOT NULL,
    `product_ref`     VARCHAR(255) NULL COMMENT 'ONIX RecordReference',
    `isbn`            VARCHAR(20) NULL,
    `issn`            VARCHAR(20) NULL,
    `title`           VARCHAR(500) NULL,
    `subtitle`        VARCHAR(500) NULL,
    `author`          VARCHAR(500) NULL,
    `publisher`       VARCHAR(255) NULL,
    `pub_year`        VARCHAR(8) NULL,
    `edition`         VARCHAR(100) NULL,
    `material_type`   VARCHAR(50) NULL,
    `price`           DECIMAL(12,2) NULL,
    `currency`        VARCHAR(8) NULL,
    `supplier`        VARCHAR(255) NULL,
    `status`          VARCHAR(20) NOT NULL DEFAULT 'parsed' COMMENT 'parsed, valid, invalid, duplicate, imported, skipped',
    `error`           VARCHAR(1000) NULL,
    `library_item_id` BIGINT UNSIGNED NULL,
    `order_line_id`   BIGINT UNSIGNED NULL,
    `raw`             LONGTEXT NULL,
    `created_at`      DATETIME NULL,
    `updated_at`      DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_onixline_ingest` (`ingest_id`),
    KEY `idx_onixline_status` (`status`),
    KEY `idx_onixline_isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
