-- Phase M — default retention settings for ahgVersionControlPlugin.
-- Idempotent: ON DUPLICATE KEY on setting_key (unique).

INSERT INTO ahg_settings (setting_key, setting_value, setting_type, setting_group, description, is_sensitive, created_at, updated_at)
VALUES
('version_control.retain_count', '0', 'integer', 'version_control',
 'How many recent versions to keep per entity. 0 = unlimited. v1 baseline is always kept.', 0, NOW(), NOW()),
('version_control.retain_days', '0', 'integer', 'version_control',
 'Keep versions newer than N days. 0 = unlimited. v1 baseline is always kept; recent-N (per retain_count) always kept.', 0, NOW(), NOW()),
('version_control.skip_on_minor_edit', '0', 'boolean', 'version_control',
 'Reserved — if 1, the save listener skips capture when changed_fields is empty. Currently unused; reserved for a future enhancement.', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

SELECT setting_key, setting_value, setting_type, description
FROM ahg_settings
WHERE setting_group='version_control'
ORDER BY setting_key;
