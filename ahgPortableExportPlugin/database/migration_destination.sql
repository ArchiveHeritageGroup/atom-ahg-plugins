-- Portable export destination options (ported from Heratio): choose between a
-- downloadable ZIP file or an uncompressed dump straight to a folder / mounted
-- drive (for collections too large for a ZIP).
--
-- Run once:  mysql -u root archive < migration_destination.sql
-- Guarded (MySQL 8 has no ADD COLUMN IF NOT EXISTS).

SET @c1 := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'portable_export' AND column_name = 'destination');
SET @d1 := IF(@c1 = 0,
  "ALTER TABLE portable_export ADD COLUMN destination VARCHAR(20) NOT NULL DEFAULT 'zip' COMMENT 'zip, folder' AFTER mode",
  'SELECT 1');
PREPARE s1 FROM @d1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @c2 := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'portable_export' AND column_name = 'destination_path');
SET @d2 := IF(@c2 = 0,
  'ALTER TABLE portable_export ADD COLUMN destination_path VARCHAR(500) NULL AFTER destination',
  'SELECT 1');
PREPARE s2 FROM @d2; EXECUTE s2; DEALLOCATE PREPARE s2;
