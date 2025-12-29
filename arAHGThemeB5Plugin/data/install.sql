-- AHG Theme B5 Plugin - Data Only
-- Version: 1.0.0
-- Tables are created by atom-framework/database/install.sql

-- Register theme plugin
INSERT IGNORE INTO atom_plugin (name, is_enabled, version, category) VALUES
('arAHGThemeB5Plugin', 1, '1.0.0', 'theme');

-- Default AHG Settings
INSERT IGNORE INTO ahg_settings (setting_key, setting_value, setting_type, setting_group) VALUES
('default_sector', 'archive', 'string', 'general'),
('enable_glam_browse', '1', 'boolean', 'general'),
('enable_3d_viewer', '1', 'boolean', 'features'),
('enable_iiif', '1', 'boolean', 'features'),
('research_booking_enabled', '1', 'boolean', 'features'),
('audit_retention_days', '365', 'integer', 'compliance');
