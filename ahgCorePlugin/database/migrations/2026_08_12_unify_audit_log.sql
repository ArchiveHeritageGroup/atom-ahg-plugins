-- ============================================================
-- One comprehensive audit_log, serving base AtoM and the AHG plugins
--
-- THE PROBLEM
--
-- audit_log is a BASE AtoM table (config/schema.yml:45). Upstream it is:
--
--     id, object_id (NOT NULL, FK object ON DELETE CASCADE),
--     user_id (FK user), user_name, action_type_id (FK term), created_at
--
-- On PSIS it is something else entirely - no object_id, no action_type_id, no
-- user_name, no foreign keys - and instead:
--
--     table_name, record_id, action, field_name, old_value, new_value,
--     old_record, new_record, username, ip_address, user_agent, module,
--     action_description
--
-- Not an extension of the upstream table: a replacement, created by hand. It is
-- in no install.sql and no migration - `grep -rlnE "CREATE TABLE .*\`audit_log\`"`
-- across every plugin and the framework returns nothing.
--
-- So the two shapes are mutually exclusive, and each instance breaks whichever
-- half it does not have:
--
--   * On a stock instance (upstream shape), ahgResearchPlugin's audit browser
--     dies with "Unknown column 'table_name'", and takes /research/dashboard
--     with it, because the dashboard reaches into that module.
--   * On PSIS (replacement shape), base AtoM's own descriptionUpdatesAction
--     joins QubitAuditLog::OBJECT_ID against a column that is not there. It only
--     appears to work because ahgSearchPlugin overrides that screen.
--
-- Five plugins read this table - ahgResearchPlugin, ahgSecurityClearancePlugin,
-- ahgDAMPlugin, ahgThemeB5Plugin (the voice actions) and the framework's
-- LandingConfigService - and none of them create it. Every one assumed whichever
-- shape the machine in front of them happened to have.
--
-- THE FIX
--
-- The union, with everything nullable, so either writer can insert a row without
-- inventing values for the other's columns. A base row leaves the AHG columns
-- null; an AHG row that is not about a Qubit object leaves object_id null.
--
-- object_id has to become nullable for that to work. It is upstream-required,
-- but an audit entry about a login, a setting or a security decision has no
-- object to point at - which is precisely why the hand-made table dropped the
-- column rather than relaxing it. Relaxing keeps base AtoM's joins working;
-- dropping did not. audit_log is not one of the protected core tables
-- (object, information_object, actor, term, taxonomy, user, repository,
-- digital_object).
--
-- Additive and idempotent by inspection: MySQL has no ADD COLUMN IF NOT EXISTS,
-- so each column is guarded against information_schema. No column is dropped and
-- no row is touched, so this is safe to run on an instance of either shape, and
-- safe to run twice.
-- ============================================================

SET @db := DATABASE();

-- ---- base AtoM's columns, for an instance carrying the replacement shape ----

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `object_id` INT NULL COMMENT ''Qubit object this entry is about; NULL for events that are not about an object''',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'object_id');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `action_type_id` INT NULL COMMENT ''term id, base AtoM action vocabulary''',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'action_type_id');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `user_name` VARCHAR(255) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'user_name');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- object_id is NOT NULL upstream. An AHG entry about a login or a setting has no
-- object, so it must be allowed to be null - see the note above.
SET @sql := (SELECT IF(COUNT(*) = 1,
  'ALTER TABLE `audit_log` MODIFY COLUMN `object_id` INT NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log'
    AND column_name = 'object_id' AND is_nullable = 'NO');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---- the AHG columns, for an instance carrying the upstream shape ----

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `table_name` VARCHAR(100) NULL COMMENT ''table the entry is about, when it is not a Qubit object''',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'table_name');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `record_id` INT NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'record_id');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `action` VARCHAR(30) NULL COMMENT ''insert, update, delete, view, login, export''',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'action');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `field_name` VARCHAR(100) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'field_name');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `old_value` TEXT NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'old_value');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `new_value` TEXT NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'new_value');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `old_record` JSON NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'old_record');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `new_record` JSON NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'new_record');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `username` VARCHAR(255) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'username');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `ip_address` VARCHAR(45) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'ip_address');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `user_agent` VARCHAR(500) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'user_agent');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `module` VARCHAR(100) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'module');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD COLUMN `action_description` VARCHAR(255) NULL',
  'DO 0') FROM information_schema.columns
  WHERE table_schema = @db AND table_name = 'audit_log' AND column_name = 'action_description');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---- indexes the readers actually query on ----

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD INDEX `idx_audit_table_record` (`table_name`, `record_id`)',
  'DO 0') FROM information_schema.statistics
  WHERE table_schema = @db AND table_name = 'audit_log' AND index_name = 'idx_audit_table_record');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD INDEX `idx_audit_created` (`created_at`)',
  'DO 0') FROM information_schema.statistics
  WHERE table_schema = @db AND table_name = 'audit_log' AND index_name = 'idx_audit_created');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `audit_log` ADD INDEX `idx_audit_user` (`user_id`)',
  'DO 0') FROM information_schema.statistics
  WHERE table_schema = @db AND table_name = 'audit_log' AND index_name = 'idx_audit_user');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
