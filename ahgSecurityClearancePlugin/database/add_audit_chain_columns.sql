-- #126: tamper-evident hash chaining for the security access log.
-- Also restores compartment_id + session_id (logAccess() writes them but the
-- columns were missing, so every audit write silently failed).
ALTER TABLE `security_access_log`
  ADD COLUMN `compartment_id` INT UNSIGNED NULL AFTER `classification_id`,
  ADD COLUMN `session_id` VARCHAR(255) NULL AFTER `user_agent`,
  ADD COLUMN `prev_hash` CHAR(64) NULL COMMENT 'SHA-256 entry_hash of the previous entry',
  ADD COLUMN `entry_hash` CHAR(64) NULL COMMENT 'SHA-256(prev_hash || canonical(content))',
  ADD KEY `idx_sal_entry_hash` (`entry_hash`);

-- An audit trail must be append-only and independent of the lifecycle of the
-- things it records. Drop the FKs so deleting a described object (CASCADE) or
-- a classification (RESTRICT) can neither erase nor block audit history — the
-- ids remain as plain snapshot values and the hash chain stays intact.
ALTER TABLE `security_access_log` DROP FOREIGN KEY `fk_sal_object`;
ALTER TABLE `security_access_log` DROP FOREIGN KEY `fk_sal_classification`;
