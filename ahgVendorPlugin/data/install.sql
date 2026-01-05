-- =====================================================
-- ahgVendorPlugin - Database Schema
-- Vendor and supplier management
-- =====================================================

-- =====================================================
-- Vendor Service Types (lookup table - create first)
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_service_types` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `requires_insurance` TINYINT(1) DEFAULT 0,
    `requires_valuation` TINYINT(1) DEFAULT 0,
    `typical_duration_days` INT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_service_slug` (`slug`),
    KEY `idx_service_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed service types
INSERT IGNORE INTO `ahg_vendor_service_types` (`name`, `slug`, `requires_insurance`, `requires_valuation`, `typical_duration_days`, `display_order`) VALUES
('Conservation', 'conservation', 1, 1, 30, 1),
('Restoration', 'restoration', 1, 1, 45, 2),
('Framing', 'framing', 1, 1, 14, 3),
('Digitization', 'digitization', 1, 0, 7, 4),
('Photography', 'photography', 1, 0, 3, 5),
('Binding', 'binding', 0, 0, 21, 6),
('Cleaning', 'cleaning', 0, 0, 5, 7),
('Pest Treatment', 'pest-treatment', 0, 0, 7, 8),
('Storage Materials', 'storage-materials', 0, 0, 3, 9),
('Transport', 'transport', 1, 1, 1, 10),
('Valuation', 'valuation', 0, 0, 14, 11),
('Insurance', 'insurance', 0, 0, 7, 12),
('Mounting', 'mounting', 1, 1, 7, 13),
('Deacidification', 'deacidification', 0, 0, 14, 14),
('Encapsulation', 'encapsulation', 0, 0, 7, 15),
('Other', 'other', 0, 0, NULL, 99);

-- =====================================================
-- Vendors
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendors` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `vendor_code` VARCHAR(50) DEFAULT NULL,
    `vendor_type` ENUM('company','individual','institution','government') DEFAULT 'company',
    `registration_number` VARCHAR(100) DEFAULT NULL,
    `vat_number` VARCHAR(50) DEFAULT NULL,
    `street_address` TEXT,
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'South Africa',
    `phone` VARCHAR(50) DEFAULT NULL,
    `phone_alt` VARCHAR(50) DEFAULT NULL,
    `fax` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `bank_branch` VARCHAR(100) DEFAULT NULL,
    `bank_account_number` VARCHAR(50) DEFAULT NULL,
    `bank_branch_code` VARCHAR(20) DEFAULT NULL,
    `bank_account_type` VARCHAR(50) DEFAULT NULL,
    `has_insurance` TINYINT(1) DEFAULT 0,
    `insurance_provider` VARCHAR(255) DEFAULT NULL,
    `insurance_policy_number` VARCHAR(100) DEFAULT NULL,
    `insurance_expiry_date` DATE DEFAULT NULL,
    `insurance_coverage_amount` DECIMAL(15,2) DEFAULT NULL,
    `quality_rating` TINYINT DEFAULT NULL COMMENT '1-5 stars',
    `reliability_rating` TINYINT DEFAULT NULL COMMENT '1-5 stars',
    `price_rating` TINYINT DEFAULT NULL COMMENT '1-5 stars',
    `status` ENUM('active','inactive','suspended','pending_approval') DEFAULT 'active',
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `is_preferred` TINYINT(1) DEFAULT 0,
    `is_bbbee_compliant` TINYINT(1) DEFAULT 0,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    UNIQUE KEY `vendor_code` (`vendor_code`),
    KEY `idx_vendor_name` (`name`),
    KEY `idx_vendor_status` (`status`),
    KEY `idx_vendor_type` (`vendor_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Contacts
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_contacts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `vendor_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `position` VARCHAR(100) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `mobile` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_vendor` (`vendor_id`),
    KEY `idx_contact_primary` (`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Services (which services each vendor provides)
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_services` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `vendor_id` INT NOT NULL,
    `service_type_id` INT NOT NULL,
    `hourly_rate` DECIMAL(10,2) DEFAULT NULL,
    `fixed_rate` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'ZAR',
    `notes` TEXT,
    `is_preferred` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_vendor_service` (`vendor_id`, `service_type_id`),
    KEY `idx_vs_vendor` (`vendor_id`),
    KEY `idx_vs_service` (`service_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Transactions
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_transactions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) NOT NULL,
    `vendor_id` INT NOT NULL,
    `service_type_id` INT NOT NULL,
    `status` ENUM('pending_approval','approved','dispatched','received_by_vendor','in_progress','completed','ready_for_collection','returned','cancelled') DEFAULT 'pending_approval',
    `request_date` DATE NOT NULL,
    `approval_date` DATE DEFAULT NULL,
    `dispatch_date` DATE DEFAULT NULL,
    `expected_return_date` DATE DEFAULT NULL,
    `actual_return_date` DATE DEFAULT NULL,
    `requested_by` INT NOT NULL,
    `approved_by` INT DEFAULT NULL,
    `dispatched_by` INT DEFAULT NULL,
    `received_by` INT DEFAULT NULL,
    `estimated_cost` DECIMAL(12,2) DEFAULT NULL,
    `actual_cost` DECIMAL(12,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'ZAR',
    `quote_reference` VARCHAR(100) DEFAULT NULL,
    `invoice_reference` VARCHAR(100) DEFAULT NULL,
    `invoice_date` DATE DEFAULT NULL,
    `payment_status` ENUM('not_invoiced','invoiced','paid','disputed') DEFAULT 'not_invoiced',
    `payment_date` DATE DEFAULT NULL,
    `total_insured_value` DECIMAL(15,2) DEFAULT NULL,
    `insurance_arranged` TINYINT(1) DEFAULT 0,
    `insurance_reference` VARCHAR(100) DEFAULT NULL,
    `shipping_method` VARCHAR(100) DEFAULT NULL,
    `tracking_number` VARCHAR(100) DEFAULT NULL,
    `courier_company` VARCHAR(100) DEFAULT NULL,
    `dispatch_notes` TEXT,
    `vendor_notes` TEXT,
    `return_notes` TEXT,
    `internal_notes` TEXT,
    `has_quotes` TINYINT(1) DEFAULT 0,
    `has_invoices` TINYINT(1) DEFAULT 0,
    `has_condition_reports` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction_number` (`transaction_number`),
    KEY `idx_trans_vendor` (`vendor_id`),
    KEY `idx_trans_service` (`service_type_id`),
    KEY `idx_trans_status` (`status`),
    KEY `idx_trans_dispatch` (`dispatch_date`),
    KEY `idx_trans_expected` (`expected_return_date`),
    KEY `idx_trans_payment` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Transaction Items
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_transaction_items` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `transaction_id` INT NOT NULL,
    `information_object_id` INT NOT NULL,
    `item_title` VARCHAR(1024) DEFAULT NULL,
    `item_reference` VARCHAR(255) DEFAULT NULL,
    `condition_before` TEXT,
    `condition_before_rating` ENUM('excellent','good','fair','poor','critical') DEFAULT NULL,
    `condition_after` TEXT,
    `condition_after_rating` ENUM('excellent','good','fair','poor','critical') DEFAULT NULL,
    `declared_value` DECIMAL(15,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'ZAR',
    `service_description` TEXT,
    `service_completed` TINYINT(1) DEFAULT 0,
    `service_notes` TEXT,
    `item_cost` DECIMAL(10,2) DEFAULT NULL,
    `dispatched_at` DATETIME DEFAULT NULL,
    `returned_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ti_transaction` (`transaction_id`),
    KEY `idx_ti_object` (`information_object_id`),
    KEY `idx_ti_completed` (`service_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Transaction History
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_transaction_history` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `transaction_id` INT NOT NULL,
    `status_from` VARCHAR(50) DEFAULT NULL,
    `status_to` VARCHAR(50) NOT NULL,
    `changed_by` INT NOT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_th_transaction` (`transaction_id`),
    KEY `idx_th_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Transaction Attachments
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_transaction_attachments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `transaction_id` INT NOT NULL,
    `attachment_type` ENUM('quote','invoice','condition_report','photo','receipt','certificate','other') NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255) DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_size` INT DEFAULT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `description` TEXT,
    `uploaded_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ta_transaction` (`transaction_id`),
    KEY `idx_ta_type` (`attachment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Vendor Metrics
-- =====================================================
CREATE TABLE IF NOT EXISTS `ahg_vendor_metrics` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `vendor_id` INT NOT NULL,
    `year` INT NOT NULL,
    `month` INT DEFAULT NULL,
    `total_transactions` INT DEFAULT 0,
    `completed_transactions` INT DEFAULT 0,
    `on_time_returns` INT DEFAULT 0,
    `late_returns` INT DEFAULT 0,
    `total_items_handled` INT DEFAULT 0,
    `total_value_handled` DECIMAL(15,2) DEFAULT 0.00,
    `total_cost` DECIMAL(15,2) DEFAULT 0.00,
    `avg_turnaround_days` DECIMAL(5,1) DEFAULT NULL,
    `avg_quality_score` DECIMAL(3,2) DEFAULT NULL,
    `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_vendor_period` (`vendor_id`, `year`, `month`),
    KEY `idx_vm_vendor` (`vendor_id`),
    KEY `idx_vm_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
