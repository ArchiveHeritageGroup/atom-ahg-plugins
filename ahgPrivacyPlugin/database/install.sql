-- =====================================================
-- AHG Privacy Plugin - Database Schema
-- Version: 1.0.0
-- =====================================================

-- Privacy Audit Log
CREATE TABLE IF NOT EXISTS `privacy_audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Breach Register
CREATE TABLE IF NOT EXISTS `privacy_breach` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `jurisdiction` ENUM('popia','gdpr','pipeda','ccpa') NOT NULL,
  `breach_type` ENUM('confidentiality','integrity','availability') NOT NULL,
  `severity` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` ENUM('detected','investigating','contained','resolved','closed') NOT NULL DEFAULT 'detected',
  `detected_date` DATETIME NOT NULL,
  `occurred_date` DATETIME DEFAULT NULL,
  `contained_date` DATETIME DEFAULT NULL,
  `resolved_date` DATETIME DEFAULT NULL,
  `data_subjects_affected` INT DEFAULT NULL,
  `data_categories_affected` TEXT,
  `notification_required` TINYINT(1) NOT NULL DEFAULT 0,
  `regulator_notified` TINYINT(1) NOT NULL DEFAULT 0,
  `regulator_notified_date` DATETIME DEFAULT NULL,
  `subjects_notified` TINYINT(1) NOT NULL DEFAULT 0,
  `subjects_notified_date` DATETIME DEFAULT NULL,
  `risk_to_rights` ENUM('unlikely','possible','likely','high') DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference_number`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Breach i18n
CREATE TABLE IF NOT EXISTS `privacy_breach_i18n` (
  `id` INT UNSIGNED NOT NULL,
  `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
  `title` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `cause` TEXT,
  `impact_assessment` TEXT,
  `remedial_actions` TEXT,
  `lessons_learned` TEXT,
  PRIMARY KEY (`id`, `culture`),
  CONSTRAINT `fk_breach_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_breach` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Breach Incident (Legacy)
CREATE TABLE IF NOT EXISTS `privacy_breach_incident` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(50) NOT NULL,
  `incident_date` DATETIME NOT NULL,
  `discovered_date` DATETIME NOT NULL,
  `breach_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `data_affected` TEXT,
  `individuals_affected` INT DEFAULT NULL,
  `severity` VARCHAR(50) DEFAULT NULL,
  `root_cause` TEXT,
  `containment_actions` TEXT,
  `regulator_notified` TINYINT(1) DEFAULT 0,
  `notification_date` DATETIME DEFAULT NULL,
  `subjects_notified` TINYINT(1) DEFAULT 0,
  `status` VARCHAR(50) DEFAULT 'open',
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Breach Notification
CREATE TABLE IF NOT EXISTS `privacy_breach_notification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `breach_id` INT UNSIGNED NOT NULL,
  `notification_type` ENUM('regulator','data_subject','internal','third_party') NOT NULL,
  `recipient` VARCHAR(255) NOT NULL,
  `method` ENUM('email','letter','portal','phone','in_person') NOT NULL,
  `sent_date` DATETIME DEFAULT NULL,
  `acknowledged_date` DATETIME DEFAULT NULL,
  `content` TEXT,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_breach` (`breach_id`),
  CONSTRAINT `fk_breach_notif` FOREIGN KEY (`breach_id`) REFERENCES `privacy_breach` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Complaint
CREATE TABLE IF NOT EXISTS `privacy_complaint` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `jurisdiction` VARCHAR(20) DEFAULT 'popia',
  `complainant_name` VARCHAR(255) NOT NULL,
  `complainant_email` VARCHAR(255) DEFAULT NULL,
  `complainant_phone` VARCHAR(50) DEFAULT NULL,
  `complaint_type` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `date_of_incident` DATE DEFAULT NULL,
  `status` ENUM('received','investigating','resolved','escalated','closed') DEFAULT 'received',
  `assigned_to` INT DEFAULT NULL,
  `resolution` TEXT,
  `resolved_date` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_complaint_ref` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Config (per jurisdiction)
CREATE TABLE IF NOT EXISTS `privacy_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction` VARCHAR(50) NOT NULL DEFAULT 'popia',
  `organization_name` VARCHAR(255) DEFAULT NULL,
  `registration_number` VARCHAR(100) DEFAULT NULL,
  `privacy_officer_id` INT UNSIGNED DEFAULT NULL,
  `data_protection_email` VARCHAR(255) DEFAULT NULL,
  `dsar_response_days` INT NOT NULL DEFAULT 30,
  `breach_notification_hours` INT NOT NULL DEFAULT 72,
  `retention_default_years` INT NOT NULL DEFAULT 5,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Consent Types
CREATE TABLE IF NOT EXISTS `privacy_consent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consent_type` ENUM('processing','marketing','profiling','third_party','cookies','research','special_category') NOT NULL,
  `purpose_code` VARCHAR(50) NOT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `valid_from` DATE DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`consent_type`),
  KEY `idx_purpose` (`purpose_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Consent i18n
CREATE TABLE IF NOT EXISTS `privacy_consent_i18n` (
  `id` INT UNSIGNED NOT NULL,
  `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `purpose_description` TEXT,
  PRIMARY KEY (`id`, `culture`),
  CONSTRAINT `fk_consent_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_consent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Consent Log
CREATE TABLE IF NOT EXISTS `privacy_consent_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consent_id` INT UNSIGNED NOT NULL,
  `user_id` INT DEFAULT NULL,
  `subject_identifier` VARCHAR(255) DEFAULT NULL COMMENT 'Email or other identifier if not user',
  `action` ENUM('granted','withdrawn','expired','renewed') NOT NULL,
  `consent_given` TINYINT(1) NOT NULL DEFAULT 0,
  `consent_date` DATETIME NOT NULL,
  `withdrawal_date` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `consent_proof` TEXT COMMENT 'Evidence of consent',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_consent` (`consent_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_subject` (`subject_identifier`),
  CONSTRAINT `fk_consent_log` FOREIGN KEY (`consent_id`) REFERENCES `privacy_consent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Consent Record (Simple)
CREATE TABLE IF NOT EXISTS `privacy_consent_record` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `data_subject_id` VARCHAR(255) NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `consent_given` TINYINT(1) DEFAULT 0,
  `consent_date` DATETIME DEFAULT NULL,
  `withdrawal_date` DATETIME DEFAULT NULL,
  `source` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(50) DEFAULT 'active',
  `withdrawn_date` DATE DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`data_subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Data Inventory
CREATE TABLE IF NOT EXISTS `privacy_data_inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `data_type` ENUM('personal','special_category','children','criminal','financial','health','biometric','genetic') NOT NULL,
  `storage_location` VARCHAR(255) DEFAULT NULL,
  `storage_format` ENUM('electronic','paper','both') NOT NULL DEFAULT 'electronic',
  `encryption` TINYINT(1) NOT NULL DEFAULT 0,
  `access_controls` TEXT,
  `retention_years` INT DEFAULT NULL,
  `disposal_method` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`data_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DSAR (Data Subject Access Request)
CREATE TABLE IF NOT EXISTS `privacy_dsar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `jurisdiction` ENUM('popia','gdpr','pipeda','ccpa') NOT NULL,
  `request_type` ENUM('access','rectification','erasure','portability','restriction','objection','withdraw_consent') NOT NULL,
  `requestor_name` VARCHAR(255) NOT NULL,
  `requestor_email` VARCHAR(255) DEFAULT NULL,
  `requestor_phone` VARCHAR(50) DEFAULT NULL,
  `requestor_id_type` VARCHAR(50) DEFAULT NULL,
  `requestor_id_number` VARCHAR(100) DEFAULT NULL,
  `requestor_address` TEXT,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verified_at` DATETIME DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `status` ENUM('received','verified','in_progress','pending_info','completed','rejected','withdrawn') NOT NULL DEFAULT 'received',
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `received_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `outcome` ENUM('granted','partially_granted','refused','not_applicable') DEFAULT NULL,
  `refusal_reason` TEXT,
  `fee_required` DECIMAL(10,2) DEFAULT NULL,
  `fee_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference_number`),
  KEY `idx_status` (`status`),
  KEY `idx_jurisdiction` (`jurisdiction`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DSAR i18n
CREATE TABLE IF NOT EXISTS `privacy_dsar_i18n` (
  `id` INT UNSIGNED NOT NULL,
  `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
  `description` TEXT,
  `notes` TEXT,
  `response_summary` TEXT,
  PRIMARY KEY (`id`, `culture`),
  CONSTRAINT `fk_dsar_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_dsar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DSAR Log
CREATE TABLE IF NOT EXISTS `privacy_dsar_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dsar_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dsar` (`dsar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DSAR Request (Legacy simple table)
CREATE TABLE IF NOT EXISTS `privacy_dsar_request` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(50) NOT NULL,
  `request_type` VARCHAR(50) NOT NULL,
  `data_subject_name` VARCHAR(255) NOT NULL,
  `data_subject_email` VARCHAR(255) DEFAULT NULL,
  `data_subject_id_type` VARCHAR(50) DEFAULT NULL,
  `received_date` DATE NOT NULL,
  `deadline_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `notes` TEXT,
  `assigned_to` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference`),
  KEY `idx_status` (`status`),
  KEY `idx_deadline` (`deadline_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Jurisdiction
CREATE TABLE IF NOT EXISTS `privacy_jurisdiction` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `region` VARCHAR(50) DEFAULT 'Africa',
  `regulator` VARCHAR(255) DEFAULT NULL,
  `regulator_url` VARCHAR(255) DEFAULT NULL,
  `dsar_days` INT DEFAULT 30,
  `breach_hours` INT DEFAULT 72,
  `effective_date` DATE DEFAULT NULL,
  `related_laws` JSON DEFAULT NULL,
  `icon` VARCHAR(10) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 99,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_jurisdiction_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Notification
CREATE TABLE IF NOT EXISTS `privacy_notification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'ropa, dsar, breach, consent',
  `entity_id` INT UNSIGNED NOT NULL,
  `notification_type` VARCHAR(50) NOT NULL COMMENT 'submitted, approved, rejected, comment, reminder',
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `link` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `email_sent` TINYINT(1) DEFAULT 0,
  `email_sent_at` DATETIME DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_unread` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Officer
CREATE TABLE IF NOT EXISTS `privacy_officer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `title` VARCHAR(100) DEFAULT NULL,
  `jurisdiction` ENUM('popia','gdpr','pipeda','ccpa','all') NOT NULL DEFAULT 'all',
  `registration_number` VARCHAR(100) DEFAULT NULL COMMENT 'POPIA Information Regulator registration',
  `appointed_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAIA Request (South Africa)
CREATE TABLE IF NOT EXISTS `privacy_paia_request` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `paia_section` ENUM('section_18','section_22','section_23','section_50','section_77') NOT NULL,
  `requestor_name` VARCHAR(255) NOT NULL,
  `requestor_email` VARCHAR(255) DEFAULT NULL,
  `requestor_phone` VARCHAR(50) DEFAULT NULL,
  `requestor_id_number` VARCHAR(100) DEFAULT NULL,
  `requestor_address` TEXT,
  `record_description` TEXT,
  `access_form` ENUM('inspect','copy','both') NOT NULL DEFAULT 'copy',
  `status` ENUM('received','processing','granted','partially_granted','refused','transferred','appealed') NOT NULL DEFAULT 'received',
  `outcome_reason` TEXT,
  `refusal_grounds` VARCHAR(100) DEFAULT NULL COMMENT 'PAIA grounds for refusal section',
  `fee_deposit` DECIMAL(10,2) DEFAULT NULL,
  `fee_access` DECIMAL(10,2) DEFAULT NULL,
  `fee_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `received_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_paia_reference` (`reference_number`),
  KEY `idx_paia_status` (`status`),
  KEY `idx_paia_section` (`paia_section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ROPA (Processing Activity)
CREATE TABLE IF NOT EXISTS `privacy_processing_activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `jurisdiction` VARCHAR(20) DEFAULT 'popia',
  `purpose` TEXT NOT NULL,
  `lawful_basis` VARCHAR(100) DEFAULT NULL,
  `lawful_basis_code` VARCHAR(50) DEFAULT NULL,
  `data_categories` TEXT,
  `data_subjects` TEXT,
  `recipients` TEXT,
  `third_countries` JSON DEFAULT NULL,
  `transfers` TEXT,
  `retention_period` VARCHAR(100) DEFAULT NULL,
  `security_measures` TEXT,
  `dpia_required` TINYINT(1) DEFAULT 0,
  `dpia_completed` TINYINT(1) DEFAULT 0,
  `dpia_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'draft',
  `owner` VARCHAR(255) DEFAULT NULL,
  `department` VARCHAR(255) DEFAULT NULL,
  `assigned_officer_id` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `next_review_date` DATE DEFAULT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `submitted_by` INT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `rejected_at` DATETIME DEFAULT NULL,
  `rejected_by` INT DEFAULT NULL,
  `rejection_reason` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ROPA i18n
CREATE TABLE IF NOT EXISTS `privacy_processing_activity_i18n` (
  `id` INT UNSIGNED NOT NULL,
  `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
  `name` VARCHAR(255) NOT NULL,
  `purpose` TEXT,
  `description` TEXT,
  PRIMARY KEY (`id`, `culture`),
  CONSTRAINT `fk_processing_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_processing_activity` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Retention Schedule
CREATE TABLE IF NOT EXISTS `privacy_retention_schedule` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_type` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `retention_period` VARCHAR(100) NOT NULL,
  `retention_years` INT DEFAULT NULL,
  `legal_basis` VARCHAR(255) DEFAULT NULL,
  `disposal_action` ENUM('destroy','archive','anonymize','review') NOT NULL DEFAULT 'destroy',
  `jurisdiction` ENUM('popia','gdpr','pipeda','ccpa','all') NOT NULL DEFAULT 'all',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Template
CREATE TABLE IF NOT EXISTS `privacy_template` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Privacy Approval Log
CREATE TABLE IF NOT EXISTS `privacy_approval_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'submitted, approved, rejected, comment',
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `comment` TEXT,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Data
-- =====================================================

-- Default Jurisdictions
INSERT INTO `privacy_jurisdiction` (`code`, `name`, `full_name`, `country`, `region`, `regulator`, `regulator_url`, `dsar_days`, `breach_hours`, `effective_date`, `icon`, `is_active`, `sort_order`) VALUES
('popia', 'POPIA', 'Protection of Personal Information Act', 'South Africa', 'Africa', 'Information Regulator', 'https://inforegulator.org.za/', 30, 72, '2021-07-01', '🇿🇦', 1, 1),
('gdpr', 'GDPR', 'General Data Protection Regulation', 'European Union', 'Europe', 'European Data Protection Board', 'https://edpb.europa.eu/', 30, 72, '2018-05-25', '🇪🇺', 1, 2),
('pipeda', 'PIPEDA', 'Personal Information Protection and Electronic Documents Act', 'Canada', 'North America', 'Office of the Privacy Commissioner', 'https://www.priv.gc.ca/', 30, 72, '2000-01-01', '🇨🇦', 0, 3),
('ccpa', 'CCPA', 'California Consumer Privacy Act', 'United States', 'North America', 'California Attorney General', 'https://oag.ca.gov/privacy/ccpa', 45, 72, '2020-01-01', '🇺🇸', 0, 4)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default Retention Schedules
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personal files, contracts, performance reviews', '7 years after termination', 7, 'BCEA, LRA', 'destroy', 'popia'),
('Financial Records', 'Invoices, receipts, financial statements', '5 years', 5, 'Companies Act, TAA', 'destroy', 'popia'),
('Tax Records', 'Tax returns, assessments, supporting documents', '5 years', 5, 'Tax Administration Act', 'destroy', 'popia'),
('Medical Records', 'Patient health records', '10 years or age 21', 10, 'National Health Act', 'archive', 'popia'),
('CCTV Footage', 'Video surveillance recordings', '30 days unless incident', 0, 'POPIA, RICA', 'destroy', 'popia'),
('Access Control Logs', 'Building and system access records', '1 year', 1, 'POPIA', 'destroy', 'popia'),
('Customer Records', 'Customer contact and transaction data', '5 years after last transaction', 5, 'CPA, POPIA', 'anonymize', 'all'),
('Marketing Consent', 'Records of consent for marketing', 'Duration of consent + 1 year', 1, 'POPIA s69, CPA', 'destroy', 'popia'),
('DSAR Records', 'Data subject access request documentation', '3 years', 3, 'POPIA', 'destroy', 'popia'),
('Breach Records', 'Data breach incident documentation', '5 years', 5, 'POPIA', 'archive', 'popia')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
