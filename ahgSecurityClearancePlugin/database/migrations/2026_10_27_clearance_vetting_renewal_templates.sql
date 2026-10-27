-- ============================================================================
-- Migration: clearance vetting, renewal, two-factor flag and justification
-- templates (atom-extensions-catalog#314)
-- Date: 2026-10-27
-- Run once on existing installs; new installs get these from install.sql.
-- Adds columns only: no existing column or row is changed.
-- ============================================================================

ALTER TABLE user_security_clearance
    ADD COLUMN vetting_reference VARCHAR(100) DEFAULT NULL COMMENT 'reference of the vetting that supports the clearance' AFTER notes,
    ADD COLUMN vetting_date DATE DEFAULT NULL COMMENT 'date the vetting was completed' AFTER vetting_reference,
    ADD COLUMN vetting_authority VARCHAR(255) DEFAULT NULL COMMENT 'body that carried out the vetting' AFTER vetting_date,
    ADD COLUMN renewal_status VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'none, pending, approved, rejected' AFTER vetting_authority,
    ADD COLUMN renewal_requested_date DATE DEFAULT NULL AFTER renewal_status,
    ADD COLUMN renewal_notes TEXT DEFAULT NULL AFTER renewal_requested_date,
    ADD COLUMN two_factor_verified TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 once the holder has verified two-factor sign-in' AFTER renewal_notes,
    ADD COLUMN two_factor_verified_at DATETIME DEFAULT NULL AFTER two_factor_verified;

ALTER TABLE access_justification_template
    ADD COLUMN category VARCHAR(50) DEFAULT NULL COMMENT 'research, legal, personal, administrative, ...' AFTER name,
    ADD COLUMN popia_ground VARCHAR(50) DEFAULT NULL COMMENT 'POPIA lawful processing ground: consent, legitimate_interest, legal_obligation, public_function, ...' AFTER paia_section,
    ADD COLUMN required_evidence JSON DEFAULT NULL COMMENT 'list of supporting documents to ask for' AFTER popia_ground,
    ADD COLUMN created_by INT DEFAULT NULL COMMENT 'user.id' AFTER is_active,
    ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at;
