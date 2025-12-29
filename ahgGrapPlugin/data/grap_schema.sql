-- ============================================================================
-- GRAP 103 Heritage Asset Financial Accounting Schema
-- Complete database schema for South African GRAP 103 compliance
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- TABLE 1: grap_heritage_asset - Main asset financial data
-- ============================================================================
CREATE TABLE IF NOT EXISTS `grap_heritage_asset` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `object_id` INT NOT NULL COMMENT 'Links to information_object.id',
    
    -- Recognition & Measurement (GRAP 103.14-49)
    `recognition_status` VARCHAR(50) COMMENT 'recognized, not_recognized, pending, operational',
    `recognition_status_reason` TEXT COMMENT 'Reason if not recognised',
    `measurement_basis` VARCHAR(50) COMMENT 'cost, fair_value, deemed_cost, nominal, not_practicable',
    `recognition_date` DATE COMMENT 'Date first recognised in financial statements',
    `initial_carrying_amount` DECIMAL(15,2) COMMENT 'Initial carrying amount',
    `current_carrying_amount` DECIMAL(15,2) COMMENT 'Current carrying amount after depreciation/impairment',
    
    -- Classification (GRAP 103.10-13)
    `asset_class` VARCHAR(50) COMMENT 'art_collections, archives, monuments, etc.',
    `asset_sub_class` VARCHAR(100) COMMENT 'Sub-classification',
    
    -- Acquisition
    `acquisition_method` VARCHAR(50) COMMENT 'purchase, donation, bequest, transfer, exchange, etc.',
    `acquisition_date` DATE COMMENT 'Date acquired',
    `cost_of_acquisition` DECIMAL(15,2) COMMENT 'Purchase price',
    `fair_value_at_acquisition` DECIMAL(15,2) COMMENT 'Fair value at acquisition (for donations)',
    
    -- Financial Classification
    `gl_account_code` VARCHAR(50) COMMENT 'General ledger account code',
    `cost_center` VARCHAR(50) COMMENT 'Cost centre',
    `fund_source` VARCHAR(100) COMMENT 'Source of funding',
    
    -- Depreciation (GRAP 103.50-58)
    `depreciation_policy` VARCHAR(50) COMMENT 'not_depreciated, depreciated, partial',
    `useful_life_years` INT COMMENT 'Useful life in years',
    `residual_value` DECIMAL(15,2) COMMENT 'Residual value',
    `depreciation_method` VARCHAR(50) COMMENT 'straight_line, reducing_balance, units_production',
    `accumulated_depreciation` DECIMAL(15,2) DEFAULT 0 COMMENT 'Total depreciation to date',
    
    -- Revaluation (GRAP 103.42-49)
    `last_valuation_date` DATE COMMENT 'Most recent valuation date',
    `last_valuation_amount` DECIMAL(15,2) COMMENT 'Value at last valuation',
    `valuer_name` VARCHAR(255) COMMENT 'Name of valuer',
    `valuer_credentials` VARCHAR(255) COMMENT 'Valuer qualifications',
    `valuation_method` VARCHAR(50) COMMENT 'market_approach, cost_approach, income_approach, expert_opinion',
    `revaluation_frequency` VARCHAR(50) COMMENT 'annual, triennial, five_yearly, as_needed',
    
    -- Impairment (GRAP 103.59-63)
    `last_impairment_date` DATE COMMENT 'Last impairment assessment date',
    `impairment_indicators` TINYINT(1) DEFAULT 0 COMMENT 'Whether impairment indicators exist',
    `impairment_loss` DECIMAL(15,2) DEFAULT 0 COMMENT 'Impairment loss amount',
    
    -- Derecognition (GRAP 103.64-69)
    `derecognition_date` DATE COMMENT 'Date removed from register',
    `derecognition_reason` VARCHAR(50) COMMENT 'sold, destroyed, lost, transferred, stolen',
    `derecognition_proceeds` DECIMAL(15,2) COMMENT 'Proceeds from disposal',
    `gain_loss_on_disposal` DECIMAL(15,2) COMMENT 'Gain or loss on disposal',
    
    -- Disclosure Requirements (GRAP 103.70-79)
    `heritage_significance` VARCHAR(50) COMMENT 'international, national, provincial, local, institutional',
    `condition_rating` VARCHAR(50) COMMENT 'excellent, good, fair, poor, critical',
    `restrictions_on_use` TEXT COMMENT 'Use or disposal restrictions',
    `conservation_commitments` TEXT COMMENT 'Conservation obligations',
    
    -- Insurance
    `insurance_value` DECIMAL(15,2) COMMENT 'Insurance value',
    `insurance_policy_number` VARCHAR(100) COMMENT 'Policy number',
    `insurance_provider` VARCHAR(255) COMMENT 'Insurance company',
    `insurance_expiry_date` DATE COMMENT 'Policy expiry',
    
    -- Location
    `current_location` VARCHAR(255) COMMENT 'Physical location',
    
    -- Notes
    `notes` TEXT COMMENT 'Additional notes',
    
    -- Audit
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT COMMENT 'User ID',
    `updated_by` INT COMMENT 'User ID',
    
    UNIQUE KEY `unique_object` (`object_id`),
    INDEX `idx_asset_class` (`asset_class`),
    INDEX `idx_gl_account` (`gl_account_code`),
    INDEX `idx_cost_center` (`cost_center`),
    INDEX `idx_recognition` (`recognition_status`),
    INDEX `idx_valuation_date` (`last_valuation_date`),
    INDEX `idx_heritage_significance` (`heritage_significance`),
    CONSTRAINT `fk_grap_object` FOREIGN KEY (`object_id`) 
        REFERENCES `information_object`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 2: grap_valuation_history - Valuation audit trail
-- ============================================================================
CREATE TABLE IF NOT EXISTS `grap_valuation_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grap_asset_id` INT UNSIGNED NOT NULL,
    `valuation_date` DATE NOT NULL,
    `valuation_amount` DECIMAL(15,2) NOT NULL,
    `previous_amount` DECIMAL(15,2) COMMENT 'Previous carrying amount',
    `valuation_method` VARCHAR(50),
    `valuer_name` VARCHAR(255),
    `valuer_credentials` VARCHAR(255),
    `valuer_organization` VARCHAR(255),
    `valuation_report_ref` VARCHAR(100) COMMENT 'Reference to valuation report',
    `revaluation_surplus` DECIMAL(15,2) COMMENT 'Increase in value',
    `revaluation_deficit` DECIMAL(15,2) COMMENT 'Decrease in value',
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    
    INDEX `idx_vh_asset` (`grap_asset_id`),
    INDEX `idx_vh_date` (`valuation_date`),
    CONSTRAINT `fk_vh_asset` FOREIGN KEY (`grap_asset_id`) 
        REFERENCES `grap_heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 3: grap_depreciation_schedule - Depreciation tracking
-- ============================================================================
CREATE TABLE IF NOT EXISTS `grap_depreciation_schedule` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grap_asset_id` INT UNSIGNED NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL COMMENT 'e.g., 2024/25',
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `opening_value` DECIMAL(15,2) NOT NULL,
    `depreciation_amount` DECIMAL(15,2) NOT NULL,
    `closing_value` DECIMAL(15,2) NOT NULL,
    `accumulated_depreciation` DECIMAL(15,2) NOT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_ds_asset` (`grap_asset_id`),
    INDEX `idx_ds_year` (`financial_year`),
    UNIQUE KEY `unique_asset_year` (`grap_asset_id`, `financial_year`),
    CONSTRAINT `fk_ds_asset` FOREIGN KEY (`grap_asset_id`) 
        REFERENCES `grap_heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 4: grap_impairment_assessment - Impairment records
-- ============================================================================
CREATE TABLE IF NOT EXISTS `grap_impairment_assessment` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grap_asset_id` INT UNSIGNED NOT NULL,
    `assessment_date` DATE NOT NULL,
    `indicators_identified` TINYINT(1) DEFAULT 0,
    `indicator_description` TEXT COMMENT 'Description of impairment indicators',
    `carrying_amount_before` DECIMAL(15,2),
    `recoverable_amount` DECIMAL(15,2),
    `impairment_loss` DECIMAL(15,2),
    `reversal_amount` DECIMAL(15,2) COMMENT 'If previous impairment reversed',
    `assessor_name` VARCHAR(255),
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    
    INDEX `idx_ia_asset` (`grap_asset_id`),
    INDEX `idx_ia_date` (`assessment_date`),
    CONSTRAINT `fk_ia_asset` FOREIGN KEY (`grap_asset_id`) 
        REFERENCES `grap_heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 5: grap_movement_register - Asset movements
-- ============================================================================
CREATE TABLE IF NOT EXISTS `grap_movement_register` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grap_asset_id` INT UNSIGNED NOT NULL,
    `movement_date` DATE NOT NULL,
    `movement_type` VARCHAR(50) NOT NULL COMMENT 'acquisition, transfer_in, transfer_out, disposal, loan_out, loan_return',
    `from_location` VARCHAR(255),
    `to_location` VARCHAR(255),
    `from_entity` VARCHAR(255) COMMENT 'Organization',
    `to_entity` VARCHAR(255),
    `reason` TEXT,
    `authorization_ref` VARCHAR(100),
    `authorized_by` VARCHAR(255),
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT,
    
    INDEX `idx_mr_asset` (`grap_asset_id`),
    INDEX `idx_mr_date` (`movement_date`),
    INDEX `idx_mr_type` (`movement_type`),
    CONSTRAINT `fk_mr_asset` FOREIGN KEY (`grap_asset_id`) 
        REFERENCES `grap_heritage_asset`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VIEW 1: v_grap_asset_register - Full asset register
-- ============================================================================
CREATE OR REPLACE VIEW `v_grap_asset_register` AS
SELECT 
    g.id AS grap_id,
    g.object_id,
    io.identifier AS reference_code,
    io_i18n.title,
    g.asset_class,
    g.asset_sub_class,
    g.heritage_significance,
    g.gl_account_code,
    g.cost_center,
    g.acquisition_method,
    g.acquisition_date,
    g.cost_of_acquisition,
    g.current_carrying_amount,
    g.measurement_basis,
    g.last_valuation_date,
    g.last_valuation_amount,
    g.valuer_name,
    g.depreciation_policy,
    g.accumulated_depreciation,
    g.recognition_status,
    g.condition_rating,
    g.current_location,
    g.insurance_value,
    g.insurance_policy_number,
    g.insurance_expiry_date,
    g.created_at,
    g.updated_at
FROM grap_heritage_asset g
JOIN information_object io ON g.object_id = io.id
LEFT JOIN information_object_i18n io_i18n ON io.id = io_i18n.id AND io_i18n.culture = 'en'
WHERE g.derecognition_date IS NULL
ORDER BY g.gl_account_code, g.asset_class, io.identifier;

-- ============================================================================
-- VIEW 2: v_grap_103_summary - GRAP 103 disclosure summary by class
-- ============================================================================
CREATE OR REPLACE VIEW `v_grap_103_summary` AS
SELECT 
    COALESCE(g.asset_class, 'Unclassified') AS asset_class,
    COUNT(*) AS asset_count,
    SUM(CASE WHEN g.recognition_status = 'recognized' THEN 1 ELSE 0 END) AS recognized_count,
    SUM(CASE WHEN g.recognition_status = 'not_recognized' THEN 1 ELSE 0 END) AS not_recognized_count,
    SUM(COALESCE(g.cost_of_acquisition, 0)) AS total_cost,
    SUM(COALESCE(g.current_carrying_amount, 0)) AS total_carrying_amount,
    SUM(COALESCE(g.accumulated_depreciation, 0)) AS total_accumulated_depreciation,
    SUM(COALESCE(g.impairment_loss, 0)) AS total_impairment,
    SUM(COALESCE(g.insurance_value, 0)) AS total_insurance_value,
    MIN(g.last_valuation_date) AS oldest_valuation,
    MAX(g.last_valuation_date) AS newest_valuation
FROM grap_heritage_asset g
WHERE g.derecognition_date IS NULL
GROUP BY g.asset_class
ORDER BY g.asset_class;

-- ============================================================================
-- VIEW 3: v_grap_valuation_schedule - Items due for revaluation
-- ============================================================================
CREATE OR REPLACE VIEW `v_grap_valuation_schedule` AS
SELECT 
    g.id AS grap_id,
    g.object_id,
    io.identifier AS reference_code,
    io_i18n.title,
    g.asset_class,
    g.last_valuation_date,
    g.last_valuation_amount,
    g.revaluation_frequency,
    CASE g.revaluation_frequency
        WHEN 'annual' THEN DATE_ADD(g.last_valuation_date, INTERVAL 1 YEAR)
        WHEN 'triennial' THEN DATE_ADD(g.last_valuation_date, INTERVAL 3 YEAR)
        WHEN 'five_yearly' THEN DATE_ADD(g.last_valuation_date, INTERVAL 5 YEAR)
        ELSE NULL
    END AS next_valuation_due,
    CASE 
        WHEN g.last_valuation_date IS NULL THEN 'Never valued'
        WHEN g.revaluation_frequency = 'annual' AND g.last_valuation_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Overdue'
        WHEN g.revaluation_frequency = 'triennial' AND g.last_valuation_date < DATE_SUB(CURDATE(), INTERVAL 3 YEAR) THEN 'Overdue'
        WHEN g.revaluation_frequency = 'five_yearly' AND g.last_valuation_date < DATE_SUB(CURDATE(), INTERVAL 5 YEAR) THEN 'Overdue'
        ELSE 'Current'
    END AS valuation_status
FROM grap_heritage_asset g
JOIN information_object io ON g.object_id = io.id
LEFT JOIN information_object_i18n io_i18n ON io.id = io_i18n.id AND io_i18n.culture = 'en'
WHERE g.derecognition_date IS NULL
  AND g.recognition_status = 'recognized'
ORDER BY g.last_valuation_date ASC;

-- ============================================================================
-- VIEW 4: v_grap_insurance_expiry - Insurance expiry tracking
-- ============================================================================
CREATE OR REPLACE VIEW `v_grap_insurance_expiry` AS
SELECT 
    g.id AS grap_id,
    g.object_id,
    io.identifier AS reference_code,
    io_i18n.title,
    g.insurance_value,
    g.insurance_policy_number,
    g.insurance_provider,
    g.insurance_expiry_date,
    DATEDIFF(g.insurance_expiry_date, CURDATE()) AS days_until_expiry,
    CASE 
        WHEN g.insurance_expiry_date IS NULL THEN 'No insurance'
        WHEN g.insurance_expiry_date < CURDATE() THEN 'Expired'
        WHEN g.insurance_expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring soon'
        ELSE 'Current'
    END AS insurance_status
FROM grap_heritage_asset g
JOIN information_object io ON g.object_id = io.id
LEFT JOIN information_object_i18n io_i18n ON io.id = io_i18n.id AND io_i18n.culture = 'en'
WHERE g.derecognition_date IS NULL
  AND g.insurance_value > 0
ORDER BY g.insurance_expiry_date ASC;

-- ============================================================================
-- VIEW 5: v_grap_compliance_check - Compliance status
-- ============================================================================
CREATE OR REPLACE VIEW `v_grap_compliance_check` AS
SELECT 
    g.id AS grap_id,
    g.object_id,
    io.identifier AS reference_code,
    io_i18n.title,
    g.recognition_status,
    -- Check required fields
    CASE WHEN g.recognition_status IS NOT NULL THEN 1 ELSE 0 END AS has_recognition_status,
    CASE WHEN g.measurement_basis IS NOT NULL THEN 1 ELSE 0 END AS has_measurement_basis,
    CASE WHEN g.asset_class IS NOT NULL THEN 1 ELSE 0 END AS has_asset_class,
    CASE WHEN g.gl_account_code IS NOT NULL THEN 1 ELSE 0 END AS has_gl_code,
    CASE WHEN g.current_carrying_amount IS NOT NULL THEN 1 ELSE 0 END AS has_carrying_amount,
    CASE WHEN g.last_valuation_date IS NOT NULL THEN 1 ELSE 0 END AS has_valuation,
    CASE WHEN g.heritage_significance IS NOT NULL THEN 1 ELSE 0 END AS has_significance,
    -- Calculate compliance score
    (
        (CASE WHEN g.recognition_status IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.measurement_basis IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.asset_class IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.gl_account_code IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.current_carrying_amount IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.last_valuation_date IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.heritage_significance IS NOT NULL THEN 1 ELSE 0 END)
    ) AS compliance_score,
    7 AS max_score,
    ROUND((
        (CASE WHEN g.recognition_status IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.measurement_basis IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.asset_class IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.gl_account_code IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.current_carrying_amount IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.last_valuation_date IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN g.heritage_significance IS NOT NULL THEN 1 ELSE 0 END)
    ) / 7 * 100, 1) AS compliance_percentage
FROM grap_heritage_asset g
JOIN information_object io ON g.object_id = io.id
LEFT JOIN information_object_i18n io_i18n ON io.id = io_i18n.id AND io_i18n.culture = 'en'
WHERE g.derecognition_date IS NULL
ORDER BY compliance_percentage ASC;

SET FOREIGN_KEY_CHECKS = 1;

-- Success message
SELECT 'GRAP 103 schema created successfully' AS status;
