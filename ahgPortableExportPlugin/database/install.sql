-- =====================================================
-- ahgPortableExportPlugin Database Schema
-- =====================================================

-- Export job tracking
CREATE TABLE IF NOT EXISTS portable_export (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    scope_type ENUM('all','fonds','repository','custom') NOT NULL DEFAULT 'all',
    scope_slug VARCHAR(255) DEFAULT NULL,
    scope_repository_id INT DEFAULT NULL,
    mode ENUM('read_only','editable') DEFAULT 'read_only',
    include_objects TINYINT(1) DEFAULT 1,
    include_masters TINYINT(1) DEFAULT 0,
    include_thumbnails TINYINT(1) DEFAULT 1,
    include_references TINYINT(1) DEFAULT 1,
    branding JSON DEFAULT NULL,
    culture VARCHAR(16) DEFAULT 'en',
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    progress INT DEFAULT 0,
    total_descriptions INT DEFAULT 0,
    total_objects INT DEFAULT 0,
    output_path VARCHAR(1024) DEFAULT NULL,
    output_size BIGINT UNSIGNED DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
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
