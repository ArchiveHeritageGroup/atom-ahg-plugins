-- =====================================================
-- ahgPortableExportPlugin Database Schema
-- =====================================================

-- Export job tracking
CREATE TABLE IF NOT EXISTS portable_export (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    scope_type VARCHAR(20) NOT NULL DEFAULT 'all' COMMENT 'all, fonds, repository, custom',
    scope_slug VARCHAR(255) DEFAULT NULL,
    scope_repository_id INT DEFAULT NULL,
    scope_items JSON DEFAULT NULL,
    mode VARCHAR(20) DEFAULT 'read_only' COMMENT 'read_only, editable, archive',
    include_objects TINYINT(1) DEFAULT 1,
    include_masters TINYINT(1) DEFAULT 0,
    include_thumbnails TINYINT(1) DEFAULT 1,
    include_references TINYINT(1) DEFAULT 1,
    branding JSON DEFAULT NULL,
    culture VARCHAR(16) DEFAULT 'en',
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, processing, completed, failed',
    progress INT DEFAULT 0,
    total_descriptions INT DEFAULT 0,
    total_objects INT DEFAULT 0,
    output_path VARCHAR(1024) DEFAULT NULL,
    output_size BIGINT UNSIGNED DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portable_export_user (user_id),
    INDEX idx_portable_export_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Export download tokens (secure sharing)
CREATE TABLE IF NOT EXISTS portable_export_token (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    export_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    download_count INT DEFAULT 0,
    max_downloads INT DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (export_id) REFERENCES portable_export(id) ON DELETE CASCADE,
    INDEX idx_portable_export_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Admin menu entry: Admin > Portable Export
-- Inserts as last child of the Admin menu node (name='admin')
-- Uses MPTT: shift rgt values to make room, then insert
-- =====================================================
SET @admin_rgt = (SELECT rgt FROM menu WHERE name = 'admin' LIMIT 1);

-- Only insert if not already present
SET @exists = (SELECT COUNT(*) FROM menu WHERE name = 'portableExport');

-- Make room in the nested set: shift nodes to the right
UPDATE menu SET rgt = rgt + 2 WHERE rgt >= @admin_rgt AND @exists = 0;
UPDATE menu SET lft = lft + 2 WHERE lft > @admin_rgt AND @exists = 0;

-- Insert the menu node as last child of Admin
INSERT INTO menu (parent_id, name, path, lft, rgt, created_at, updated_at, source_culture, serial_number)
SELECT id, 'portableExport', 'portableExport/index', @admin_rgt, @admin_rgt + 1, NOW(), NOW(), 'en', 0
FROM menu WHERE name = 'admin' AND @exists = 0
LIMIT 1;

-- Insert the i18n label
INSERT INTO menu_i18n (id, culture, label, description)
SELECT m.id, 'en', 'Portable Export', 'Export catalogue to CD/USB/ZIP for offline viewing'
FROM menu m WHERE m.name = 'portableExport' AND NOT EXISTS (
    SELECT 1 FROM menu_i18n mi WHERE mi.id = m.id AND mi.culture = 'en'
);

-- =====================================================
-- Import job tracking
-- =====================================================
CREATE TABLE IF NOT EXISTS portable_import (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    source_url VARCHAR(500) DEFAULT NULL,
    source_version VARCHAR(50) DEFAULT NULL,
    archive_path VARCHAR(1024) DEFAULT NULL,
    mode VARCHAR(20) DEFAULT 'merge' COMMENT 'merge, replace, dry_run',
    entity_types JSON DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, validating, validated, importing, completed, failed',
    progress INT DEFAULT 0,
    total_entities INT DEFAULT 0,
    imported_entities INT DEFAULT 0,
    skipped_entities INT DEFAULT 0,
    error_count INT DEFAULT 0,
    id_mapping JSON DEFAULT NULL,
    error_log TEXT DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portable_import_user (user_id),
    INDEX idx_portable_import_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Settings defaults for portable export
-- =====================================================
INSERT IGNORE INTO ahg_settings (setting_key, setting_value, setting_group, created_at, updated_at)
VALUES
('portable_export_enabled', 'true', 'portable_export', NOW(), NOW()),
('portable_export_retention_days', '30', 'portable_export', NOW(), NOW()),
('portable_export_max_size_mb', '2048', 'portable_export', NOW(), NOW()),
('portable_export_default_mode', 'read_only', 'portable_export', NOW(), NOW()),
('portable_export_include_objects', 'true', 'portable_export', NOW(), NOW()),
('portable_export_include_thumbnails', 'true', 'portable_export', NOW(), NOW()),
('portable_export_include_references', 'true', 'portable_export', NOW(), NOW()),
('portable_export_include_masters', 'false', 'portable_export', NOW(), NOW()),
('portable_export_default_culture', 'en', 'portable_export', NOW(), NOW()),
('portable_export_description_button', 'true', 'portable_export', NOW(), NOW()),
('portable_export_clipboard_button', 'true', 'portable_export', NOW(), NOW());

-- ---------------------------------------------------------------------------
-- Merged in from database/migration_destination.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

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

-- ---------------------------------------------------------------------------
-- Merged in from database/migration_disclosure_summary.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

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
INSERT IGNORE INTO ahg_settings (setting_key, setting_value, setting_group, created_at, updated_at)
SELECT 'portable_export_include_unpublished', 'false', 'portable_export', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM ahg_settings WHERE setting_key = 'portable_export_include_unpublished'
);
