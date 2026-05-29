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
