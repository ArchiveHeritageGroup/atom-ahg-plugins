-- =============================================================================
-- ahgHeritageAccountingPlugin - CORE Heritage Asset Accounting Tables
-- This file contains ONLY the core schema without regional compliance rules
-- Regional rules are in database/regions/*.sql
-- =============================================================================

-- Accounting Standards Registry (populated by regional installs)
CREATE TABLE IF NOT EXISTS `heritage_accounting_standard` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `country` VARCHAR(50) NOT NULL,
    `region_code` VARCHAR(30) NULL COMMENT 'Links to regional config',
    `description` TEXT NULL,
    `capitalisation_required` TINYINT(1) DEFAULT 0,
    `valuation_methods` JSON NULL,
    `disclosure_requirements` JSON NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_standard_code` (`code`),
    KEY `idx_region` (`region_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset Classes (universal)
CREATE TABLE IF NOT EXISTS `heritage_asset_class` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `parent_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_class_code` (`code`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Heritage Assets (core fields)
CREATE TABLE IF NOT EXISTS `heritage_asset` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT NULL,
    `information_object_id` INT NULL,
    `accounting_standard_id` INT UNSIGNED NULL,
    `asset_class_id` INT UNSIGNED NULL,
    `asset_sub_class` VARCHAR(100) NULL,
    `recognition_status` ENUM('recognised','not_recognised','pending') DEFAULT 'pending',
    `recognition_status_reason` TEXT NULL,
    `recognition_date` DATE NULL,
    `measurement_basis` VARCHAR(50) NULL,
    `acquisition_method` VARCHAR(50) NULL,
    `acquisition_date` DATE NULL,
    `acquisition_cost` DECIMAL(15,2) DEFAULT 0,
    `acquisition_currency` VARCHAR(3) DEFAULT 'USD',
    `fair_value_at_acquisition` DECIMAL(15,2) NULL,
    `nominal_value` DECIMAL(15,2) DEFAULT 1,
    `donor_name` VARCHAR(255) NULL,
    `donor_restrictions` TEXT NULL,
    `initial_carrying_amount` DECIMAL(15,2) DEFAULT 0,
    `current_carrying_amount` DECIMAL(15,2) DEFAULT 0,
    `heritage_significance` VARCHAR(50) NULL,
    `significance_statement` TEXT NULL,
    `restrictions_on_use` TEXT NULL,
    `restrictions_on_disposal` TEXT NULL,
    `conservation_requirements` TEXT NULL,
    `insurance_required` TINYINT(1) DEFAULT 0,
    `insurance_value` DECIMAL(15,2) NULL,
    `insurance_policy_number` VARCHAR(100) NULL,
    `insurance_provider` VARCHAR(255) NULL,
    `insurance_expiry_date` DATE NULL,
    `current_location` VARCHAR(255) NULL,
    `storage_conditions` TEXT NULL,
    `condition_rating` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_information_object` (`information_object_id`),
    KEY `idx_standard` (`accounting_standard_id`),
    KEY `idx_class` (`asset_class_id`),
    KEY `idx_recognition` (`recognition_status`),
    FOREIGN KEY (`accounting_standard_id`) REFERENCES `heritage_accounting_standard`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`asset_class_id`) REFERENCES `heritage_asset_class`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valuations History
CREATE TABLE IF NOT EXISTS `heritage_valuation_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id` INT UNSIGNED NOT NULL,
    `valuation_date` DATE NOT NULL,
    `valuation_type` VARCHAR(50) NULL,
    `previous_value` DECIMAL(15,2) NULL,
    `new_value` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `valuation_method` VARCHAR(50) NULL,
    `valuer_name` VARCHAR(255) NULL,
    `valuer_credentials` VARCHAR(255) NULL,
    `valuer_organization` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`asset_id`),
    KEY `idx_date` (`valuation_date`),
    FOREIGN KEY (`asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Impairments
CREATE TABLE IF NOT EXISTS `heritage_impairment_assessment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id` INT UNSIGNED NOT NULL,
    `impairment_date` DATE NOT NULL,
    `impairment_type` VARCHAR(50) NULL,
    `carrying_amount_before` DECIMAL(15,2) NOT NULL,
    `impairment_loss` DECIMAL(15,2) NOT NULL,
    `carrying_amount_after` DECIMAL(15,2) NOT NULL,
    `reason` TEXT NOT NULL,
    `reversible` TINYINT(1) DEFAULT 0,
    `reversal_date` DATE NULL,
    `reversal_amount` DECIMAL(15,2) NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`asset_id`),
    KEY `idx_date` (`impairment_date`),
    FOREIGN KEY (`asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movements/Transfers
CREATE TABLE IF NOT EXISTS `heritage_movement_register` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id` INT UNSIGNED NOT NULL,
    `movement_date` DATE NOT NULL,
    `movement_type` ENUM('acquisition','disposal','transfer','loan_out','loan_return','revaluation','impairment','other') NOT NULL,
    `from_location` VARCHAR(255) NULL,
    `to_location` VARCHAR(255) NULL,
    `reason` TEXT NULL,
    `authorized_by` VARCHAR(255) NULL,
    `insurance_value` DECIMAL(15,2) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`asset_id`),
    KEY `idx_date` (`movement_date`),
    KEY `idx_type` (`movement_type`),
    FOREIGN KEY (`asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Journal Entries
CREATE TABLE IF NOT EXISTS `heritage_journal_entry` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id` INT UNSIGNED NOT NULL,
    `entry_date` DATE NOT NULL,
    `entry_type` VARCHAR(50) NOT NULL,
    `debit_account` VARCHAR(100) NULL,
    `credit_account` VARCHAR(100) NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `description` TEXT NULL,
    `reference` VARCHAR(100) NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`asset_id`),
    KEY `idx_date` (`entry_date`),
    FOREIGN KEY (`asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compliance Rules (populated by regional installs)
CREATE TABLE IF NOT EXISTS `heritage_compliance_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `standard_id` INT UNSIGNED NOT NULL,
    `category` ENUM('recognition', 'measurement', 'disclosure') NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `check_type` ENUM('required_field', 'value_check', 'date_check', 'custom') DEFAULT 'required_field',
    `field_name` VARCHAR(100) NULL,
    `condition` VARCHAR(255) NULL,
    `error_message` VARCHAR(255) NOT NULL,
    `reference` VARCHAR(100) NULL,
    `severity` ENUM('error', 'warning', 'info') DEFAULT 'error',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_standard` (`standard_id`),
    KEY `idx_category` (`category`),
    UNIQUE KEY `uk_standard_code` (`standard_id`, `code`),
    FOREIGN KEY (`standard_id`) REFERENCES `heritage_accounting_standard`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction log
CREATE TABLE IF NOT EXISTS `heritage_transaction_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `heritage_asset_id` INT UNSIGNED NOT NULL,
    `object_id` INT NULL,
    `transaction_type` VARCHAR(50) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `transaction_data` JSON NULL,
    `user_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_heritage_asset` (`heritage_asset_id`),
    INDEX `idx_object` (`object_id`),
    INDEX `idx_type` (`transaction_type`),
    INDEX `idx_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Regional configuration
CREATE TABLE IF NOT EXISTS `heritage_regional_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `region_code` VARCHAR(30) NOT NULL UNIQUE,
    `region_name` VARCHAR(100) NOT NULL,
    `countries` JSON NOT NULL COMMENT 'List of countries in this region',
    `default_currency` VARCHAR(3) DEFAULT 'USD',
    `financial_year_start` VARCHAR(5) DEFAULT '01-01',
    `regulatory_body` VARCHAR(255) NULL,
    `report_formats` JSON NULL,
    `is_installed` TINYINT(1) DEFAULT 0,
    `installed_at` DATETIME NULL,
    `config_data` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Institution/Repository regional settings
CREATE TABLE IF NOT EXISTS `heritage_institution_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repository_id` INT NULL COMMENT 'NULL for global setting',
    `region_code` VARCHAR(30) NOT NULL,
    `accounting_standard_id` INT UNSIGNED NULL,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `financial_year_start` VARCHAR(5) DEFAULT '01-01',
    `tax_registration` VARCHAR(100) NULL,
    `regulatory_registration` VARCHAR(100) NULL,
    `reporting_contact_email` VARCHAR(255) NULL,
    `config_data` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_repository` (`repository_id`),
    KEY `idx_region` (`region_code`),
    FOREIGN KEY (`accounting_standard_id`) REFERENCES `heritage_accounting_standard`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Seed Data: Universal Asset Classes (not region-specific)
-- =============================================================================

INSERT INTO heritage_asset_class (code, name, description, is_active, sort_order) VALUES
('ART', 'Works of Art', 'Paintings, sculptures, prints, photographs', 1, 1),
('ARCH', 'Archives & Manuscripts', 'Historical documents, manuscripts, records', 1, 2),
('BOOKS', 'Rare Books & Libraries', 'Rare books, special collections', 1, 3),
('ARTIFACTS', 'Historical Artifacts', 'Objects of historical significance', 1, 4),
('NATURAL', 'Natural History', 'Specimens, fossils, geological samples', 1, 5),
('BUILDINGS', 'Heritage Buildings', 'Historic structures and monuments', 1, 6),
('LAND', 'Heritage Land & Sites', 'Archaeological sites, heritage landscapes', 1, 7),
('COLLECTIONS', 'Mixed Collections', 'Collections spanning multiple categories', 1, 8),
('INTANGIBLE', 'Intangible Heritage', 'Digital archives, recordings, oral histories', 1, 9),
('OTHER', 'Other Heritage Assets', 'Assets not classified elsewhere', 1, 99)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =============================================================================
-- Seed Data: Available Regions (all inactive until installed)
-- =============================================================================

INSERT INTO heritage_regional_config (region_code, region_name, countries, default_currency, regulatory_body, is_installed) VALUES
('africa_ipsas', 'Africa (IPSAS)', '["Zimbabwe", "Kenya", "Nigeria", "Ghana", "Tanzania", "Uganda", "Rwanda", "Botswana", "Zambia", "Malawi"]', 'USD', 'National Auditor General', 0),
('south_africa_grap', 'South Africa (GRAP)', '["South Africa"]', 'ZAR', 'National Treasury / ASB', 0),
('uk_frs', 'United Kingdom (FRS)', '["United Kingdom", "Ireland"]', 'GBP', 'Charity Commission / FRC', 0),
('usa_government', 'USA Government (GASB)', '["United States"]', 'USD', 'GASB', 0),
('usa_nonprofit', 'USA Non-Profit (FASB)', '["United States"]', 'USD', 'FASB', 0),
('australia_nz', 'Australia & NZ (AASB)', '["Australia", "New Zealand"]', 'AUD', 'AASB / XRB', 0),
('canada_psas', 'Canada (PSAS)', '["Canada"]', 'CAD', 'PSAB', 0),
('international_private', 'International Private (IAS)', '["International"]', 'USD', 'IASB', 0)
ON DUPLICATE KEY UPDATE region_name=VALUES(region_name);
