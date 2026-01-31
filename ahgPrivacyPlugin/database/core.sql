-- =====================================================
-- AHG Privacy Plugin - Core Schema (Regional Architecture)
-- Version: 2.0.0
--
-- This installs the core privacy tables without jurisdiction-specific data.
-- Jurisdictions are installed separately via:
--   php symfony privacy:jurisdiction --install=<code>
-- =====================================================

-- Drop ENUM constraints and recreate with VARCHAR for flexibility
-- This allows jurisdictions to be added dynamically

-- Privacy Jurisdiction Registry (defines available jurisdictions)
CREATE TABLE IF NOT EXISTS `privacy_jurisdiction_registry` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `region` VARCHAR(50) DEFAULT 'International',
  `regulator` VARCHAR(255) DEFAULT NULL,
  `regulator_url` VARCHAR(500) DEFAULT NULL,
  `dsar_days` INT NOT NULL DEFAULT 30,
  `breach_hours` INT NOT NULL DEFAULT 72,
  `effective_date` DATE DEFAULT NULL,
  `related_laws` JSON DEFAULT NULL,
  `icon` VARCHAR(10) DEFAULT NULL,
  `default_currency` VARCHAR(3) DEFAULT 'USD',
  `is_installed` TINYINT(1) NOT NULL DEFAULT 0,
  `installed_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT DEFAULT 99,
  `config_data` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_jurisdiction_code` (`code`),
  KEY `idx_installed` (`is_installed`),
  KEY `idx_active` (`is_active`),
  KEY `idx_region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Lawful Bases per Jurisdiction
CREATE TABLE IF NOT EXISTS `privacy_lawful_basis` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction_code` VARCHAR(30) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `legal_reference` VARCHAR(100) DEFAULT NULL,
  `requires_consent` TINYINT(1) DEFAULT 0,
  `requires_lia` TINYINT(1) DEFAULT 0 COMMENT 'Legitimate Interest Assessment',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_jurisdiction_basis` (`jurisdiction_code`, `code`),
  KEY `idx_jurisdiction` (`jurisdiction_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Special Categories per Jurisdiction
CREATE TABLE IF NOT EXISTS `privacy_special_category` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction_code` VARCHAR(30) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `legal_reference` VARCHAR(100) DEFAULT NULL,
  `requires_explicit_consent` TINYINT(1) DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_jurisdiction_category` (`jurisdiction_code`, `code`),
  KEY `idx_jurisdiction` (`jurisdiction_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Request Types per Jurisdiction
CREATE TABLE IF NOT EXISTS `privacy_request_type` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction_code` VARCHAR(30) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `legal_reference` VARCHAR(100) DEFAULT NULL,
  `response_days` INT DEFAULT NULL COMMENT 'Override jurisdiction default',
  `fee_allowed` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_jurisdiction_request` (`jurisdiction_code`, `code`),
  KEY `idx_jurisdiction` (`jurisdiction_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Jurisdiction Compliance Rules
CREATE TABLE IF NOT EXISTS `privacy_compliance_rule` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurisdiction_code` VARCHAR(30) NOT NULL,
  `category` VARCHAR(50) NOT NULL COMMENT 'dsar, breach, ropa, consent, retention',
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `check_type` VARCHAR(50) NOT NULL COMMENT 'required_field, value_check, date_check, custom',
  `field_name` VARCHAR(100) DEFAULT NULL,
  `condition` VARCHAR(255) DEFAULT NULL,
  `error_message` TEXT,
  `legal_reference` VARCHAR(100) DEFAULT NULL,
  `severity` ENUM('error', 'warning', 'info') DEFAULT 'error',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_jurisdiction_rule` (`jurisdiction_code`, `code`),
  KEY `idx_jurisdiction` (`jurisdiction_code`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Active Jurisdiction Configuration (per repository/global)
CREATE TABLE IF NOT EXISTS `privacy_institution_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `repository_id` INT DEFAULT NULL COMMENT 'NULL = global',
  `jurisdiction_code` VARCHAR(30) NOT NULL,
  `organization_name` VARCHAR(255) DEFAULT NULL,
  `registration_number` VARCHAR(100) DEFAULT NULL,
  `privacy_officer_id` INT UNSIGNED DEFAULT NULL,
  `data_protection_email` VARCHAR(255) DEFAULT NULL,
  `dsar_response_days` INT DEFAULT NULL COMMENT 'Override jurisdiction default',
  `breach_notification_hours` INT DEFAULT NULL COMMENT 'Override jurisdiction default',
  `retention_default_years` INT DEFAULT 5,
  `settings` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_repository` (`repository_id`),
  KEY `idx_jurisdiction` (`jurisdiction_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- Seed Available Jurisdictions (metadata only, not installed)
-- =====================================================

INSERT INTO `privacy_jurisdiction_registry`
(`code`, `name`, `full_name`, `country`, `region`, `regulator`, `regulator_url`, `dsar_days`, `breach_hours`, `effective_date`, `icon`, `default_currency`, `is_installed`, `is_active`, `sort_order`)
VALUES
('popia', 'POPIA', 'Protection of Personal Information Act', 'South Africa', 'Africa', 'Information Regulator', 'https://inforegulator.org.za/', 30, 72, '2021-07-01', '🇿🇦', 'ZAR', 0, 0, 1),
('gdpr', 'GDPR', 'General Data Protection Regulation', 'European Union', 'Europe', 'European Data Protection Board', 'https://edpb.europa.eu/', 30, 72, '2018-05-25', '🇪🇺', 'EUR', 0, 0, 2),
('uk_gdpr', 'UK GDPR', 'UK General Data Protection Regulation', 'United Kingdom', 'Europe', 'Information Commissioner''s Office', 'https://ico.org.uk/', 30, 72, '2021-01-01', '🇬🇧', 'GBP', 0, 0, 3),
('pipeda', 'PIPEDA', 'Personal Information Protection and Electronic Documents Act', 'Canada', 'North America', 'Office of the Privacy Commissioner', 'https://www.priv.gc.ca/', 30, 72, '2000-01-01', '🇨🇦', 'CAD', 0, 0, 4),
('ccpa', 'CCPA/CPRA', 'California Consumer Privacy Act / California Privacy Rights Act', 'United States (California)', 'North America', 'California Privacy Protection Agency', 'https://cppa.ca.gov/', 45, 72, '2020-01-01', '🇺🇸', 'USD', 0, 0, 5),
('ndpa', 'NDPA', 'Nigeria Data Protection Act', 'Nigeria', 'Africa', 'Nigeria Data Protection Commission', 'https://ndpc.gov.ng/', 30, 72, '2023-06-14', '🇳🇬', 'NGN', 0, 0, 6),
('kenya_dpa', 'Kenya DPA', 'Data Protection Act', 'Kenya', 'Africa', 'Office of the Data Protection Commissioner', 'https://www.odpc.go.ke/', 30, 72, '2019-11-25', '🇰🇪', 'KES', 0, 0, 7),
('lgpd', 'LGPD', 'Lei Geral de Proteção de Dados', 'Brazil', 'South America', 'ANPD - Autoridade Nacional de Proteção de Dados', 'https://www.gov.br/anpd/', 15, 72, '2020-09-18', '🇧🇷', 'BRL', 0, 0, 8),
('australia_privacy', 'Privacy Act', 'Privacy Act 1988 (APPs)', 'Australia', 'Oceania', 'Office of the Australian Information Commissioner', 'https://www.oaic.gov.au/', 30, 72, '1988-12-01', '🇦🇺', 'AUD', 0, 0, 9),
('pdpa_sg', 'PDPA', 'Personal Data Protection Act', 'Singapore', 'Asia', 'Personal Data Protection Commission', 'https://www.pdpc.gov.sg/', 30, 72, '2012-10-15', '🇸🇬', 'SGD', 0, 0, 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Migration note: Run this after installation to migrate
-- existing jurisdiction data:
-- UPDATE privacy_jurisdiction_registry pjr
-- SET pjr.is_installed = 1, pjr.is_active = 1, pjr.installed_at = NOW()
-- WHERE pjr.code IN (SELECT code FROM privacy_jurisdiction WHERE is_active = 1);
-- =====================================================
