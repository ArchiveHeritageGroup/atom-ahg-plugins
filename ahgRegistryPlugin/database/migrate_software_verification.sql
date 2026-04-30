-- Add verification audit columns to registry_software (parity with registry_vendor / registry_institution)
-- Required by SoftwareService::verify() and adminSoftwareVerify action.

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registry_software' AND COLUMN_NAME = 'verified_at');
SET @sql := IF(@col = 0, 'ALTER TABLE registry_software ADD COLUMN verified_at DATETIME NULL AFTER is_verified', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registry_software' AND COLUMN_NAME = 'verified_by');
SET @sql := IF(@col = 0, 'ALTER TABLE registry_software ADD COLUMN verified_by INT NULL AFTER verified_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registry_software' AND COLUMN_NAME = 'verification_notes');
SET @sql := IF(@col = 0, 'ALTER TABLE registry_software ADD COLUMN verification_notes TEXT NULL AFTER verified_by', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
