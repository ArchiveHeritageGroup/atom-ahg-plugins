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

-- No menu row: the plugin registers its menu entry at runtime with
-- AhgNav::register('manage', ...), so it disappears when the plugin is disabled
-- (catalog #273).

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
