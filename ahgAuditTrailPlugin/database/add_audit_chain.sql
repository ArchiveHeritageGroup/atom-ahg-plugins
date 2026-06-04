-- #126: cryptographic hash chaining for ahg_audit_log (seal-forward).
-- Historical rows (entry_hash IS NULL) are left as-is; every NEW entry from the
-- seal point on is SHA-256 linked to the previous, making post-seal history
-- tamper-evident.
ALTER TABLE `ahg_audit_log`
  ADD COLUMN `prev_hash` CHAR(64) NULL COMMENT 'SHA-256 entry_hash of the previous chained entry',
  ADD COLUMN `entry_hash` CHAR(64) NULL COMMENT 'SHA-256(prev_hash || canonical(content))',
  ADD KEY `idx_audit_entry_hash` (`entry_hash`);

-- Single-row chain head + seal anchor. Locked FOR UPDATE on each append so
-- concurrent writers cannot fork the chain.
CREATE TABLE IF NOT EXISTS `ahg_audit_chain_state` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `genesis_hash` CHAR(64) NOT NULL COMMENT 'anchor binding the chain to the seal moment',
  `last_hash` CHAR(64) NOT NULL COMMENT 'entry_hash of the most recent chained entry',
  `last_audit_id` BIGINT UNSIGNED NULL,
  `sealed_from_id` BIGINT UNSIGNED NULL COMMENT 'MAX(ahg_audit_log.id) when sealed',
  `sealed_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
