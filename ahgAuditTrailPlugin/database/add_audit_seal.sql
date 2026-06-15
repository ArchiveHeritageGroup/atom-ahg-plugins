-- ahgAuditTrailPlugin — cryptographic seal columns (#5 / DB-audit build-order #5)
--
-- The hash chain (#126, add_audit_chain.sql) already makes ahg_audit_log
-- tamper-EVIDENT. These columns add the tamper-PROOF seal on top:
--   kid       — id of the Ed25519 key that signed the entry
--   seq       — monotonic per-chain ordinal (gap-detectable)
--   signature — base64 detached Ed25519 signature over entry_hash
--   tenant_id — multi-tenant scoping (nullable; PSIS multi-tenancy disabled)
--
-- All nullable/additive — pre-seal rows keep their entry_hash and still verify.
-- Run-once. ALTER on a large table: MySQL 8 ADD COLUMN is INSTANT.

ALTER TABLE `ahg_audit_log`
    ADD COLUMN `kid` VARCHAR(32) NULL COMMENT 'Ed25519 signing key id' AFTER `entry_hash`,
    ADD COLUMN `seq` BIGINT NULL COMMENT 'monotonic per-chain ordinal' AFTER `kid`,
    ADD COLUMN `signature` VARCHAR(128) NULL COMMENT 'base64 detached Ed25519 signature over entry_hash' AFTER `seq`,
    ADD COLUMN `tenant_id` INT NULL COMMENT 'multi-tenant scoping (nullable)' AFTER `signature`;

-- Track the monotonic seq counter on the single chain-state row.
ALTER TABLE `ahg_audit_chain_state`
    ADD COLUMN `last_seq` BIGINT NOT NULL DEFAULT 0 COMMENT 'last issued seq' AFTER `last_audit_id`;
