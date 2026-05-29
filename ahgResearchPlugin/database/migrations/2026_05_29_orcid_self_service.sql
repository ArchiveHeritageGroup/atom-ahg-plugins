-- ============================================================================
-- ORCID self-service (Heratio #102 parity) — 2026-05-29
-- ============================================================================
-- Per-researcher ORCID OAuth client credentials (so each researcher can run
-- Connect & Sync with their own ORCID app, no admin/.env wall), plus a
-- last_profile_synced_at marker for tokenless Pull-profile.
--
-- Idempotent: CREATE TABLE IF NOT EXISTS + guarded ALTER (MySQL 8 has no
-- ADD COLUMN IF NOT EXISTS). research_orcid_link already carries
-- last_synced_at / last_works_count / last_error.
-- ============================================================================

CREATE TABLE IF NOT EXISTS researcher_orcid_credential (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    researcher_id           BIGINT UNSIGNED NOT NULL,
    client_id               VARCHAR(100) NOT NULL,
    client_secret_encrypted TEXT NULL COMMENT 'AES-256-CBC, base64(iv+ciphertext)',
    redirect_uri            VARCHAR(500) NULL,
    api_base                VARCHAR(100) NULL COMMENT 'e.g. https://pub.orcid.org or https://api.orcid.org',
    created_at              DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orcid_cred_researcher (researcher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- research_orcid_link: marker for the last tokenless profile pull.
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'research_orcid_link' AND COLUMN_NAME = 'last_profile_synced_at');
SET @sql = IF(@col = 0, 'ALTER TABLE research_orcid_link ADD COLUMN last_profile_synced_at DATETIME NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
