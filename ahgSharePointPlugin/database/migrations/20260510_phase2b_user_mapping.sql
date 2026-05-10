-- Phase 2.B — sharepoint_user_mapping table.
--
-- Maps an Azure AD object id (oid claim) to an AtoM user.id. The mapping is
-- consulted on every manual push so the audit trail records the actual SP
-- user, not a service identity.
--
-- Auto-create AtoM users on first push is the default (per locked decision),
-- gated by ahg_settings: setting_group=sharepoint, key=sharepoint_push_user_create_enabled.
--
-- Mirrored in heratio/packages/ahg-sharepoint/database/migrations/.

CREATE TABLE IF NOT EXISTS sharepoint_user_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aad_object_id VARCHAR(64) NOT NULL COMMENT 'AAD oid claim',
    aad_upn VARCHAR(255) DEFAULT NULL COMMENT 'AAD UPN — audit-readable identifier',
    aad_email VARCHAR(255) DEFAULT NULL COMMENT 'AAD email claim',
    atom_user_id INT NOT NULL COMMENT 'FK to user.id',
    created_by VARCHAR(20) NOT NULL DEFAULT 'auto' COMMENT 'auto, manual, admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_aad (aad_object_id),
    KEY idx_user (atom_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
