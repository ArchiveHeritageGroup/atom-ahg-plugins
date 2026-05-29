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
