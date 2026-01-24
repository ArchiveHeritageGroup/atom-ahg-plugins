-- ahgMultiTenantPlugin Installation SQL
-- This plugin uses the existing ahg_settings table for storage.
-- No new tables are required.

-- Ensure ahg_settings table exists (should already exist from ahgCorePlugin)
CREATE TABLE IF NOT EXISTS ahg_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'float') DEFAULT 'string',
    setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
    description VARCHAR(500),
    is_sensitive TINYINT(1) DEFAULT 0,
    updated_by INT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_setting_group (setting_group),
    FOREIGN KEY (updated_by) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for tenant settings lookups
-- CREATE INDEX IF NOT EXISTS idx_tenant_settings ON ahg_settings (setting_key) WHERE setting_key LIKE 'tenant_repo_%';
-- Note: MySQL doesn't support partial indexes, so we rely on the existing UNIQUE index on setting_key

-- Settings format for multi-tenancy:
-- tenant_repo_{repository_id}_super_users = "5,12,18" (comma-separated user IDs)
-- tenant_repo_{repository_id}_users = "22,25,30" (comma-separated user IDs)
-- tenant_repo_{repository_id}_primary_color = "#336699"
-- tenant_repo_{repository_id}_secondary_color = "#6c757d"
-- tenant_repo_{repository_id}_header_bg_color = "#212529"
-- tenant_repo_{repository_id}_header_text_color = "#ffffff"
-- tenant_repo_{repository_id}_link_color = "#0d6efd"
-- tenant_repo_{repository_id}_button_color = "#198754"
-- tenant_repo_{repository_id}_logo = "/uploads/tenants/{repository_id}/logo.png"
-- tenant_repo_{repository_id}_custom_css = "..."
