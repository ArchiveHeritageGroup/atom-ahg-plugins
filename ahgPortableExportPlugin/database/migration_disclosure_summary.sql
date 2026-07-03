-- #1389 — disclosure gate for portable/offline exports (ported from Heratio).
-- Records which confidentiality gates a portable-export run applied, so the
-- operator can see what was withheld from an offline package.
--
-- Run once:  mysql -u root archive < migration_disclosure_summary.sql
-- (MySQL 8 does not support ADD COLUMN IF NOT EXISTS, so this is guarded by a
--  prepared statement that no-ops when the column already exists.)

SET @col := (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'portable_export'
    AND column_name = 'disclosure_summary'
);
SET @ddl := IF(@col = 0,
  'ALTER TABLE portable_export ADD COLUMN disclosure_summary TEXT NULL AFTER error_message',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Operator override: include unpublished descriptions in offline packages
-- (default OFF — fail-closed). VARCHAR value, not ENUM.
INSERT INTO ahg_settings (setting_key, setting_value, setting_group, created_at, updated_at)
SELECT 'portable_export_include_unpublished', 'false', 'portable_export', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM ahg_settings WHERE setting_key = 'portable_export_include_unpublished'
);
