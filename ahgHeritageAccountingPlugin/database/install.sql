-- =============================================================================
-- ahgHeritageAccountingPlugin - Heritage Asset Accounting Tables
-- Multi-standard support (GRAP 103, FRS 102, GASB 34, IPSAS 45, etc.)
-- =============================================================================

-- Accounting Standards (admin-configurable)
CREATE TABLE IF NOT EXISTS `heritage_accounting_standard` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `region_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `capitalisation_required` tinyint(1) DEFAULT '0',
  `valuation_methods` json DEFAULT NULL,
  `disclosure_requirements` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_region` (`region_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asset Classes
CREATE TABLE IF NOT EXISTS `heritage_asset_class` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_id` int unsigned DEFAULT NULL,
  `default_useful_life` int DEFAULT NULL,
  `default_depreciation_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_depreciable` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `heritage_asset_class_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `heritage_asset_class` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Heritage Assets
CREATE TABLE IF NOT EXISTS `heritage_asset` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `information_object_id` int DEFAULT NULL,
  `object_id` int DEFAULT NULL,
  `accounting_standard_id` int unsigned DEFAULT NULL,
  `recognition_status` varchar(56) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'recognised, not_recognised, pending, derecognised',
  `recognition_status_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recognition_date` date DEFAULT NULL,
  `asset_class_id` int unsigned DEFAULT NULL,
  `asset_sub_class` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `measurement_basis` varchar(49) COLLATE utf8mb4_unicode_ci DEFAULT 'cost' COMMENT 'cost, fair_value, nominal, not_practicable',
  `acquisition_method` varchar(65) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'purchase, donation, bequest, transfer, found, exchange, other',
  `acquisition_date` date DEFAULT NULL,
  `acquisition_cost` decimal(18,2) DEFAULT '0.00',
  `fair_value_at_acquisition` decimal(18,2) DEFAULT NULL,
  `nominal_value` decimal(18,2) DEFAULT '1.00',
  `donor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `donor_restrictions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `initial_carrying_amount` decimal(18,2) DEFAULT '0.00',
  `current_carrying_amount` decimal(18,2) DEFAULT '0.00',
  `accumulated_depreciation` decimal(18,2) DEFAULT '0.00',
  `revaluation_surplus` decimal(18,2) DEFAULT '0.00',
  `impairment_loss` decimal(18,2) DEFAULT '0.00',
  `last_valuation_date` date DEFAULT NULL,
  `last_valuation_amount` decimal(18,2) DEFAULT NULL,
  `valuation_method` varchar(51) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'market, cost, income, expert, insurance, other',
  `valuer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valuer_credentials` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valuation_report_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revaluation_frequency` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT 'as_needed' COMMENT 'annual, triennial, quinquennial, as_needed, not_applicable',
  `depreciation_policy` varchar(76) COLLATE utf8mb4_unicode_ci DEFAULT 'not_depreciated' COMMENT 'not_depreciated, straight_line, reducing_balance, units_of_production',
  `useful_life_years` int DEFAULT NULL,
  `residual_value` decimal(18,2) DEFAULT '0.00',
  `annual_depreciation` decimal(18,2) DEFAULT '0.00',
  `last_impairment_date` date DEFAULT NULL,
  `impairment_indicators` tinyint(1) DEFAULT '0',
  `impairment_indicators_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recoverable_amount` decimal(18,2) DEFAULT NULL,
  `derecognition_date` date DEFAULT NULL,
  `derecognition_reason` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'disposal, destruction, loss, transfer, write_off, other',
  `derecognition_proceeds` decimal(18,2) DEFAULT NULL,
  `gain_loss_on_derecognition` decimal(18,2) DEFAULT NULL,
  `heritage_significance` varchar(37) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'exceptional, high, medium, low',
  `significance_statement` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `restrictions_on_use` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `restrictions_on_disposal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `conservation_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `insurance_required` tinyint(1) DEFAULT '1',
  `insurance_value` decimal(18,2) DEFAULT NULL,
  `insurance_policy_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_expiry_date` date DEFAULT NULL,
  `current_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `condition_rating` varchar(43) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'excellent, good, fair, poor, critical',
  `last_condition_assessment` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_object` (`object_id`),
  KEY `idx_standard` (`accounting_standard_id`),
  KEY `idx_class` (`asset_class_id`),
  KEY `idx_recognition` (`recognition_status`),
  KEY `idx_acquisition_date` (`acquisition_date`),
  KEY `idx_valuation_date` (`last_valuation_date`),
  KEY `idx_heritage_io` (`information_object_id`),
  CONSTRAINT `heritage_asset_ibfk_2` FOREIGN KEY (`accounting_standard_id`) REFERENCES `heritage_accounting_standard` (`id`) ON DELETE SET NULL,
  CONSTRAINT `heritage_asset_ibfk_3` FOREIGN KEY (`asset_class_id`) REFERENCES `heritage_asset_class` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valuations History
CREATE TABLE IF NOT EXISTS `heritage_valuation_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `heritage_asset_id` INT UNSIGNED NOT NULL,
    `valuation_date` DATE NOT NULL,
    `valuation_type` VARCHAR(50) NULL,
    `previous_value` DECIMAL(15,2) NULL,
    `new_value` DECIMAL(15,2) NOT NULL,
    `valuation_method` VARCHAR(50) NULL,
    `valuer_name` VARCHAR(255) NULL,
    `valuer_credentials` VARCHAR(255) NULL,
    `valuer_organization` VARCHAR(255) NULL,
    `valuer_id` INT UNSIGNED NULL,
    -- Written by HeritageAssetService::addValuation() (new_value minus the
    -- previous carrying amount). It was added to live databases by a migration
    -- and never to this file, so a fresh install had a service that always
    -- failed with "Unknown column 'valuation_change'" while an upgraded one
    -- worked. Classic CREATE TABLE IF NOT EXISTS drift: the table exists, so the
    -- statement is skipped, and the two schemas never converge.
    `valuation_change` DECIMAL(15,2) NULL,
    `valuation_report_reference` VARCHAR(100) NULL,
    -- The surplus/deficit split GRAP 103.74(e) and IPSAS 45.88(e) require.
    -- The column exists on upgraded installs; nothing writes it yet.
    `revaluation_surplus_change` DECIMAL(15,2) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`heritage_asset_id`),
    KEY `idx_date` (`valuation_date`),
    FOREIGN KEY (`heritage_asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Impairments
-- Impairment assessment, matching the schema in production. The installer
-- previously shipped an older 13-column shape keyed on `asset_id` with a single
-- `impairment_type`; the real design records the IPSAS/GRAP indicators
-- (physical damage, obsolescence, change in use, external factors) and the
-- recoverable amount, and keys on `heritage_asset_id`. A fresh install got the
-- old shape, so any code written against production failed on column names.
CREATE TABLE IF NOT EXISTS `heritage_impairment_assessment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `heritage_asset_id` int unsigned NOT NULL,
  `assessment_date` date NOT NULL,
  `physical_damage` tinyint(1) DEFAULT '0',
  `physical_damage_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `obsolescence` tinyint(1) DEFAULT '0',
  `obsolescence_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `change_in_use` tinyint(1) DEFAULT '0',
  `change_in_use_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `external_factors` tinyint(1) DEFAULT '0',
  `external_factors_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `impairment_identified` tinyint(1) DEFAULT '0',
  `carrying_amount_before` decimal(18,2) DEFAULT NULL,
  `recoverable_amount` decimal(18,2) DEFAULT NULL,
  `impairment_loss` decimal(18,2) DEFAULT NULL,
  `carrying_amount_after` decimal(18,2) DEFAULT NULL,
  `reversal_applicable` tinyint(1) DEFAULT '0',
  `reversal_amount` decimal(18,2) DEFAULT NULL,
  `reversal_date` date DEFAULT NULL,
  `assessor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asset` (`heritage_asset_id`),
  KEY `idx_date` (`assessment_date`),
  CONSTRAINT `heritage_impairment_assessment_ibfk_1` FOREIGN KEY (`heritage_asset_id`) REFERENCES `heritage_asset` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movements/Transfers
CREATE TABLE IF NOT EXISTS `heritage_movement_register` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `heritage_asset_id` INT UNSIGNED NOT NULL,
    `movement_date` DATE NOT NULL,
    `movement_type` VARCHAR(98) COMMENT 'acquisition, disposal, transfer, loan_out, loan_return, revaluation, impairment, other' NOT NULL,
    `from_location` VARCHAR(255) NULL,
    `to_location` VARCHAR(255) NULL,
    `reason` TEXT NULL,
    `authorized_by` VARCHAR(255) NULL,
    -- Loan/movement lifecycle. Present on upgraded installs via migration and
    -- never in this file, so a fresh install has a narrower table than the
    -- reports expect.
    `authorization_date` DATE NULL,
    `expected_return_date` DATE NULL,
    `actual_return_date` DATE NULL,
    `condition_on_departure` TEXT NULL,
    `condition_on_return` TEXT NULL,
    `condition_notes` TEXT NULL,
    `insurance_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
    `insurance_value` DECIMAL(15,2) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`heritage_asset_id`),
    KEY `idx_date` (`movement_date`),
    KEY `idx_type` (`movement_type`),
    FOREIGN KEY (`heritage_asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Journal Entries
-- Double-entry journal.
--
-- This definition was a whole design behind the code: it declared
-- entry_date/entry_type/amount while every query uses journal_date, journal_type
-- and the debit/credit pair. migrations/005_fix_journal_and_movement_columns.php
-- has renamed and extended it for a long time, and that migration never reached
-- this file. So an upgraded install worked and a fresh one died with
-- "Unknown column 'journal_date' in 'order clause'" the moment a heritage asset
-- was opened. CREATE TABLE IF NOT EXISTS skips the statement when the table is
-- already present, so the two shapes never converge on their own.
--
-- Kept deliberately identical to what 005 produces - column for column, type for
-- type - so a fresh install and a migrated one end up the same. 005 CHANGEs the
-- old names rather than keeping them, so there are no aliases to retain here.
CREATE TABLE IF NOT EXISTS `heritage_journal_entry` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `heritage_asset_id` INT UNSIGNED NOT NULL,
    `journal_date` DATE NOT NULL,
    `journal_number` VARCHAR(50) NULL,
    `journal_type` VARCHAR(120) NOT NULL
        COMMENT 'recognition, revaluation, depreciation, impairment, impairment_reversal, derecognition, adjustment, transfer',
    `debit_account` VARCHAR(50) NOT NULL,
    `debit_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
    `credit_account` VARCHAR(50) NOT NULL,
    `credit_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
    `description` TEXT NULL,
    `reference_document` VARCHAR(255) NULL,
    `fiscal_year` INT NULL,
    `fiscal_period` INT NULL,
    `posted` TINYINT(1) DEFAULT 0,
    `posted_by` INT NULL,
    `posted_at` DATETIME NULL,
    `reversed` TINYINT(1) DEFAULT 0,
    `reversal_journal_id` INT UNSIGNED NULL,
    `reversal_date` DATE NULL,
    `reversal_reason` TEXT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asset` (`heritage_asset_id`),
    KEY `idx_date` (`journal_date`),
    KEY `idx_type` (`journal_type`),
    KEY `idx_fiscal_year` (`fiscal_year`),
    KEY `idx_posted` (`posted`),
    FOREIGN KEY (`heritage_asset_id`) REFERENCES `heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compliance Rules (database-driven)
CREATE TABLE IF NOT EXISTS `heritage_compliance_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `standard_id` INT UNSIGNED NOT NULL,
    `category` VARCHAR(48) COMMENT 'recognition, measurement, disclosure' NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `check_type` VARCHAR(59) COMMENT 'required_field, value_check, date_check, custom' DEFAULT 'required_field',
    `field_name` VARCHAR(100) NULL,
    `condition` VARCHAR(255) NULL,
    `error_message` VARCHAR(255) NOT NULL,
    `reference` VARCHAR(100) NULL,
    `severity` VARCHAR(32) COMMENT 'error, warning, info' DEFAULT 'error',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_standard` (`standard_id`),
    KEY `idx_category` (`category`),
    FOREIGN KEY (`standard_id`) REFERENCES `heritage_accounting_standard`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- Seed Data: Accounting Standards
-- =============================================================================

INSERT INTO heritage_accounting_standard 
(code, name, country, description, capitalisation_required, valuation_methods, disclosure_requirements, is_active, sort_order)
VALUES
('GRAP103', 'GRAP 103 Heritage Assets', 'South Africa', 
 'Generally Recognised Accounting Practice - dedicated heritage assets standard for SA public sector',
 1, '["cost", "fair_value", "deemed_cost", "nominal"]',
 '["asset_class", "measurement_basis", "carrying_amount", "restrictions", "conservation"]', 1, 1),
('FRS102', 'FRS 102 Section 34', 'United Kingdom',
 'Financial Reporting Standard - Section 34.49-56 Heritage Assets',
 0, '["cost", "valuation", "nominal"]',
 '["nature_of_holdings", "policy", "carrying_amount", "restrictions"]', 1, 2),
('GASB34', 'GASB Statement 34', 'United States',
 'Governmental Accounting Standards Board - infrastructure and collections',
 0, '["cost", "fair_value"]',
 '["collection_description", "capitalisation_policy"]', 1, 3),
('FASB958', 'FASB ASC 958', 'United States',
 'Financial Accounting Standards Board - Not-for-profit entities collections',
 0, '["cost", "fair_value", "nominal"]',
 '["collection_description", "capitalisation_policy", "stewardship"]', 1, 4),
('PSAS3150', 'PSAS 3150 Tangible Capital Assets', 'Canada',
 'Public Sector Accounting Standard - includes heritage/works of art',
 0, '["cost", "deemed_cost"]',
 '["measurement_basis", "useful_life", "restrictions"]', 1, 5),
('IPSAS45', 'IPSAS 45 Property, Plant & Equipment', 'International (Africa)',
 'International Public Sector Accounting Standard - covers heritage assets. Used by Nigeria, Kenya, Ghana, Tanzania, Uganda, Rwanda, Botswana, Zimbabwe.',
 0, '["cost", "fair_value", "deemed_cost"]',
 '["asset_class", "measurement_basis", "useful_life", "depreciation_method", "reconciliation"]', 1, 6),
('IPSAS17', 'IPSAS 17 Property, Plant & Equipment (Legacy)', 'International',
 'Previous IPSAS standard for PPE including heritage - replaced by IPSAS 45',
 0, '["cost", "revaluation"]',
 '["measurement_basis", "depreciation", "reconciliation"]', 1, 7),
('AASB116', 'AASB 116 / PBE IPSAS 17', 'Australia / New Zealand',
 'Australian Accounting Standards Board - Property, Plant & Equipment including heritage. Based on IPSAS.',
 0, '["cost", "revaluation", "fair_value"]',
 '["measurement_basis", "depreciation_method", "useful_life", "reconciliation"]', 1, 8),
('IAS16', 'IAS 16 Property, Plant & Equipment', 'International (Private Sector)',
 'International Accounting Standard for private sector museums, galleries and cultural institutions.',
 0, '["cost", "revaluation"]',
 '["measurement_basis", "depreciation", "useful_life", "impairment"]', 1, 9),
('CUSTOM', 'Custom / Local Standard', 'Other / Custom',
 'For institutions using local accounting standards or custom requirements not covered by other standards.',
 0, '["cost", "fair_value", "nominal", "insurance", "replacement"]',
 NULL, 1, 99)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- =============================================================================
-- Seed Data: Asset Classes
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
-- Seed Data: Compliance Rules
-- =============================================================================

-- GRAP 103 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Class Required' as name, 
           'Heritage asset must have an asset class' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required for GRAP 103 compliance' as error_message,
           'GRAP 103.14' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Recognition Date Required', 
           'Date when asset was recognised must be recorded',
           'required_field', 'recognition_date', NULL,
           'Recognition date is required', 'GRAP 103.14', 'error', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Significance Statement', 
           'Heritage significance must be documented',
           'required_field', 'significance_statement', NULL,
           'Heritage significance statement is required', 'GRAP 103.74', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Measurement Basis Required', 
           'Measurement basis must be specified',
           'required_field', 'measurement_basis', NULL,
           'Measurement basis is required', 'GRAP 103.26', 'error', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount Required', 
           'Current carrying amount must be recorded',
           'value_check', 'current_carrying_amount', '>0',
           'Current carrying amount must be greater than zero', 'GRAP 103.26-28', 'error', 11
    UNION ALL SELECT 'measurement', 'MEA003', 'Acquisition Date', 
           'Acquisition date should be recorded',
           'required_field', 'acquisition_date', NULL,
           'Acquisition date is recommended', 'GRAP 103.36', 'warning', 12
    UNION ALL SELECT 'disclosure', 'DIS001', 'Restrictions on Use', 
           'Any restrictions on use must be disclosed',
           'required_field', 'restrictions_on_use', NULL,
           'Restrictions on use should be documented', 'GRAP 103.74(a)', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Conservation Requirements', 
           'Conservation requirements must be disclosed',
           'required_field', 'conservation_requirements', NULL,
           'Conservation requirements should be documented', 'GRAP 103.74(b)', 'warning', 21
) r
WHERE s.code = 'GRAP103'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- FRS 102 Rules (9 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Class Required' as name,
           'Heritage asset must be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required' as error_message,
           'FRS 102.34.49' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Significance Statement',
           'Heritage characteristics must be documented',
           'required_field', 'significance_statement', NULL,
           'Heritage characteristics/significance should be documented', 'FRS 102.34.50', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Recognition Date',
           'Date of recognition/acquisition',
           'required_field', 'recognition_date', NULL,
           'Recognition date should be recorded', 'FRS 102.34.51', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Measurement Basis',
           'Indicate if cost or valuation basis used',
           'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'FRS 102.34.52', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Carrying amount if capitalised',
           'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded if capitalised', 'FRS 102.34.52', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Nature of Holdings',
           'Disclose nature and scale of heritage assets',
           'required_field', 'heritage_significance', NULL,
           'Nature of holdings should be documented', 'FRS 102.34.55', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Preservation Policy',
           'Preservation and management policy',
           'required_field', 'conservation_requirements', NULL,
           'Preservation policy should be documented', 'FRS 102.34.55', 'info', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Accounting Policy',
           'Disclose accounting policy adopted',
           'required_field', 'measurement_basis', NULL,
           'Accounting policy should be documented', 'FRS 102.34.55(a)', 'warning', 22
    UNION ALL SELECT 'disclosure', 'DIS004', 'Restrictions',
           'Disclose any restrictions on disposal',
           'required_field', 'restrictions_on_disposal', NULL,
           'Restrictions on disposal should be documented', 'FRS 102.34.55(c)', 'info', 23
) r
WHERE s.code = 'FRS102'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- GASB 34 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Collection Designation' as name,
           'Must indicate if part of collection' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class/collection designation required' as error_message,
           'GASB 34.27' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Acquisition Date',
           'Date asset was acquired', 'required_field', 'acquisition_date', NULL,
           'Acquisition date should be recorded', 'GASB 34.18', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Acquisition Method',
           'How asset was acquired', 'required_field', 'acquisition_method', NULL,
           'Acquisition method should be documented', 'GASB 34.18', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Historical Cost',
           'Historical cost if capitalised', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'GASB 34.18', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Current carrying amount', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded', 'GASB 34.19', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Collection Description',
           'Description of collection if not capitalised', 'required_field', 'significance_statement', NULL,
           'Collection description recommended', 'GASB 34.118', 'info', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Collection Criteria',
           'Criteria for adding to collection', 'required_field', 'heritage_significance', NULL,
           'Collection criteria should be documented', 'GASB 34.27', 'info', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Conservation Policy',
           'Policy for preservation', 'required_field', 'conservation_requirements', NULL,
           'Conservation policy should be documented', 'GASB 34.27', 'info', 22
) r
WHERE s.code = 'GASB34'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- FASB 958 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Collection Policy' as name,
           'Document collection capitalisation policy' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset classification required' as error_message,
           'FASB 958-360-25' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Acquisition Date',
           'Date item was acquired', 'required_field', 'acquisition_date', NULL,
           'Acquisition date should be recorded', 'FASB 958-360-25-2', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Donor Information',
           'Donor details for contributed items', 'required_field', 'donor_name', NULL,
           'Donor information recommended', 'FASB 958-605-25', 'info', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Fair Value',
           'Fair value at acquisition', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'FASB 958-360-30-1', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Current carrying amount if capitalised', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded if capitalised', 'FASB 958-360-35', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Collection Description',
           'Description required for non-capitalised collections', 'required_field', 'significance_statement', NULL,
           'Collection description recommended', 'FASB 958-360-50', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Capitalisation Policy',
           'Policy for capitalising vs not capitalising', 'required_field', 'recognition_status_reason', NULL,
           'Capitalisation policy should be documented', 'FASB 958-360-50-1', 'warning', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Stewardship Activities',
           'Description of stewardship activities', 'required_field', 'conservation_requirements', NULL,
           'Stewardship activities should be documented', 'FASB 958-360-50-2', 'info', 22
) r
WHERE s.code = 'FASB958'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- PSAS 3150 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Classification' as name,
           'Tangible capital asset must be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required' as error_message,
           'PS 3150.08' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Recognition Date',
           'Date of recognition', 'required_field', 'recognition_date', NULL,
           'Recognition date should be recorded', 'PS 3150.10', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Useful Life Assessment',
           'Assessment of useful life', 'required_field', 'heritage_significance', NULL,
           'Useful life/significance assessment recommended', 'PS 3150.22', 'info', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Historical Cost',
           'Record at historical cost where determinable', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'PS 3150.15', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Net book value', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded', 'PS 3150.15', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Heritage Disclosure',
           'Disclose heritage assets if not recognised', 'required_field', 'significance_statement', NULL,
           'Heritage significance should be documented', 'PS 3150.42', 'info', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Cost Information',
           'Cost or deemed cost', 'required_field', 'acquisition_cost', NULL,
           'Cost information should be documented', 'PS 3150.39', 'warning', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Restrictions',
           'Any restrictions on use or disposal', 'required_field', 'restrictions_on_use', NULL,
           'Restrictions should be disclosed', 'PS 3150.42', 'info', 22
) r
WHERE s.code = 'PSAS3150'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- IPSAS 45 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Class Required' as name,
           'Asset must be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required' as error_message,
           'IPSAS 45.14' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Recognition Date',
           'Date asset was recognised', 'required_field', 'recognition_date', NULL,
           'Recognition date should be recorded', 'IPSAS 45.14', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Heritage Significance',
           'Documentation of heritage characteristics', 'required_field', 'significance_statement', NULL,
           'Heritage significance should be documented', 'IPSAS 45.5', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Measurement Basis',
           'Measurement model must be specified', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'IPSAS 45.43', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Carrying amount after recognition', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded', 'IPSAS 45.43', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Measurement Basis Disclosure',
           'Disclose measurement basis used', 'required_field', 'measurement_basis', NULL,
           'Measurement basis must be disclosed', 'IPSAS 45.88', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Restrictions',
           'Restrictions on disposal', 'required_field', 'restrictions_on_disposal', NULL,
           'Restrictions should be disclosed', 'IPSAS 45.88', 'info', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Conservation',
           'Conservation requirements', 'required_field', 'conservation_requirements', NULL,
           'Conservation requirements should be documented', 'IPSAS 45.88', 'info', 22
) r
WHERE s.code = 'IPSAS45'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- IPSAS 17 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Class Required' as name,
           'Asset must be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required' as error_message,
           'IPSAS 17.14' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Recognition Date',
           'Date of recognition', 'required_field', 'recognition_date', NULL,
           'Recognition date should be recorded', 'IPSAS 17.14', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Heritage Characteristics',
           'Document heritage characteristics', 'required_field', 'significance_statement', NULL,
           'Heritage characteristics should be documented', 'IPSAS 17.9', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Measurement Basis',
           'Cost or revaluation model', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'IPSAS 17.42', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Current carrying amount', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded', 'IPSAS 17.88', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Measurement Disclosure',
           'Disclose measurement basis', 'required_field', 'measurement_basis', NULL,
           'Measurement basis must be disclosed', 'IPSAS 17.88', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Restrictions',
           'Restrictions on title', 'required_field', 'restrictions_on_use', NULL,
           'Restrictions should be disclosed', 'IPSAS 17.88(d)', 'info', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Reconciliation',
           'Reconciliation of carrying amounts', 'required_field', 'current_carrying_amount', NULL,
           'Carrying amount reconciliation required', 'IPSAS 17.88(e)', 'info', 22
) r
WHERE s.code = 'IPSAS17'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- AASB 116 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Class Required' as name,
           'Asset must be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required' as error_message,
           'AASB 116.7' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Recognition Date',
           'Date of recognition', 'required_field', 'recognition_date', NULL,
           'Recognition date should be recorded', 'AASB 116.7', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Heritage Significance',
           'Cultural significance', 'required_field', 'significance_statement', NULL,
           'Heritage significance should be documented', 'AASB 116 Aus7.1', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Measurement Basis',
           'Cost or revaluation model', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'AASB 116.29', 'warning', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Current carrying amount', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded', 'AASB 116.30', 'warning', 11
    UNION ALL SELECT 'disclosure', 'DIS001', 'Measurement Disclosure',
           'Disclose measurement basis', 'required_field', 'measurement_basis', NULL,
           'Measurement basis must be disclosed', 'AASB 116.73', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Restrictions',
           'Restrictions on title', 'required_field', 'restrictions_on_use', NULL,
           'Restrictions should be disclosed', 'AASB 116.74(a)', 'info', 21
    UNION ALL SELECT 'disclosure', 'DIS003', 'Conservation',
           'Conservation policy', 'required_field', 'conservation_requirements', NULL,
           'Conservation policy should be documented', 'AASB 116 Aus73.1', 'info', 22
) r
WHERE s.code = 'AASB116'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- IAS 16 Rules (8 rules)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Class Required' as name,
           'Asset must be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset class is required' as error_message,
           'IAS 16.7' as reference, 'error' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Recognition Date',
           'Date of recognition', 'required_field', 'recognition_date', NULL,
           'Recognition date should be recorded', 'IAS 16.7', 'warning', 2
    UNION ALL SELECT 'recognition', 'REC003', 'Future Economic Benefits',
           'Document expected benefits', 'required_field', 'significance_statement', NULL,
           'Future benefits/significance should be documented', 'IAS 16.7(a)', 'warning', 3
    UNION ALL SELECT 'measurement', 'MEA001', 'Measurement Basis',
           'Cost or revaluation model', 'required_field', 'measurement_basis', NULL,
           'Measurement basis should be specified', 'IAS 16.29', 'error', 10
    UNION ALL SELECT 'measurement', 'MEA002', 'Carrying Amount',
           'Current carrying amount', 'value_check', 'current_carrying_amount', '>=0',
           'Carrying amount should be recorded', 'IAS 16.30', 'warning', 11
    UNION ALL SELECT 'measurement', 'MEA003', 'Cost at Recognition',
           'Cost at initial recognition', 'required_field', 'acquisition_cost', NULL,
           'Initial cost should be recorded', 'IAS 16.15', 'warning', 12
    UNION ALL SELECT 'disclosure', 'DIS001', 'Measurement Disclosure',
           'Disclose measurement basis used', 'required_field', 'measurement_basis', NULL,
           'Measurement basis must be disclosed', 'IAS 16.73(a)', 'warning', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Restrictions',
           'Restrictions on title', 'required_field', 'restrictions_on_use', NULL,
           'Restrictions should be disclosed', 'IAS 16.74(a)', 'info', 21
) r
WHERE s.code = 'IAS16'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- CUSTOM Rules (5 rules - basic)
INSERT INTO heritage_compliance_rule 
(standard_id, category, code, name, description, check_type, field_name, `condition`, error_message, reference, severity, sort_order)
SELECT s.id, r.category, r.code, r.name, r.description, r.check_type, r.field_name, r.cond, r.error_message, r.reference, r.severity, r.sort_order
FROM heritage_accounting_standard s
CROSS JOIN (
    SELECT 'recognition' as category, 'REC001' as code, 'Asset Classification' as name,
           'Asset should be classified' as description,
           'required_field' as check_type, 'asset_class_id' as field_name, NULL as cond,
           'Asset classification is recommended' as error_message,
           'Best Practice' as reference, 'warning' as severity, 1 as sort_order
    UNION ALL SELECT 'recognition', 'REC002', 'Description',
           'Asset description', 'required_field', 'significance_statement', NULL,
           'Asset description is recommended', 'Best Practice', 'info', 2
    UNION ALL SELECT 'measurement', 'MEA001', 'Value Record',
           'Record some form of value', 'required_field', 'measurement_basis', NULL,
           'Measurement approach should be documented', 'Best Practice', 'info', 10
    UNION ALL SELECT 'disclosure', 'DIS001', 'Location',
           'Current location', 'required_field', 'current_location', NULL,
           'Asset location should be recorded', 'Best Practice', 'info', 20
    UNION ALL SELECT 'disclosure', 'DIS002', 'Condition',
           'Condition assessment', 'required_field', 'condition_rating', NULL,
           'Condition should be assessed', 'Best Practice', 'info', 21
) r
WHERE s.code = 'CUSTOM'
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Transaction log for heritage assets
CREATE TABLE IF NOT EXISTS `heritage_transaction_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `heritage_asset_id` INT UNSIGNED NOT NULL,
    `object_id` INT NULL,
    `transaction_type` VARCHAR(50) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NULL,
    `transaction_data` JSON NULL,
    `user_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_heritage_asset` (`heritage_asset_id`),
    INDEX `idx_object` (`object_id`),
    INDEX `idx_type` (`transaction_type`),
    INDEX `idx_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/core.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

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
    `recognition_status` VARCHAR(47) COMMENT 'recognised, not_recognised, pending' DEFAULT 'pending',
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
    `depreciation_policy` VARCHAR(76) DEFAULT 'not_depreciated' COMMENT 'not_depreciated, straight_line, reducing_balance, units_of_production',
    `derecognition_reason` VARCHAR(60) DEFAULT NULL COMMENT 'disposal, destruction, loss, transfer, write_off, other',
    `revaluation_frequency` VARCHAR(64) DEFAULT 'as_needed' COMMENT 'annual, triennial, quinquennial, as_needed, not_applicable',
    `valuation_method` VARCHAR(51) DEFAULT NULL COMMENT 'market, cost, income, expert, insurance, other',
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
    `movement_type` VARCHAR(98) COMMENT 'acquisition, disposal, transfer, loan_out, loan_return, revaluation, impairment, other' NOT NULL,
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
    `category` VARCHAR(48) COMMENT 'recognition, measurement, disclosure' NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `check_type` VARCHAR(59) COMMENT 'required_field, value_check, date_check, custom' DEFAULT 'required_field',
    `field_name` VARCHAR(100) NULL,
    `condition` VARCHAR(255) NULL,
    `error_message` VARCHAR(255) NOT NULL,
    `reference` VARCHAR(100) NULL,
    `severity` VARCHAR(32) COMMENT 'error, warning, info' DEFAULT 'error',
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

INSERT IGNORE INTO heritage_asset_class (code, name, description, is_active, sort_order) VALUES
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

INSERT IGNORE INTO heritage_regional_config (region_code, region_name, countries, default_currency, regulatory_body, is_installed) VALUES
('africa_ipsas', 'Africa (IPSAS)', '["Zimbabwe", "Kenya", "Nigeria", "Ghana", "Tanzania", "Uganda", "Rwanda", "Botswana", "Zambia", "Malawi"]', 'USD', 'National Auditor General', 0),
('south_africa_grap', 'South Africa (GRAP)', '["South Africa"]', 'ZAR', 'National Treasury / ASB', 0),
('uk_frs', 'United Kingdom (FRS)', '["United Kingdom", "Ireland"]', 'GBP', 'Charity Commission / FRC', 0),
('usa_government', 'USA Government (GASB)', '["United States"]', 'USD', 'GASB', 0),
('usa_nonprofit', 'USA Non-Profit (FASB)', '["United States"]', 'USD', 'FASB', 0),
('australia_nz', 'Australia & NZ (AASB)', '["Australia", "New Zealand"]', 'AUD', 'AASB / XRB', 0),
('canada_psas', 'Canada (PSAS)', '["Canada"]', 'CAD', 'PSAB', 0),
('international_private', 'International Private (IAS)', '["International"]', 'USD', 'IASB', 0)
ON DUPLICATE KEY UPDATE region_name=VALUES(region_name);
