-- #118 records-manage: file plan / classification scheme (nested-set tree).
CREATE TABLE IF NOT EXISTS `rm_fileplan_node` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `function_object_id` INT DEFAULT NULL COMMENT 'optional link to a function (object.id)',
  `node_type` VARCHAR(20) NOT NULL DEFAULT 'series' COMMENT 'function, series, subseries, file, class',
  `code` VARCHAR(100) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `disposal_class_id` INT UNSIGNED DEFAULT NULL COMMENT 'optional rm_disposal_class.id',
  `retention_period` VARCHAR(100) DEFAULT NULL,
  `disposal_action` VARCHAR(40) DEFAULT NULL COMMENT 'destroy, transfer, retain_permanent, review',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, superseded, draft',
  `source_department` VARCHAR(255) DEFAULT NULL,
  `source_agency_code` VARCHAR(50) DEFAULT NULL,
  `import_session_id` INT UNSIGNED DEFAULT NULL,
  `depth` INT NOT NULL DEFAULT 0,
  `lft` INT DEFAULT NULL,
  `rgt` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fileplan_code` (`code`),
  KEY `idx_fileplan_parent` (`parent_id`),
  KEY `idx_fileplan_lft` (`lft`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/add_email_capture_table.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- #118 records-manage: email capture queue (capture business email as records).
CREATE TABLE IF NOT EXISTS `rm_email_capture` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` VARCHAR(255) NOT NULL,
  `from_address` VARCHAR(500) DEFAULT NULL,
  `to_addresses` TEXT DEFAULT NULL,
  `cc_addresses` TEXT DEFAULT NULL,
  `subject` VARCHAR(1000) DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `received_at` DATETIME DEFAULT NULL,
  `body_text` MEDIUMTEXT DEFAULT NULL,
  `body_html` MEDIUMTEXT DEFAULT NULL,
  `attachment_count` INT NOT NULL DEFAULT 0,
  `eml_storage_path` VARCHAR(1000) DEFAULT NULL,
  `capture_source` VARCHAR(20) NOT NULL DEFAULT 'eml_upload' COMMENT 'eml_upload, imap, msg_upload',
  `status` VARCHAR(16) NOT NULL DEFAULT 'captured' COMMENT 'captured, classified, declared',
  `fileplan_node_id` INT UNSIGNED DEFAULT NULL COMMENT 'rm_fileplan_node.id',
  `disposal_class_id` INT UNSIGNED DEFAULT NULL,
  `information_object_id` INT DEFAULT NULL COMMENT 'set when declared as a record',
  `captured_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_message_id` (`message_id`),
  KEY `idx_email_status` (`status`),
  KEY `idx_email_node` (`fileplan_node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
