-- ============================================================
-- ahgHeritageAccountingPlugin - Base Database Schema
-- Multi-standard heritage asset accounting
-- Supports: GRAP 103 (ZA), FRS 102 (UK), GASB 34 (US), PSAS 3150 (CA)
-- ============================================================

-- Accounting Standards Reference
CREATE TABLE IF NOT EXISTS heritage_accounting_standard (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(50) NOT NULL,
    description TEXT,
    capitalisation_required TINYINT(1) DEFAULT 0,
    valuation_methods JSON,
    disclosure_requirements JSON,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert supported standards
INSERT IGNORE INTO heritage_accounting_standard (code, name, country, capitalisation_required, sort_order) VALUES
('GRAP103', 'GRAP 103 Heritage Assets', 'South Africa', 1, 1),
('FRS102', 'FRS 102 Section 34', 'United Kingdom', 0, 2),
('GASB34', 'GASB Statement 34', 'United States', 0, 3),
('FASB958', 'FASB ASC 958', 'United States', 0, 4),
('PSAS3150', 'PSAS 3150 Tangible Capital Assets', 'Canada', 0, 5);

-- Asset Classes (shared across standards)
CREATE TABLE IF NOT EXISTS heritage_asset_class (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT UNSIGNED NULL,
    default_useful_life INT NULL,
    default_depreciation_method VARCHAR(50) NULL,
    is_depreciable TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES heritage_asset_class(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert standard asset classes
INSERT IGNORE INTO heritage_asset_class (code, name, is_depreciable, sort_order) VALUES
('art', 'Art Works & Collections', 0, 1),
('archives', 'Archives & Manuscripts', 0, 2),
('library', 'Library Collections', 0, 3),
('museum', 'Museum Objects', 0, 4),
('natural', 'Natural History Specimens', 0, 5),
('archaeological', 'Archaeological Items', 0, 6),
('historical', 'Historical Buildings & Sites', 0, 7),
('cultural', 'Cultural & Religious Items', 0, 8),
('scientific', 'Scientific Collections', 0, 9),
('other', 'Other Heritage Assets', 0, 10);

-- Main Heritage Asset Table (shared fields)
CREATE TABLE IF NOT EXISTS heritage_asset (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    
    -- Standard being applied
    accounting_standard_id INT UNSIGNED NULL,
    
    -- Recognition
    recognition_status ENUM('recognised', 'not_recognised', 'pending', 'derecognised') DEFAULT 'pending',
    recognition_status_reason VARCHAR(255),
    recognition_date DATE NULL,
    
    -- Classification
    asset_class_id INT UNSIGNED NULL,
    asset_sub_class VARCHAR(100),
    
    -- Measurement
    measurement_basis ENUM('cost', 'fair_value', 'nominal', 'not_practicable') DEFAULT 'cost',
    
    -- Acquisition
    acquisition_method ENUM('purchase', 'donation', 'bequest', 'transfer', 'found', 'exchange', 'other') NULL,
    acquisition_date DATE NULL,
    acquisition_cost DECIMAL(18,2) DEFAULT 0.00,
    fair_value_at_acquisition DECIMAL(18,2) NULL,
    nominal_value DECIMAL(18,2) DEFAULT 1.00,
    
    -- Donor information (for donated assets)
    donor_name VARCHAR(255),
    donor_restrictions TEXT,
    
    -- Current values
    initial_carrying_amount DECIMAL(18,2) DEFAULT 0.00,
    current_carrying_amount DECIMAL(18,2) DEFAULT 0.00,
    accumulated_depreciation DECIMAL(18,2) DEFAULT 0.00,
    revaluation_surplus DECIMAL(18,2) DEFAULT 0.00,
    impairment_loss DECIMAL(18,2) DEFAULT 0.00,
    
    -- Valuation
    last_valuation_date DATE NULL,
    last_valuation_amount DECIMAL(18,2) NULL,
    valuation_method ENUM('market', 'cost', 'income', 'expert', 'insurance', 'other') NULL,
    valuer_name VARCHAR(255),
    valuer_credentials VARCHAR(255),
    valuation_report_reference VARCHAR(255),
    revaluation_frequency ENUM('annual', 'triennial', 'quinquennial', 'as_needed', 'not_applicable') DEFAULT 'as_needed',
    
    -- Depreciation (for depreciable heritage assets)
    depreciation_policy ENUM('not_depreciated', 'straight_line', 'reducing_balance', 'units_of_production') DEFAULT 'not_depreciated',
    useful_life_years INT NULL,
    residual_value DECIMAL(18,2) DEFAULT 0.00,
    annual_depreciation DECIMAL(18,2) DEFAULT 0.00,
    
    -- Impairment
    last_impairment_date DATE NULL,
    impairment_indicators TINYINT(1) DEFAULT 0,
    impairment_indicators_details TEXT,
    recoverable_amount DECIMAL(18,2) NULL,
    
    -- Derecognition
    derecognition_date DATE NULL,
    derecognition_reason ENUM('disposal', 'destruction', 'loss', 'transfer', 'write_off', 'other') NULL,
    derecognition_proceeds DECIMAL(18,2) NULL,
    gain_loss_on_derecognition DECIMAL(18,2) NULL,
    
    -- Heritage specific
    heritage_significance ENUM('exceptional', 'high', 'medium', 'low') NULL,
    significance_statement TEXT,
    restrictions_on_use TEXT,
    restrictions_on_disposal TEXT,
    conservation_requirements TEXT,
    
    -- Insurance
    insurance_required TINYINT(1) DEFAULT 1,
    insurance_value DECIMAL(18,2) NULL,
    insurance_policy_number VARCHAR(100),
    insurance_provider VARCHAR(255),
    insurance_expiry_date DATE NULL,
    
    -- Location & Condition
    current_location VARCHAR(255),
    storage_conditions TEXT,
    condition_rating ENUM('excellent', 'good', 'fair', 'poor', 'critical') NULL,
    last_condition_assessment DATE NULL,
    
    -- Audit trail
    created_by INT NULL,
    updated_by INT NULL,
    approved_by INT NULL,
    approved_date DATE NULL,
    notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_object (object_id),
    KEY idx_standard (accounting_standard_id),
    KEY idx_class (asset_class_id),
    KEY idx_recognition (recognition_status),
    KEY idx_acquisition_date (acquisition_date),
    KEY idx_valuation_date (last_valuation_date),
    
    FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE,
    FOREIGN KEY (accounting_standard_id) REFERENCES heritage_accounting_standard(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_class_id) REFERENCES heritage_asset_class(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valuation History
CREATE TABLE IF NOT EXISTS heritage_valuation_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NOT NULL,
    valuation_date DATE NOT NULL,
    previous_value DECIMAL(18,2) NULL,
    new_value DECIMAL(18,2) NOT NULL,
    valuation_change DECIMAL(18,2) NULL,
    valuation_method ENUM('market', 'cost', 'income', 'expert', 'insurance', 'other') NULL,
    valuer_name VARCHAR(255),
    valuer_credentials VARCHAR(255),
    valuer_organization VARCHAR(255),
    valuation_report_reference VARCHAR(255),
    revaluation_surplus_change DECIMAL(18,2) NULL,
    notes TEXT,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_asset (heritage_asset_id),
    KEY idx_date (valuation_date),
    FOREIGN KEY (heritage_asset_id) REFERENCES heritage_asset(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Impairment Assessment
CREATE TABLE IF NOT EXISTS heritage_impairment_assessment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NOT NULL,
    assessment_date DATE NOT NULL,
    
    -- Impairment indicators
    physical_damage TINYINT(1) DEFAULT 0,
    physical_damage_details TEXT,
    obsolescence TINYINT(1) DEFAULT 0,
    obsolescence_details TEXT,
    change_in_use TINYINT(1) DEFAULT 0,
    change_in_use_details TEXT,
    external_factors TINYINT(1) DEFAULT 0,
    external_factors_details TEXT,
    
    -- Assessment result
    impairment_identified TINYINT(1) DEFAULT 0,
    carrying_amount_before DECIMAL(18,2) NULL,
    recoverable_amount DECIMAL(18,2) NULL,
    impairment_loss DECIMAL(18,2) NULL,
    carrying_amount_after DECIMAL(18,2) NULL,
    
    -- Reversal
    reversal_applicable TINYINT(1) DEFAULT 0,
    reversal_amount DECIMAL(18,2) NULL,
    reversal_date DATE NULL,
    
    assessor_name VARCHAR(255),
    notes TEXT,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_asset (heritage_asset_id),
    KEY idx_date (assessment_date),
    FOREIGN KEY (heritage_asset_id) REFERENCES heritage_asset(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movement Register
CREATE TABLE IF NOT EXISTS heritage_movement_register (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NOT NULL,
    movement_date DATE NOT NULL,
    movement_type ENUM('loan_out', 'loan_return', 'transfer', 'exhibition', 'conservation', 'storage_change', 'other') NOT NULL,
    from_location VARCHAR(255),
    to_location VARCHAR(255),
    reason TEXT,
    authorized_by VARCHAR(255),
    authorization_date DATE NULL,
    expected_return_date DATE NULL,
    actual_return_date DATE NULL,
    condition_on_departure ENUM('excellent', 'good', 'fair', 'poor') NULL,
    condition_on_return ENUM('excellent', 'good', 'fair', 'poor') NULL,
    condition_notes TEXT,
    insurance_confirmed TINYINT(1) DEFAULT 0,
    insurance_value DECIMAL(18,2) NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_asset (heritage_asset_id),
    KEY idx_date (movement_date),
    KEY idx_type (movement_type),
    FOREIGN KEY (heritage_asset_id) REFERENCES heritage_asset(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journal Entries
CREATE TABLE IF NOT EXISTS heritage_journal_entry (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NOT NULL,
    journal_date DATE NOT NULL,
    journal_number VARCHAR(50),
    journal_type ENUM('recognition', 'revaluation', 'depreciation', 'impairment', 'impairment_reversal', 'derecognition', 'adjustment', 'transfer') NOT NULL,
    debit_account VARCHAR(50) NOT NULL,
    debit_amount DECIMAL(18,2) NOT NULL,
    credit_account VARCHAR(50) NOT NULL,
    credit_amount DECIMAL(18,2) NOT NULL,
    description TEXT,
    reference_document VARCHAR(255),
    fiscal_year INT NULL,
    fiscal_period INT NULL,
    posted TINYINT(1) DEFAULT 0,
    posted_by INT NULL,
    posted_at DATETIME NULL,
    reversed TINYINT(1) DEFAULT 0,
    reversal_journal_id INT UNSIGNED NULL,
    reversal_date DATE NULL,
    reversal_reason TEXT,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_asset (heritage_asset_id),
    KEY idx_date (journal_date),
    KEY idx_type (journal_type),
    KEY idx_fiscal (fiscal_year, fiscal_period),
    KEY idx_posted (posted),
    FOREIGN KEY (heritage_asset_id) REFERENCES heritage_asset(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Financial Year Snapshot
CREATE TABLE IF NOT EXISTS heritage_financial_year_snapshot (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id INT NULL,
    accounting_standard_id INT UNSIGNED NULL,
    financial_year_start DATE NOT NULL,
    financial_year_end DATE NOT NULL,
    asset_class_id INT UNSIGNED NULL,
    
    -- Counts
    total_assets INT DEFAULT 0,
    recognised_assets INT DEFAULT 0,
    not_recognised_assets INT DEFAULT 0,
    
    -- Values
    total_carrying_amount DECIMAL(18,2) DEFAULT 0.00,
    total_accumulated_depreciation DECIMAL(18,2) DEFAULT 0.00,
    total_impairment DECIMAL(18,2) DEFAULT 0.00,
    total_revaluation_surplus DECIMAL(18,2) DEFAULT 0.00,
    
    -- Movements
    additions_count INT DEFAULT 0,
    additions_value DECIMAL(18,2) DEFAULT 0.00,
    disposals_count INT DEFAULT 0,
    disposals_value DECIMAL(18,2) DEFAULT 0.00,
    impairments_count INT DEFAULT 0,
    impairments_value DECIMAL(18,2) DEFAULT 0.00,
    revaluations_count INT DEFAULT 0,
    revaluations_value DECIMAL(18,2) DEFAULT 0.00,
    
    -- Extended data
    snapshot_data JSON,
    notes TEXT,
    
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_snapshot (repository_id, financial_year_end, asset_class_id, accounting_standard_id),
    KEY idx_repo (repository_id),
    KEY idx_fy (financial_year_end),
    KEY idx_class (asset_class_id),
    FOREIGN KEY (accounting_standard_id) REFERENCES heritage_accounting_standard(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_class_id) REFERENCES heritage_asset_class(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Depreciation Schedule
CREATE TABLE IF NOT EXISTS heritage_depreciation_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NOT NULL,
    fiscal_year INT NOT NULL,
    fiscal_period VARCHAR(20) NULL,
    opening_value DECIMAL(18,2) NULL,
    depreciation_amount DECIMAL(18,2) NULL,
    closing_value DECIMAL(18,2) NULL,
    calculated_at DATETIME NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_period (heritage_asset_id, fiscal_year, fiscal_period),
    KEY idx_fiscal (fiscal_year),
    FOREIGN KEY (heritage_asset_id) REFERENCES heritage_asset(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Log (audit)
CREATE TABLE IF NOT EXISTS heritage_transaction_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NULL,
    object_id INT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    transaction_date DATE NULL,
    amount DECIMAL(18,2) NULL,
    transaction_data JSON,
    user_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_asset (heritage_asset_id),
    KEY idx_object (object_id),
    KEY idx_type (transaction_type),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regional extension data (for standard-specific fields)
CREATE TABLE IF NOT EXISTS heritage_regional_data (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heritage_asset_id INT UNSIGNED NOT NULL,
    accounting_standard_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_field (heritage_asset_id, accounting_standard_id, field_name),
    KEY idx_asset (heritage_asset_id),
    KEY idx_standard (accounting_standard_id),
    FOREIGN KEY (heritage_asset_id) REFERENCES heritage_asset(id) ON DELETE CASCADE,
    FOREIGN KEY (accounting_standard_id) REFERENCES heritage_accounting_standard(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
