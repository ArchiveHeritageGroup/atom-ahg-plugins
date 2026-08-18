-- ahgTimeLimitedShareLinkPlugin — schema (Phase A)
--
-- Two tables for time-limited share links on information_object records.
-- BASE ATOM IS NOT MODIFIED. FKs reference base tables for read-only
-- referential integrity only.
--
-- Idempotent: CREATE TABLE IF NOT EXISTS guards both tables.

-- -----------------------------------------------------
-- 1. information_object_share_token
-- One row per issued share link.
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS information_object_share_token (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    information_object_id INT NOT NULL,
    token                 VARCHAR(64) NOT NULL COMMENT 'URL-safe base64; HMAC-derived; unguessable',
    issued_by             INT NOT NULL COMMENT 'FK user.id — the curator/officer who issued the link',
    recipient_email       VARCHAR(255) DEFAULT NULL COMMENT 'Informational only; not enforced as an access gate',
    recipient_note        VARCHAR(500) DEFAULT NULL COMMENT 'Free-text reason captured at issuance',
    expires_at            DATETIME NOT NULL COMMENT 'Cap on link validity',
    max_access            INT UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited within window',
    access_count          INT UNSIGNED NOT NULL DEFAULT 0,
    revoked_at            DATETIME DEFAULT NULL,
    revoked_by            INT DEFAULT NULL COMMENT 'FK user.id — admin or original issuer',
    revoke_reason         VARCHAR(255) DEFAULT NULL,
    classification_level_at_issuance TINYINT UNSIGNED DEFAULT NULL COMMENT 'Snapshot of record classification level when issued',
    issuer_download_at_issuance      TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether the issuer could download the DO at the moment of issuance',
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    KEY idx_io (information_object_id),
    KEY idx_issued_by (issued_by),
    KEY idx_expires (expires_at),
    KEY idx_revoked (revoked_at),
    CONSTRAINT fk_iost_io   FOREIGN KEY (information_object_id) REFERENCES information_object(id) ON DELETE CASCADE,
    CONSTRAINT fk_iost_user FOREIGN KEY (issued_by)             REFERENCES user(id)               ON DELETE CASCADE,
    CONSTRAINT fk_iost_rev  FOREIGN KEY (revoked_by)            REFERENCES user(id)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. information_object_share_access
-- One row per access attempt (granted or denied).
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS information_object_share_access (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    token_id    BIGINT UNSIGNED NOT NULL,
    accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address  VARCHAR(45) DEFAULT NULL,
    user_agent  VARCHAR(500) DEFAULT NULL,
    action      VARCHAR(20) NOT NULL COMMENT 'view | download | denied_expired | denied_revoked | denied_quota | auto_expired',
    PRIMARY KEY (id),
    KEY idx_token (token_id),
    KEY idx_accessed (accessed_at),
    CONSTRAINT fk_iosa_token FOREIGN KEY (token_id) REFERENCES information_object_share_token(id) ON DELETE CASCADE
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

-- ahgTimeLimitedShareLinkPlugin — Phase I seed
--
-- Idempotent ACL grants for the 5 share-link permissions.
-- Group ids (AtoM defaults):
--   100 = administrator  → bypassed in code (AclCheck), no INSERT needed
--   101 = editor
--   102 = contributor
--   103 = translator     → no grants
--
-- Permission matrix:
--   share_link.create                  101, 102
--   share_link.create_classified       (admin only, no group grant)
--   share_link.create_unlimited_expiry (admin only, no group grant)
--   share_link.list_all                101
--   share_link.revoke_others           101
--
-- Run via: mysql archive < seed-acl-permissions.sql

-- editor (101) — create + list_all + revoke_others
INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'share_link.create', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='share_link.create');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'share_link.list_all', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='share_link.list_all');

INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'share_link.revoke_others', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='share_link.revoke_others');

-- contributor (102) — create only
INSERT IGNORE INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 102, NULL, 'share_link.create', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=102 AND action='share_link.create');

-- Final report
SELECT g.id AS group_id, gi.name AS group_name, p.action, p.grant_deny
FROM acl_permission p
JOIN acl_group g ON g.id = p.group_id
LEFT JOIN acl_group_i18n gi ON gi.id = g.id AND gi.culture = 'en'
WHERE p.action LIKE 'share_link.%'
ORDER BY g.id, p.action;
