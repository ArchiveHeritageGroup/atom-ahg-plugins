-- =====================================================
-- ahgFtpPlugin - FTP/SFTP Upload for CSV Import
-- No tables needed - config stored in ahg_settings
-- =====================================================

-- Enablement is deliberately NOT done here.
--
-- This previously inserted its own atom_plugin row with is_enabled = 1, so
-- installing the schema also switched the plugin on - and then UPDATEd that row
-- on every re-run. Two problems: an operator never chose to enable it, and a
-- plugin enabled this way is *statically enabled*, which sfPluginAdminPlugin
-- drops from its list (pluginsAction.class.php:44-46). It could not be turned
-- off through the admin UI because it was not shown there.
--
-- Installing schema and deciding to run a plugin are separate acts.

-- =====================================================
-- Menu entry: Import > FTP Upload
-- Inserts as last child of the Import menu node (name='import')
-- Uses MPTT: shift rgt values to make room, then insert
-- =====================================================
SET @import_rgt = (SELECT rgt FROM menu WHERE name = 'import' LIMIT 1);

-- Only insert if not already present
SET @exists = (SELECT COUNT(*) FROM menu WHERE name = 'ftpUpload');

-- Make room in the nested set: shift nodes to the right
UPDATE menu SET rgt = rgt + 2 WHERE rgt >= @import_rgt AND @exists = 0;
UPDATE menu SET lft = lft + 2 WHERE lft > @import_rgt AND @exists = 0;

-- Insert the menu node as last child of Import
INSERT INTO menu (parent_id, name, path, lft, rgt, created_at, updated_at, source_culture, serial_number)
SELECT id, 'ftpUpload', 'ftpUpload/index', @import_rgt, @import_rgt + 1, NOW(), NOW(), 'en', 0
FROM menu WHERE name = 'import' AND @exists = 0
LIMIT 1;

-- Insert the i18n label
INSERT INTO menu_i18n (id, culture, label, description)
SELECT m.id, 'en', 'FTP Upload', 'Upload digital objects via FTP/SFTP for CSV import'
FROM menu m WHERE m.name = 'ftpUpload' AND NOT EXISTS (
    SELECT 1 FROM menu_i18n mi WHERE mi.id = m.id AND mi.culture = 'en'
);

-- =====================================================
-- Default settings (group: ftp)
-- =====================================================
INSERT IGNORE INTO ahg_settings (setting_key, setting_value, setting_group, created_at, updated_at)
VALUES
('ftp_protocol', 'sftp', 'ftp', NOW(), NOW()),
('ftp_host', '', 'ftp', NOW(), NOW()),
('ftp_port', '22', 'ftp', NOW(), NOW()),
('ftp_username', '', 'ftp', NOW(), NOW()),
('ftp_password', '', 'ftp', NOW(), NOW()),
('ftp_remote_path', '/uploads', 'ftp', NOW(), NOW()),
('ftp_passive_mode', 'true', 'ftp', NOW(), NOW());
