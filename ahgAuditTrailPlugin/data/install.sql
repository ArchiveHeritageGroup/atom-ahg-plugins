-- ahgAuditTrailPlugin - Data Only
-- Version: 1.0.1
-- Tables are created by atom-framework/database/install.sql

-- Register plugin
('ahgAuditTrailPlugin', 1, '1.0.1', 'compliance');

-- Default audit settings
INSERT IGNORE INTO ahg_audit_settings (setting_key, setting_value, description) VALUES
('log_views', '1', 'Log view events'),
('log_downloads', '1', 'Log download events'),
('log_searches', '1', 'Log search events'),
('log_logins', '1', 'Log authentication events'),
('retention_days', '365', 'Days to retain audit logs'),
('anonymize_after_days', '730', 'Days before anonymizing user data');
