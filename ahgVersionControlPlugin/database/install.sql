-- ahgVersionControlPlugin — schema (Phase A)
--
-- Two version tables, one per entity type in the v1 scope (information_object, actor).
-- Snapshot is the full deterministic JSON state including all i18n cultures,
-- access points, taxonomy relations and custom field values.
--
-- BASE ATOM IS NOT MODIFIED. These tables FK to base AtoM tables for read-only
-- referential integrity; the base schema is untouched per project lock.
--
-- Idempotent: safe to re-run. CREATE TABLE IF NOT EXISTS guards both tables.

-- -----------------------------------------------------
-- 1. information_object_version
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS information_object_version (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    information_object_id INT NOT NULL,
    version_number INT NOT NULL COMMENT 'Monotonic per information_object',
    snapshot JSON NOT NULL COMMENT 'Full deterministic state: schema_version + base + i18n + access_points + custom_fields',
    change_summary VARCHAR(500) DEFAULT NULL COMMENT 'Auto-generated summary or user-supplied note',
    changed_fields JSON DEFAULT NULL COMMENT 'List of field names that differ from prior version',
    created_by INT DEFAULT NULL COMMENT 'FK user.id; nullable for system-created versions',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_restore TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if this version was created by a restore action',
    restored_from_version INT DEFAULT NULL COMMENT 'When is_restore=1, the version_number that was restored',
    PRIMARY KEY (id),
    UNIQUE KEY uq_io_version (information_object_id, version_number),
    KEY idx_io (information_object_id),
    KEY idx_created (created_at),
    KEY idx_created_by (created_by),
    CONSTRAINT fk_iov_io FOREIGN KEY (information_object_id)
        REFERENCES information_object(id) ON DELETE CASCADE,
    CONSTRAINT fk_iov_user FOREIGN KEY (created_by)
        REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. actor_version
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS actor_version (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id INT NOT NULL,
    version_number INT NOT NULL COMMENT 'Monotonic per actor',
    snapshot JSON NOT NULL,
    change_summary VARCHAR(500) DEFAULT NULL,
    changed_fields JSON DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_restore TINYINT(1) NOT NULL DEFAULT 0,
    restored_from_version INT DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_actor_version (actor_id, version_number),
    KEY idx_actor (actor_id),
    KEY idx_created (created_at),
    KEY idx_created_by (created_by),
    CONSTRAINT fk_av_actor FOREIGN KEY (actor_id)
        REFERENCES actor(id) ON DELETE CASCADE,
    CONSTRAINT fk_av_user FOREIGN KEY (created_by)
        REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/seed-acl-permissions.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ahgVersionControlPlugin — default ACL permissions (Phase K)
--
-- Idempotent: INSERT IGNORE keyed on (group_id, action) effectively, but
-- acl_permission has no unique constraint on that pair, so we use a NOT EXISTS
-- guard per row.
--
-- AtoM default groups (acl_group):
--   98  anonymous
--   99  authenticated
--   100 administrator   ← gets allow-all via action=NULL row (already seeded by base AtoM)
--   101 editor          ← curatorial role: list + diff + restore (non-classified)
--   102 contributor     ← read-only of version history: list + diff
--   103 translator      ← can see versions exist (for understanding context): list
--
-- restore_classified is GRANTED to editor + administrator only. The Phase J
-- clearance check still requires the user's actual security clearance to
-- match the record's classification level.

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.list', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.list');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.diff', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.diff');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.restore', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.restore');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.restore_classified', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.restore_classified');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 102, NULL, 'version.list', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=102 AND action='version.list');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 102, NULL, 'version.diff', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=102 AND action='version.diff');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 103, NULL, 'version.list', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=103 AND action='version.list');

-- Final report.
SELECT g.id AS group_id, gi.name AS group_name, p.action, p.grant_deny
FROM acl_permission p
JOIN acl_group g ON g.id = p.group_id
LEFT JOIN acl_group_i18n gi ON gi.id = g.id AND gi.culture = 'en'
WHERE p.action LIKE 'version.%'
ORDER BY g.id, p.action;

-- ---------------------------------------------------------------------------
-- Merged in from database/seed-settings.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- Phase M — default retention settings for ahgVersionControlPlugin.
-- Idempotent: ON DUPLICATE KEY on setting_key (unique).

INSERT IGNORE INTO ahg_settings (setting_key, setting_value, setting_type, setting_group, description, is_sensitive, created_at, updated_at)
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
