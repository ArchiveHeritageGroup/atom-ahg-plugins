-- ============================================================
-- ahgPrivacyPlugin - Database Schema
-- Multi-jurisdictional privacy compliance
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

-- Privacy Configuration
CREATE TABLE IF NOT EXISTS `privacy_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jurisdiction` ENUM('popia', 'gdpr', 'pipeda', 'ccpa', 'custom') NOT NULL DEFAULT 'popia',
    `organization_name` VARCHAR(255) NULL,
    `registration_number` VARCHAR(100) NULL,
    `privacy_officer_id` INT UNSIGNED NULL,
    `data_protection_email` VARCHAR(255) NULL,
    `dsar_response_days` INT NOT NULL DEFAULT 30,
    `breach_notification_hours` INT NOT NULL DEFAULT 72,
    `retention_default_years` INT NOT NULL DEFAULT 5,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `settings` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Officer / Information Officer
CREATE TABLE IF NOT EXISTS `privacy_officer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `title` VARCHAR(100) NULL,
    `jurisdiction` ENUM('popia', 'gdpr', 'pipeda', 'ccpa', 'all') NOT NULL DEFAULT 'all',
    `registration_number` VARCHAR(100) NULL COMMENT 'POPIA Information Regulator registration',
    `appointed_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Subject Access Requests (DSAR)
CREATE TABLE IF NOT EXISTS `privacy_dsar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `jurisdiction` ENUM('popia', 'gdpr', 'pipeda', 'ccpa') NOT NULL,
    `request_type` ENUM('access', 'rectification', 'erasure', 'portability', 'restriction', 'objection', 'withdraw_consent') NOT NULL,
    `requestor_name` VARCHAR(255) NOT NULL,
    `requestor_email` VARCHAR(255) NULL,
    `requestor_phone` VARCHAR(50) NULL,
    `requestor_id_type` VARCHAR(50) NULL,
    `requestor_id_number` VARCHAR(100) NULL,
    `requestor_address` TEXT NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `verified_at` DATETIME NULL,
    `verified_by` INT NULL,
    `status` ENUM('received', 'verified', 'in_progress', 'pending_info', 'completed', 'rejected', 'withdrawn') NOT NULL DEFAULT 'received',
    `priority` ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    `received_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `completed_date` DATE NULL,
    `assigned_to` INT NULL,
    `outcome` ENUM('granted', 'partially_granted', 'refused', 'not_applicable') NULL,
    `refusal_reason` TEXT NULL,
    `fee_required` DECIMAL(10,2) NULL,
    `fee_paid` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_status` (`status`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_dsar_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `description` TEXT NULL,
    `notes` TEXT NULL,
    `response_summary` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_dsar_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_dsar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Breach Register
CREATE TABLE IF NOT EXISTS `privacy_breach` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `jurisdiction` ENUM('popia', 'gdpr', 'pipeda', 'ccpa') NOT NULL,
    `breach_type` ENUM('confidentiality', 'integrity', 'availability') NOT NULL,
    `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `status` ENUM('detected', 'investigating', 'contained', 'resolved', 'closed') NOT NULL DEFAULT 'detected',
    `detected_date` DATETIME NOT NULL,
    `occurred_date` DATETIME NULL,
    `contained_date` DATETIME NULL,
    `resolved_date` DATETIME NULL,
    `data_subjects_affected` INT NULL,
    `data_categories_affected` TEXT NULL,
    `notification_required` TINYINT(1) NOT NULL DEFAULT 0,
    `regulator_notified` TINYINT(1) NOT NULL DEFAULT 0,
    `regulator_notified_date` DATETIME NULL,
    `subjects_notified` TINYINT(1) NOT NULL DEFAULT 0,
    `subjects_notified_date` DATETIME NULL,
    `risk_to_rights` ENUM('unlikely', 'possible', 'likely', 'high') NULL,
    `assigned_to` INT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_status` (`status`),
    KEY `idx_severity` (`severity`),
    KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_breach_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `title` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `cause` TEXT NULL,
    `impact_assessment` TEXT NULL,
    `remedial_actions` TEXT NULL,
    `lessons_learned` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_breach_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_breach` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Breach Notifications
CREATE TABLE IF NOT EXISTS `privacy_breach_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `breach_id` INT UNSIGNED NOT NULL,
    `notification_type` ENUM('regulator', 'data_subject', 'internal', 'third_party') NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `method` ENUM('email', 'letter', 'portal', 'phone', 'in_person') NOT NULL,
    `sent_date` DATETIME NULL,
    `acknowledged_date` DATETIME NULL,
    `content` TEXT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_breach` (`breach_id`),
    CONSTRAINT `fk_breach_notif` FOREIGN KEY (`breach_id`) REFERENCES `privacy_breach` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent Management
CREATE TABLE IF NOT EXISTS `privacy_consent` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consent_type` ENUM('processing', 'marketing', 'profiling', 'third_party', 'cookies', 'research', 'special_category') NOT NULL,
    `purpose_code` VARCHAR(50) NOT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `valid_from` DATE NULL,
    `valid_until` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`consent_type`),
    KEY `idx_purpose` (`purpose_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_consent_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `purpose_description` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_consent_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_consent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent Log (individual consent records)
CREATE TABLE IF NOT EXISTS `privacy_consent_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consent_id` INT UNSIGNED NOT NULL,
    `user_id` INT NULL,
    `subject_identifier` VARCHAR(255) NULL COMMENT 'Email or other identifier if not user',
    `action` ENUM('granted', 'withdrawn', 'expired', 'renewed') NOT NULL,
    `consent_given` TINYINT(1) NOT NULL DEFAULT 0,
    `consent_date` DATETIME NOT NULL,
    `withdrawal_date` DATETIME NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `consent_proof` TEXT NULL COMMENT 'Evidence of consent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_consent` (`consent_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_subject` (`subject_identifier`),
    CONSTRAINT `fk_consent_log` FOREIGN KEY (`consent_id`) REFERENCES `privacy_consent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Processing Activities (ROPA - Records of Processing Activities)
CREATE TABLE IF NOT EXISTS `privacy_processing_activity` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_code` VARCHAR(50) NOT NULL,
    `controller_name` VARCHAR(255) NULL,
    `processor_name` VARCHAR(255) NULL,
    `lawful_basis` ENUM('consent', 'contract', 'legal_obligation', 'vital_interests', 'public_task', 'legitimate_interests') NOT NULL,
    `data_categories` JSON NULL,
    `special_categories` TINYINT(1) NOT NULL DEFAULT 0,
    `data_subjects` JSON NULL,
    `recipients` JSON NULL,
    `third_country_transfers` TINYINT(1) NOT NULL DEFAULT 0,
    `transfer_safeguards` VARCHAR(255) NULL,
    `retention_period` VARCHAR(100) NULL,
    `security_measures` TEXT NULL,
    `dpia_required` TINYINT(1) NOT NULL DEFAULT 0,
    `dpia_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `dpia_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_reviewed` DATE NULL,
    `next_review` DATE NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_code`),
    KEY `idx_lawful_basis` (`lawful_basis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_processing_activity_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `name` VARCHAR(255) NOT NULL,
    `purpose` TEXT NULL,
    `description` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_processing_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_processing_activity` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Inventory
CREATE TABLE IF NOT EXISTS `privacy_data_inventory` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `data_type` ENUM('personal', 'special_category', 'children', 'criminal', 'financial', 'health', 'biometric', 'genetic') NOT NULL,
    `storage_location` VARCHAR(255) NULL,
    `storage_format` ENUM('electronic', 'paper', 'both') NOT NULL DEFAULT 'electronic',
    `encryption` TINYINT(1) NOT NULL DEFAULT 0,
    `access_controls` TEXT NULL,
    `retention_years` INT NULL,
    `disposal_method` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`data_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retention Schedule
CREATE TABLE IF NOT EXISTS `privacy_retention_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `record_type` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `retention_period` VARCHAR(100) NOT NULL,
    `retention_years` INT NULL,
    `legal_basis` VARCHAR(255) NULL,
    `disposal_action` ENUM('destroy', 'archive', 'anonymize', 'review') NOT NULL DEFAULT 'destroy',
    `jurisdiction` ENUM('popia', 'gdpr', 'pipeda', 'ccpa', 'all') NOT NULL DEFAULT 'all',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log
CREATE TABLE IF NOT EXISTS `privacy_audit_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `user_id` INT NULL,
    `ip_address` VARCHAR(45) NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default jurisdiction-specific retention schedules
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Personnel records', 'Employee files after termination', '5 years after termination', 5, 'POPIA s14, BCEA', 'destroy', 'popia'),
('Financial records', 'Accounting and tax records', '5 years', 5, 'TAA, Companies Act', 'destroy', 'popia'),
('Access requests', 'DSAR records', '3 years after completion', 3, 'POPIA s23', 'archive', 'popia'),
('Consent records', 'Proof of consent', 'Duration of processing + 3 years', 3, 'POPIA s11', 'destroy', 'popia'),
('Breach records', 'Data breach documentation', '5 years', 5, 'POPIA s22', 'archive', 'popia'),
('Personnel records', 'Employee files after termination', '6 years after termination', 6, 'GDPR Art 17, Limitation Act', 'destroy', 'gdpr'),
('Access requests', 'DSAR records', '3 years after completion', 3, 'GDPR Art 5(2)', 'archive', 'gdpr'),
('Consent records', 'Proof of consent', 'Duration of processing + 6 years', 6, 'GDPR Art 7', 'destroy', 'gdpr'),
('Breach records', 'Data breach documentation', '5 years', 5, 'GDPR Art 33', 'archive', 'gdpr'),
('Customer records', 'Customer personal data', '7 years after last transaction', 7, 'PIPEDA Principle 5', 'destroy', 'pipeda'),
('Access requests', 'Access request records', '2 years after completion', 2, 'PIPEDA s8', 'archive', 'pipeda'),
('Consumer requests', 'CCPA request records', '24 months', 2, 'CCPA 1798.130', 'destroy', 'ccpa'),
('Opt-out records', 'Do Not Sell records', '24 months', 2, 'CCPA 1798.135', 'archive', 'ccpa')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);

-- PAIA Requests (South Africa - Promotion of Access to Information Act)
CREATE TABLE IF NOT EXISTS `privacy_paia_request` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `paia_section` ENUM('section_18', 'section_22', 'section_23', 'section_50', 'section_77') NOT NULL,
    `requestor_name` VARCHAR(255) NOT NULL,
    `requestor_email` VARCHAR(255) NULL,
    `requestor_phone` VARCHAR(50) NULL,
    `requestor_id_number` VARCHAR(100) NULL,
    `requestor_address` TEXT NULL,
    `record_description` TEXT NULL,
    `access_form` ENUM('inspect', 'copy', 'both') NOT NULL DEFAULT 'copy',
    `status` ENUM('received', 'processing', 'granted', 'partially_granted', 'refused', 'transferred', 'appealed') NOT NULL DEFAULT 'received',
    `outcome_reason` TEXT NULL,
    `refusal_grounds` VARCHAR(100) NULL COMMENT 'PAIA grounds for refusal section',
    `fee_deposit` DECIMAL(10,2) NULL,
    `fee_access` DECIMAL(10,2) NULL,
    `fee_paid` TINYINT(1) NOT NULL DEFAULT 0,
    `received_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `completed_date` DATE NULL,
    `assigned_to` INT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_paia_reference` (`reference_number`),
    KEY `idx_paia_status` (`status`),
    KEY `idx_paia_section` (`paia_section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add jurisdiction column to existing tables if not exists
-- Note: Using ALTER TABLE with IF NOT EXISTS workaround

-- Check and add jurisdiction to privacy_processing_activity
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'privacy_processing_activity' AND COLUMN_NAME = 'jurisdiction');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE privacy_processing_activity ADD COLUMN jurisdiction VARCHAR(20) DEFAULT ''popia'' AFTER name', 'SELECT ''Column exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add lawful_basis_code
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'privacy_processing_activity' AND COLUMN_NAME = 'lawful_basis_code');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE privacy_processing_activity ADD COLUMN lawful_basis_code VARCHAR(50) NULL AFTER lawful_basis', 'SELECT ''Column exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add cross_border_safeguards
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'privacy_processing_activity' AND COLUMN_NAME = 'cross_border_safeguards');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE privacy_processing_activity ADD COLUMN cross_border_safeguards TEXT NULL AFTER third_countries', 'SELECT ''Column exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add jurisdiction to consent_record
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'privacy_consent_record' AND COLUMN_NAME = 'jurisdiction');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE privacy_consent_record ADD COLUMN jurisdiction VARCHAR(20) DEFAULT ''popia''', 'SELECT ''Column exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
