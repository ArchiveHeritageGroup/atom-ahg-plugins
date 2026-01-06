-- =====================================================
-- ahgPrivacyPlugin - Complete Database Schema
-- All privacy-related tables for POPIA/GDPR compliance
-- =====================================================

-- Jurisdictions (POPIA, GDPR, etc.)
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

-- Privacy Configuration per jurisdiction
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

-- Privacy/Information Officers
CREATE TABLE IF NOT EXISTS `privacy_officer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` VARCHAR(100) DEFAULT 'Information Officer',
  `registration_number` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Subject Access Requests (DSAR)
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

-- DSAR Activity Log
CREATE TABLE IF NOT EXISTS `privacy_dsar_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dsar_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT,
  `user_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dsar` (`dsar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DSAR Request Details (extended info)
CREATE TABLE IF NOT EXISTS `privacy_dsar_request` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(50) NOT NULL,
  `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
  `request_type` VARCHAR(50) NOT NULL,
  `data_subject_name` VARCHAR(255) NOT NULL,
  `data_subject_email` VARCHAR(255) DEFAULT NULL,
  `data_subject_phone` VARCHAR(50) DEFAULT NULL,
  `data_subject_id` VARCHAR(100) DEFAULT NULL,
  `description` TEXT,
  `status` VARCHAR(50) DEFAULT 'pending',
  `received_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Breaches
CREATE TABLE IF NOT EXISTS `privacy_breach` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `jurisdiction` ENUM('popia','gdpr','pipeda','ccpa') NOT NULL,
  `breach_date` DATETIME NOT NULL,
  `detected_date` DATETIME NOT NULL,
  `breach_type` ENUM('unauthorized_access','unauthorized_disclosure','loss','theft','accidental','malicious','other') NOT NULL,
  `severity` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `data_categories` JSON DEFAULT NULL,
  `records_affected` INT DEFAULT NULL,
  `status` ENUM('detected','investigating','contained','notifying','resolved','closed') NOT NULL DEFAULT 'detected',
  `root_cause` TEXT,
  `containment_actions` TEXT,
  `regulator_notified` TINYINT(1) DEFAULT 0,
  `regulator_notified_at` DATETIME DEFAULT NULL,
  `subjects_notified` TINYINT(1) DEFAULT 0,
  `subjects_notified_at` DATETIME DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference_number`),
  KEY `idx_status` (`status`),
  KEY `idx_jurisdiction` (`jurisdiction`),
  KEY `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Breach i18n
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

-- Breach Incidents
CREATE TABLE IF NOT EXISTS `privacy_breach_incident` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(50) NOT NULL,
  `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
  `incident_date` DATETIME NOT NULL,
  `detected_date` DATETIME NOT NULL,
  `description` TEXT,
  `severity` ENUM('low','medium','high','critical') DEFAULT 'medium',
  `status` VARCHAR(50) DEFAULT 'open',
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Breach Notifications
CREATE TABLE IF NOT EXISTS `privacy_breach_notification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `breach_id` INT UNSIGNED NOT NULL,
  `notification_type` ENUM('regulator','data_subject','internal','other') NOT NULL,
  `recipient` VARCHAR(255) NOT NULL,
  `method` ENUM('email','letter','phone','portal','other') NOT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `acknowledged_at` DATETIME DEFAULT NULL,
  `content` TEXT,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_breach` (`breach_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Processing Activities (ROPA)
CREATE TABLE IF NOT EXISTS `privacy_processing_activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `purpose` TEXT NOT NULL,
  `lawful_basis` VARCHAR(100) DEFAULT NULL,
  `lawful_basis_code` VARCHAR(50) DEFAULT NULL,
  `data_categories` JSON DEFAULT NULL,
  `data_subjects` JSON DEFAULT NULL,
  `recipients` JSON DEFAULT NULL,
  `third_countries` JSON DEFAULT NULL,
  `transfers` TEXT,
  `retention_period` VARCHAR(255) DEFAULT NULL,
  `security_measures` TEXT,
  `dpia_required` TINYINT(1) DEFAULT 0,
  `dpia_completed` TINYINT(1) DEFAULT 0,
  `dpia_date` DATE DEFAULT NULL,
  `status` ENUM('draft','active','under_review','archived') DEFAULT 'draft',
  `owner` VARCHAR(255) DEFAULT NULL,
  `next_review_date` DATE DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jurisdiction` (`jurisdiction`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Processing Activity i18n
CREATE TABLE IF NOT EXISTS `privacy_processing_activity_i18n` (
  `id` INT UNSIGNED NOT NULL,
  `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
  `description` TEXT,
  `purpose_details` TEXT,
  PRIMARY KEY (`id`, `culture`),
  CONSTRAINT `fk_processing_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_processing_activity` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent Management
CREATE TABLE IF NOT EXISTS `privacy_consent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `purpose` TEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent i18n
CREATE TABLE IF NOT EXISTS `privacy_consent_i18n` (
  `id` INT UNSIGNED NOT NULL,
  `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
  `description` TEXT,
  `legal_text` TEXT,
  PRIMARY KEY (`id`, `culture`),
  CONSTRAINT `fk_consent_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_consent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent Records
CREATE TABLE IF NOT EXISTS `privacy_consent_record` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `data_subject_id` VARCHAR(255) NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `consent_given` TINYINT(1) DEFAULT 0,
  `consent_date` DATETIME DEFAULT NULL,
  `withdrawal_date` DATETIME DEFAULT NULL,
  `withdrawn_date` DATE DEFAULT NULL,
  `source` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`data_subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent Log
CREATE TABLE IF NOT EXISTS `privacy_consent_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consent_record_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_consent` (`consent_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Complaints
CREATE TABLE IF NOT EXISTS `privacy_complaint` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
  `complainant_name` VARCHAR(255) NOT NULL,
  `complainant_email` VARCHAR(255) DEFAULT NULL,
  `complainant_phone` VARCHAR(50) DEFAULT NULL,
  `complaint_type` VARCHAR(100) DEFAULT NULL,
  `description` TEXT,
  `status` ENUM('received','investigating','resolved','escalated','closed') DEFAULT 'received',
  `resolution` TEXT,
  `received_date` DATE NOT NULL,
  `resolved_date` DATE DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAIA Requests (South Africa specific)
CREATE TABLE IF NOT EXISTS `privacy_paia_request` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `requester_name` VARCHAR(255) NOT NULL,
  `requester_email` VARCHAR(255) DEFAULT NULL,
  `requester_phone` VARCHAR(50) DEFAULT NULL,
  `requester_id_number` VARCHAR(50) DEFAULT NULL,
  `requester_address` TEXT,
  `request_type` ENUM('personal','third_party','public') NOT NULL DEFAULT 'personal',
  `records_requested` TEXT NOT NULL,
  `purpose` TEXT,
  `format_requested` ENUM('copy','inspection','both') DEFAULT 'copy',
  `status` ENUM('received','processing','granted','refused','partially_granted','transferred','withdrawn') DEFAULT 'received',
  `decision_reason` TEXT,
  `fee_required` DECIMAL(10,2) DEFAULT NULL,
  `fee_paid` TINYINT(1) DEFAULT 0,
  `received_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `assigned_to` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reference` (`reference_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log
CREATE TABLE IF NOT EXISTS `privacy_audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Inventory
CREATE TABLE IF NOT EXISTS `privacy_data_inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `data_type` VARCHAR(100) DEFAULT NULL,
  `storage_location` VARCHAR(255) DEFAULT NULL,
  `retention_period` VARCHAR(100) DEFAULT NULL,
  `is_personal_data` TINYINT(1) DEFAULT 0,
  `is_sensitive` TINYINT(1) DEFAULT 0,
  `processing_activity_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retention Schedule
CREATE TABLE IF NOT EXISTS `privacy_retention_schedule` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_type` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `retention_period` VARCHAR(100) NOT NULL,
  `retention_reason` TEXT,
  `disposal_method` VARCHAR(100) DEFAULT NULL,
  `legal_citation` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates
CREATE TABLE IF NOT EXISTS `privacy_template` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
  `template_type` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `variables` JSON DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`template_type`),
  KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Data
-- =====================================================

-- Default Jurisdictions
INSERT IGNORE INTO `privacy_jurisdiction` (`code`, `name`, `full_name`, `country`, `region`, `regulator`, `regulator_url`, `dsar_days`, `breach_hours`, `effective_date`, `related_laws`, `icon`, `is_active`, `sort_order`) VALUES
('popia', 'POPIA', 'Protection of Personal Information Act', 'South Africa', 'Africa', 'Information Regulator', 'https://www.justice.gov.za/inforeg/', 30, 72, '2021-07-01', '["PAIA", "ECTA", "RICA"]', 'za', 1, 1),
('ndpa', 'NDPA', 'Nigeria Data Protection Act', 'Nigeria', 'Africa', 'Nigeria Data Protection Commission (NDPC)', 'https://ndpc.gov.ng/', 30, 72, '2023-06-14', '["NITDA Act", "Cybercrimes Act"]', 'ng', 0, 2),
('kenya_dpa', 'Kenya DPA', 'Data Protection Act', 'Kenya', 'Africa', 'Office of the Data Protection Commissioner (ODPC)', 'https://www.odpc.go.ke/', 30, 72, '2019-11-25', '["Computer Misuse and Cybercrimes Act"]', 'ke', 0, 3),
('gdpr', 'GDPR', 'General Data Protection Regulation', 'European Union', 'Europe', 'Supervisory Authority (per member state)', 'https://edpb.europa.eu/', 30, 72, '2018-05-25', '["ePrivacy Directive"]', 'eu', 1, 10),
('pipeda', 'PIPEDA', 'Personal Information Protection and Electronic Documents Act', 'Canada', 'North America', 'Office of the Privacy Commissioner of Canada (OPC)', 'https://www.priv.gc.ca/', 30, 0, '2000-01-01', '["CASL", "Provincial privacy laws"]', 'ca', 0, 11),
('ccpa', 'CCPA/CPRA', 'California Consumer Privacy Act / California Privacy Rights Act', 'USA (California)', 'North America', 'California Privacy Protection Agency (CPPA)', 'https://cppa.ca.gov/', 45, 0, '2020-01-01', '["CPRA amendments"]', 'us', 0, 12);
