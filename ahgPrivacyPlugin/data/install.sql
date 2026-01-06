-- =============================================================================
-- ahgPrivacyPlugin - Privacy Compliance Tables
-- Multi-jurisdictional privacy compliance (POPIA, NDPA, GDPR, PIPEDA, CCPA, etc.)
-- =============================================================================

-- Privacy Jurisdiction (admin-configurable)
CREATE TABLE IF NOT EXISTS `privacy_jurisdiction` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(30) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `country` VARCHAR(100) NOT NULL,
    `region` VARCHAR(50) DEFAULT 'Africa',
    `regulator` VARCHAR(255) NULL,
    `regulator_url` VARCHAR(255) NULL,
    `dsar_days` INT DEFAULT 30,
    `breach_hours` INT DEFAULT 72,
    `effective_date` DATE NULL,
    `related_laws` JSON NULL,
    `icon` VARCHAR(10) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 99,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_jurisdiction_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Privacy Config (per jurisdiction settings)
CREATE TABLE IF NOT EXISTS `privacy_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
    `organization_name` VARCHAR(255) NULL,
    `registration_number` VARCHAR(100) NULL,
    `data_protection_email` VARCHAR(255) NULL,
    `dsar_response_days` INT DEFAULT 30,
    `breach_notification_hours` INT DEFAULT 72,
    `settings` JSON NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jurisdiction` (`jurisdiction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Subject Access Requests (DSAR)
CREATE TABLE IF NOT EXISTS `privacy_dsar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
    `request_type` VARCHAR(50) NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL,
    `subject_email` VARCHAR(255) NULL,
    `subject_phone` VARCHAR(50) NULL,
    `subject_id_type` VARCHAR(50) NULL,
    `subject_id_number` VARCHAR(100) NULL,
    `description` TEXT NULL,
    `status` ENUM('received','acknowledged','in_progress','extended','completed','rejected','withdrawn') DEFAULT 'received',
    `priority` ENUM('low','normal','high','urgent') DEFAULT 'normal',
    `received_date` DATE NOT NULL,
    `acknowledged_date` DATE NULL,
    `due_date` DATE NULL,
    `completed_date` DATE NULL,
    `extension_reason` TEXT NULL,
    `response_summary` TEXT NULL,
    `assigned_to` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_status` (`status`),
    KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Breaches
CREATE TABLE IF NOT EXISTS `privacy_breach` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `breach_type` VARCHAR(50) NULL,
    `severity` ENUM('low','medium','high','critical') DEFAULT 'medium',
    `detected_date` DATETIME NOT NULL,
    `occurred_date` DATETIME NULL,
    `reported_date` DATETIME NULL,
    `data_subjects_affected` INT DEFAULT 0,
    `data_categories` JSON NULL,
    `containment_actions` TEXT NULL,
    `remediation_actions` TEXT NULL,
    `notification_required` TINYINT(1) DEFAULT 0,
    `regulator_notified` TINYINT(1) DEFAULT 0,
    `regulator_notified_date` DATETIME NULL,
    `subjects_notified` TINYINT(1) DEFAULT 0,
    `subjects_notified_date` DATETIME NULL,
    `status` ENUM('detected','investigating','contained','resolved','closed') DEFAULT 'detected',
    `root_cause` TEXT NULL,
    `lessons_learned` TEXT NULL,
    `assigned_to` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_status` (`status`),
    KEY `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Record of Processing Activities (ROPA)
CREATE TABLE IF NOT EXISTS `privacy_processing_activity` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `purpose` TEXT NOT NULL,
    `lawful_basis` VARCHAR(100) NULL,
    `data_categories` JSON NULL,
    `data_subjects` JSON NULL,
    `recipients` JSON NULL,
    `third_countries` JSON NULL,
    `retention_period` VARCHAR(255) NULL,
    `security_measures` TEXT NULL,
    `dpia_required` TINYINT(1) DEFAULT 0,
    `dpia_completed` TINYINT(1) DEFAULT 0,
    `dpia_date` DATE NULL,
    `status` ENUM('draft','active','under_review','archived') DEFAULT 'draft',
    `owner` VARCHAR(255) NULL,
    `next_review_date` DATE NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Consent Records
CREATE TABLE IF NOT EXISTS `privacy_consent_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
    `subject_identifier` VARCHAR(255) NOT NULL,
    `consent_type` VARCHAR(100) NOT NULL,
    `purpose` TEXT NOT NULL,
    `status` ENUM('active','withdrawn','expired') DEFAULT 'active',
    `granted_date` DATETIME NOT NULL,
    `withdrawn_date` DATETIME NULL,
    `expiry_date` DATE NULL,
    `consent_method` VARCHAR(100) NULL,
    `evidence` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_status` (`status`),
    KEY `idx_subject` (`subject_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Privacy Complaints
CREATE TABLE IF NOT EXISTS `privacy_complaint` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `jurisdiction` VARCHAR(30) NOT NULL DEFAULT 'popia',
    `complaint_type` VARCHAR(50) NOT NULL,
    `complainant_name` VARCHAR(255) NOT NULL,
    `complainant_email` VARCHAR(255) NULL,
    `complainant_phone` VARCHAR(50) NULL,
    `description` TEXT NOT NULL,
    `date_of_incident` DATE NULL,
    `status` ENUM('received','investigating','resolved','escalated','closed') DEFAULT 'received',
    `assigned_to` INT UNSIGNED NULL,
    `resolution` TEXT NULL,
    `resolved_date` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAIA Requests (South Africa specific)
CREATE TABLE IF NOT EXISTS `privacy_paia_request` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `requester_name` VARCHAR(255) NOT NULL,
    `requester_email` VARCHAR(255) NULL,
    `requester_phone` VARCHAR(50) NULL,
    `requester_address` TEXT NULL,
    `request_type` ENUM('personal','third_party','public') DEFAULT 'personal',
    `record_description` TEXT NOT NULL,
    `access_form` ENUM('inspect','copy','both') DEFAULT 'copy',
    `status` ENUM('received','processing','granted','partially_granted','refused','transferred') DEFAULT 'received',
    `received_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `decision_date` DATE NULL,
    `refusal_reason` TEXT NULL,
    `fees_required` DECIMAL(10,2) DEFAULT 0,
    `fees_paid` DECIMAL(10,2) DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reference` (`reference_number`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Information Officers (POPIA specific)
CREATE TABLE IF NOT EXISTS `privacy_information_officer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jurisdiction` VARCHAR(30) DEFAULT 'popia',
    `officer_type` ENUM('information_officer','deputy_information_officer') DEFAULT 'information_officer',
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `department` VARCHAR(255) NULL,
    `registration_number` VARCHAR(100) NULL,
    `appointed_date` DATE NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jurisdiction` (`jurisdiction`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Seed Data: Jurisdictions
-- =============================================================================

INSERT INTO privacy_jurisdiction 
(code, name, full_name, country, region, regulator, regulator_url, dsar_days, breach_hours, effective_date, related_laws, icon, is_active, sort_order)
VALUES
('popia', 'POPIA', 'Protection of Personal Information Act', 'South Africa', 'Africa', 
 'Information Regulator', 'https://www.justice.gov.za/inforeg/', 30, 72, '2021-07-01',
 '["PAIA", "ECTA", "RICA"]', 'za', 1, 1),
('ndpa', 'NDPA', 'Nigeria Data Protection Act', 'Nigeria', 'Africa',
 'Nigeria Data Protection Commission (NDPC)', 'https://ndpc.gov.ng/', 30, 72, '2023-06-14',
 '["NITDA Act", "Cybercrimes Act"]', 'ng', 1, 2),
('kenya_dpa', 'Kenya DPA', 'Data Protection Act', 'Kenya', 'Africa',
 'Office of the Data Protection Commissioner (ODPC)', 'https://www.odpc.go.ke/', 30, 72, '2019-11-25',
 '["Computer Misuse and Cybercrimes Act"]', 'ke', 1, 3),
('gdpr', 'GDPR', 'General Data Protection Regulation', 'European Union', 'Europe',
 'Supervisory Authority (per member state)', 'https://edpb.europa.eu/', 30, 72, '2018-05-25',
 '["ePrivacy Directive"]', 'eu', 1, 10),
('pipeda', 'PIPEDA', 'Personal Information Protection and Electronic Documents Act', 'Canada', 'North America',
 'Office of the Privacy Commissioner of Canada (OPC)', 'https://www.priv.gc.ca/', 30, 0, '2000-01-01',
 '["CASL", "Provincial privacy laws"]', 'ca', 1, 11),
('ccpa', 'CCPA/CPRA', 'California Consumer Privacy Act / California Privacy Rights Act', 'USA (California)', 'North America',
 'California Privacy Protection Agency (CPPA)', 'https://cppa.ca.gov/', 45, 0, '2020-01-01',
 '["CPRA amendments"]', 'us', 1, 12)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =====================================================
-- I18N Tables for multi-language support
-- =====================================================

CREATE TABLE IF NOT EXISTS `privacy_dsar_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `description` TEXT NULL,
    `notes` TEXT NULL,
    `response_summary` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_dsar_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_dsar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_breach_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `description` TEXT NULL,
    `impact_assessment` TEXT NULL,
    `remediation_notes` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_breach_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_breach` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_processing_activity_i18n` (
    `id` INT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `description` TEXT NULL,
    `purpose_details` TEXT NULL,
    PRIMARY KEY (`id`, `culture`),
    CONSTRAINT `fk_processing_i18n` FOREIGN KEY (`id`) REFERENCES `privacy_processing_activity` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `privacy_officer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `role` VARCHAR(100) NULL DEFAULT 'Information Officer',
    `registration_number` VARCHAR(100) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
