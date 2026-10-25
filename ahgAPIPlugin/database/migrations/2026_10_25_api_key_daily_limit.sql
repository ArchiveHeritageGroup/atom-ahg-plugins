-- ============================================================================
-- Migration: API key daily cap and issuer (atom-ahg-plugins#223)
-- Date: 2026-10-25
-- Run once on existing installs; new installs get these from install.sql.
-- ============================================================================

ALTER TABLE ahg_api_key
    ADD COLUMN daily_limit INT DEFAULT NULL COMMENT 'requests per day; NULL = no daily cap' AFTER rate_limit,
    ADD COLUMN issued_by INT DEFAULT NULL COMMENT 'user.id of the administrator who issued the key for its owner' AFTER daily_limit;
