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
