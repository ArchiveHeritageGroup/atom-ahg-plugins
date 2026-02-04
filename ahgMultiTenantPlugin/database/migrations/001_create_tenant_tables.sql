-- Migration: Create heritage_tenant and heritage_tenant_user tables
-- Version: 1.1.0
-- Date: 2026-02-02
-- Description: Adds dedicated tenant management tables for multi-tenancy

-- ============================================================================
-- Table: heritage_tenant
-- Stores tenant (organization) information for multi-tenancy
-- ============================================================================
CREATE TABLE IF NOT EXISTS heritage_tenant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique tenant code/slug',
    name VARCHAR(255) NOT NULL COMMENT 'Display name of the tenant',
    domain VARCHAR(255) DEFAULT NULL COMMENT 'Custom domain for the tenant',
    subdomain VARCHAR(100) DEFAULT NULL COMMENT 'Subdomain for the tenant',
    settings JSON DEFAULT NULL COMMENT 'Tenant-specific settings override',
    status ENUM('active', 'suspended', 'trial') NOT NULL DEFAULT 'trial' COMMENT 'Tenant status',
    trial_ends_at DATETIME DEFAULT NULL COMMENT 'Trial expiration date',
    suspended_at DATETIME DEFAULT NULL COMMENT 'When tenant was suspended',
    suspended_reason VARCHAR(500) DEFAULT NULL COMMENT 'Reason for suspension',
    repository_id INT DEFAULT NULL COMMENT 'Link to AtoM repository (optional)',
    contact_name VARCHAR(255) DEFAULT NULL COMMENT 'Primary contact name',
    contact_email VARCHAR(255) DEFAULT NULL COMMENT 'Primary contact email',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL COMMENT 'User who created the tenant',

    INDEX idx_tenant_code (code),
    INDEX idx_tenant_status (status),
    INDEX idx_tenant_domain (domain),
    INDEX idx_tenant_subdomain (subdomain),
    INDEX idx_tenant_repository (repository_id),

    FOREIGN KEY (repository_id) REFERENCES repository(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: heritage_tenant_user
-- Maps users to tenants with roles
-- ============================================================================
CREATE TABLE IF NOT EXISTS heritage_tenant_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'super_user', 'editor', 'contributor', 'viewer') NOT NULL DEFAULT 'viewer' COMMENT 'User role within tenant',
    is_primary TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is this the users primary tenant',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT DEFAULT NULL COMMENT 'User who assigned this user',

    UNIQUE KEY uk_tenant_user (tenant_id, user_id),
    INDEX idx_tenant_user_tenant (tenant_id),
    INDEX idx_tenant_user_user (user_id),
    INDEX idx_tenant_user_role (role),

    FOREIGN KEY (tenant_id) REFERENCES heritage_tenant(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: heritage_tenant_settings_override
-- Stores per-tenant settings that override global defaults
-- ============================================================================
CREATE TABLE IF NOT EXISTS heritage_tenant_settings_override (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT DEFAULT NULL,

    UNIQUE KEY uk_tenant_setting (tenant_id, setting_key),
    INDEX idx_tenant_setting_key (setting_key),

    FOREIGN KEY (tenant_id) REFERENCES heritage_tenant(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Migration: Migrate existing data from ahg_settings to new tables
-- ============================================================================

-- This procedure migrates existing tenant data from ahg_settings
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS migrate_tenant_data()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_repo_id INT;
    DECLARE v_repo_name VARCHAR(255);
    DECLARE v_repo_identifier VARCHAR(255);
    DECLARE v_tenant_id INT;

    -- Cursor for repositories that have tenant settings
    DECLARE repo_cursor CURSOR FOR
        SELECT DISTINCT
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(setting_key, '_', 3), '_', -1) AS UNSIGNED) as repo_id
        FROM ahg_settings
        WHERE setting_key LIKE 'tenant_repo_%'
        AND setting_key REGEXP 'tenant_repo_[0-9]+_';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN repo_cursor;

    read_loop: LOOP
        FETCH repo_cursor INTO v_repo_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Get repository name
        SELECT ai.authorized_form_of_name, r.identifier
        INTO v_repo_name, v_repo_identifier
        FROM repository r
        LEFT JOIN actor_i18n ai ON r.id = ai.id AND ai.culture = 'en'
        WHERE r.id = v_repo_id
        LIMIT 1;

        -- Skip if repository doesn't exist
        IF v_repo_name IS NOT NULL THEN
            -- Check if tenant already exists for this repository
            SELECT id INTO v_tenant_id
            FROM heritage_tenant
            WHERE repository_id = v_repo_id
            LIMIT 1;

            -- Create tenant if doesn't exist
            IF v_tenant_id IS NULL THEN
                INSERT INTO heritage_tenant (code, name, repository_id, status, created_at)
                VALUES (
                    COALESCE(v_repo_identifier, CONCAT('tenant-', v_repo_id)),
                    COALESCE(v_repo_name, CONCAT('Tenant ', v_repo_id)),
                    v_repo_id,
                    'active',
                    NOW()
                );
                SET v_tenant_id = LAST_INSERT_ID();
            END IF;

            -- Migrate super users
            INSERT IGNORE INTO heritage_tenant_user (tenant_id, user_id, role, assigned_at)
            SELECT
                v_tenant_id,
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(setting_value, ',', numbers.n), ',', -1) AS UNSIGNED),
                'super_user',
                NOW()
            FROM
                (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
                 UNION ALL SELECT 9 UNION ALL SELECT 10) numbers
            INNER JOIN ahg_settings s
                ON s.setting_key = CONCAT('tenant_repo_', v_repo_id, '_super_users')
                AND CHAR_LENGTH(s.setting_value) - CHAR_LENGTH(REPLACE(s.setting_value, ',', '')) >= numbers.n - 1
            WHERE TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(setting_value, ',', numbers.n), ',', -1)) != '';

            -- Migrate regular users
            INSERT IGNORE INTO heritage_tenant_user (tenant_id, user_id, role, assigned_at)
            SELECT
                v_tenant_id,
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(setting_value, ',', numbers.n), ',', -1) AS UNSIGNED),
                'editor',
                NOW()
            FROM
                (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
                 UNION ALL SELECT 9 UNION ALL SELECT 10) numbers
            INNER JOIN ahg_settings s
                ON s.setting_key = CONCAT('tenant_repo_', v_repo_id, '_users')
                AND CHAR_LENGTH(s.setting_value) - CHAR_LENGTH(REPLACE(s.setting_value, ',', '')) >= numbers.n - 1
            WHERE TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(setting_value, ',', numbers.n), ',', -1)) != '';

            -- Migrate branding settings to JSON
            UPDATE heritage_tenant t
            SET t.settings = (
                SELECT JSON_OBJECT(
                    'primary_color', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_primary_color')),
                    'secondary_color', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_secondary_color')),
                    'header_bg_color', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_header_bg_color')),
                    'header_text_color', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_header_text_color')),
                    'link_color', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_link_color')),
                    'button_color', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_button_color')),
                    'logo', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_logo')),
                    'custom_css', (SELECT setting_value FROM ahg_settings WHERE setting_key = CONCAT('tenant_repo_', v_repo_id, '_custom_css'))
                )
            )
            WHERE t.id = v_tenant_id;
        END IF;
    END LOOP;

    CLOSE repo_cursor;
END //

DELIMITER ;

-- Execute migration (commented out - run manually after review)
-- CALL migrate_tenant_data();
-- DROP PROCEDURE IF EXISTS migrate_tenant_data;
