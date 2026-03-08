-- ============================================================================
-- ahgLibraryPlugin — Full Library System Migration
-- Issue #214: Extend Heratio to be a full library system
-- Date: 2026-03-08
-- ============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- 1. Heritage Accounting columns on library_item
-- ============================================================================

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'heritage_asset_id');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN heritage_asset_id INT UNSIGNED NULL COMMENT ''FK to heritage_asset'' AFTER updated_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_method');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_method VARCHAR(50) NULL COMMENT ''purchase, donation, gift, bequest, exchange, deposit'' AFTER heritage_asset_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_date');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_date DATE NULL AFTER acquisition_method', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_cost');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_cost DECIMAL(15,2) NULL AFTER acquisition_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'acquisition_currency');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN acquisition_currency VARCHAR(3) DEFAULT ''ZAR'' AFTER acquisition_cost', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'replacement_value');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN replacement_value DECIMAL(15,2) NULL AFTER acquisition_currency', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'insurance_value');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN insurance_value DECIMAL(15,2) NULL AFTER replacement_value', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'insurance_policy');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN insurance_policy VARCHAR(100) NULL AFTER insurance_value', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'insurance_expiry');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN insurance_expiry DATE NULL AFTER insurance_policy', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'asset_class_code');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN asset_class_code VARCHAR(20) NULL COMMENT ''heritage_asset_class.code'' AFTER insurance_expiry', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'recognition_status');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN recognition_status VARCHAR(30) NULL DEFAULT ''pending'' AFTER asset_class_code', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'valuation_date');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN valuation_date DATE NULL AFTER recognition_status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'valuation_method');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN valuation_method VARCHAR(50) NULL AFTER valuation_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'valuation_notes');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN valuation_notes TEXT NULL AFTER valuation_method', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'donor_name');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN donor_name VARCHAR(255) NULL AFTER valuation_notes', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'donor_restrictions');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN donor_restrictions TEXT NULL AFTER donor_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'condition_grade');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN condition_grade VARCHAR(30) NULL AFTER donor_restrictions', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'library_item' AND COLUMN_NAME = 'conservation_priority');
SET @sql = IF(@col = 0, 'ALTER TABLE library_item ADD COLUMN conservation_priority VARCHAR(20) NULL AFTER condition_grade', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 2. Library Copy (individual physical copies)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_copy (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL,
    copy_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    barcode VARCHAR(50) NULL,
    accession_number VARCHAR(50) NULL,
    call_number_suffix VARCHAR(20) NULL COMMENT 'e.g. c.2, v.3',
    shelf_location VARCHAR(100) NULL,
    branch VARCHAR(100) NULL COMMENT 'Library branch/location',
    status VARCHAR(30) NOT NULL DEFAULT 'available',
    condition_grade VARCHAR(30) NULL,
    condition_notes TEXT NULL,
    acquisition_method VARCHAR(50) NULL,
    acquisition_date DATE NULL,
    acquisition_cost DECIMAL(15,2) NULL,
    acquisition_source VARCHAR(255) NULL COMMENT 'vendor or donor',
    withdrawal_date DATE NULL,
    withdrawal_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_barcode (barcode),
    KEY idx_item (library_item_id),
    KEY idx_status (status),
    KEY idx_branch (branch),
    KEY idx_accession (accession_number),
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. Library Patron (borrowers)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_patron (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT UNSIGNED NULL COMMENT 'FK to actor table',
    card_number VARCHAR(50) NOT NULL,
    patron_type VARCHAR(30) NOT NULL DEFAULT 'public',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    institution VARCHAR(255) NULL,
    department VARCHAR(100) NULL,
    id_number VARCHAR(50) NULL COMMENT 'National ID or student number',
    date_of_birth DATE NULL,
    membership_start DATE NOT NULL,
    membership_expiry DATE NULL,
    max_checkouts SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    max_renewals SMALLINT UNSIGNED NOT NULL DEFAULT 2,
    max_holds SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    borrowing_status VARCHAR(20) NOT NULL DEFAULT 'active',
    suspension_reason TEXT NULL,
    suspension_until DATE NULL,
    total_fines_owed DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_fines_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_checkouts INT UNSIGNED NOT NULL DEFAULT 0,
    last_activity_date DATE NULL,
    photo_url VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_card (card_number),
    KEY idx_actor (actor_id),
    KEY idx_type (patron_type),
    KEY idx_status (borrowing_status),
    KEY idx_name (last_name, first_name),
    KEY idx_email (email),
    KEY idx_expiry (membership_expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. Library Checkout (circulation transactions)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_checkout (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    copy_id BIGINT UNSIGNED NOT NULL,
    patron_id BIGINT UNSIGNED NOT NULL,
    checkout_date DATETIME NOT NULL,
    due_date DATE NOT NULL,
    return_date DATETIME NULL,
    renewed_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    checkout_notes TEXT NULL,
    return_notes TEXT NULL,
    return_condition VARCHAR(30) NULL,
    checked_out_by INT UNSIGNED NULL COMMENT 'Staff user_id',
    checked_in_by INT UNSIGNED NULL COMMENT 'Staff user_id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_copy (copy_id),
    KEY idx_patron (patron_id),
    KEY idx_status (status),
    KEY idx_due (due_date),
    KEY idx_checkout_date (checkout_date),
    FOREIGN KEY (copy_id) REFERENCES library_copy(id) ON DELETE RESTRICT,
    FOREIGN KEY (patron_id) REFERENCES library_patron(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. Library Hold (reservation queue)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_hold (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL,
    patron_id BIGINT UNSIGNED NOT NULL,
    hold_date DATETIME NOT NULL,
    expiry_date DATE NULL COMMENT 'Hold expires if not picked up',
    pickup_branch VARCHAR(100) NULL,
    queue_position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    notification_sent TINYINT(1) NOT NULL DEFAULT 0,
    notification_date DATETIME NULL,
    fulfilled_date DATETIME NULL,
    cancelled_date DATETIME NULL,
    cancel_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_item (library_item_id),
    KEY idx_patron (patron_id),
    KEY idx_status (status),
    KEY idx_queue (library_item_id, queue_position),
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE,
    FOREIGN KEY (patron_id) REFERENCES library_patron(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. Library Fine (fees & payments)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_fine (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patron_id BIGINT UNSIGNED NOT NULL,
    checkout_id BIGINT UNSIGNED NULL,
    fine_type VARCHAR(30) NOT NULL DEFAULT 'overdue',
    amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'ZAR',
    status VARCHAR(20) NOT NULL DEFAULT 'outstanding',
    description TEXT NULL,
    fine_date DATE NOT NULL,
    payment_date DATETIME NULL,
    payment_method VARCHAR(30) NULL,
    payment_reference VARCHAR(100) NULL,
    waived_by INT UNSIGNED NULL COMMENT 'Staff user_id who waived',
    waived_date DATETIME NULL,
    waive_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_patron (patron_id),
    KEY idx_checkout (checkout_id),
    KEY idx_status (status),
    KEY idx_type (fine_type),
    KEY idx_date (fine_date),
    FOREIGN KEY (patron_id) REFERENCES library_patron(id) ON DELETE RESTRICT,
    FOREIGN KEY (checkout_id) REFERENCES library_checkout(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. Library Subscription (serial management)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_subscription (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL COMMENT 'Parent serial/periodical',
    vendor_id INT UNSIGNED NULL COMMENT 'FK to vendor',
    subscription_number VARCHAR(100) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    renewal_date DATE NULL,
    frequency VARCHAR(30) NULL COMMENT 'From ahg_dropdown',
    issues_per_year SMALLINT UNSIGNED NULL,
    cost_per_year DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',
    budget_code VARCHAR(50) NULL,
    routing_list JSON NULL COMMENT 'Ordered list of staff for routing',
    delivery_method VARCHAR(30) NULL COMMENT 'mail, electronic, both',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_item (library_item_id),
    KEY idx_status (status),
    KEY idx_renewal (renewal_date),
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. Library Serial Issue (individual issue tracking)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_serial_issue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id BIGINT UNSIGNED NOT NULL,
    library_item_id BIGINT UNSIGNED NOT NULL,
    volume VARCHAR(20) NULL,
    issue_number VARCHAR(20) NULL,
    part VARCHAR(20) NULL,
    supplement VARCHAR(50) NULL,
    issue_date DATE NULL,
    expected_date DATE NULL,
    received_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'expected',
    claim_date DATE NULL,
    claim_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    barcode VARCHAR(50) NULL,
    shelf_location VARCHAR(100) NULL,
    bound_volume_id BIGINT UNSIGNED NULL COMMENT 'FK to bound volume record',
    notes TEXT NULL,
    checked_in_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_subscription (subscription_id),
    KEY idx_item (library_item_id),
    KEY idx_status (status),
    KEY idx_expected (expected_date),
    KEY idx_volume (volume, issue_number),
    UNIQUE KEY uk_barcode (barcode),
    FOREIGN KEY (subscription_id) REFERENCES library_subscription(id) ON DELETE CASCADE,
    FOREIGN KEY (library_item_id) REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. Library Order (acquisitions / purchase orders)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_order (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    vendor_id INT UNSIGNED NULL,
    vendor_name VARCHAR(255) NULL,
    order_date DATE NOT NULL,
    expected_date DATE NULL,
    received_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    order_type VARCHAR(30) NOT NULL DEFAULT 'purchase',
    budget_code VARCHAR(50) NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    shipping DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'ZAR',
    invoice_number VARCHAR(100) NULL,
    invoice_date DATE NULL,
    payment_status VARCHAR(30) NULL DEFAULT 'unpaid',
    shipping_address TEXT NULL,
    notes TEXT NULL,
    approved_by INT UNSIGNED NULL,
    approved_date DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_order_number (order_number),
    KEY idx_vendor (vendor_id),
    KEY idx_status (status),
    KEY idx_date (order_date),
    KEY idx_budget (budget_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. Library Order Line (PO line items)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_order_line (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    library_item_id BIGINT UNSIGNED NULL COMMENT 'Link to catalog record if exists',
    title VARCHAR(500) NOT NULL,
    isbn VARCHAR(17) NULL,
    issn VARCHAR(9) NULL,
    author VARCHAR(255) NULL,
    publisher VARCHAR(255) NULL,
    edition VARCHAR(100) NULL,
    material_type VARCHAR(50) NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    quantity_received SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    received_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'ordered',
    budget_code VARCHAR(50) NULL,
    fund_code VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_order (order_id),
    KEY idx_item (library_item_id),
    KEY idx_isbn (isbn),
    KEY idx_status (status),
    FOREIGN KEY (order_id) REFERENCES library_order(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. Library Budget (fund allocation)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_budget (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) NOT NULL,
    fund_name VARCHAR(255) NOT NULL,
    fiscal_year VARCHAR(9) NOT NULL COMMENT 'e.g. 2025/2026',
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    committed_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'On order',
    spent_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Received/invoiced',
    currency VARCHAR(3) DEFAULT 'ZAR',
    category VARCHAR(50) NULL COMMENT 'monographs, serials, electronic, etc.',
    department VARCHAR(100) NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_code_year (budget_code, fiscal_year),
    KEY idx_year (fiscal_year),
    KEY idx_status (status),
    KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. Interlibrary Loan Requests
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_ill_request (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL,
    direction VARCHAR(20) NOT NULL COMMENT 'borrowing or lending',
    patron_id BIGINT UNSIGNED NULL COMMENT 'Borrowing patron',
    partner_library VARCHAR(255) NOT NULL,
    partner_contact VARCHAR(255) NULL,
    partner_email VARCHAR(255) NULL,
    title VARCHAR(500) NOT NULL,
    author VARCHAR(255) NULL,
    isbn VARCHAR(17) NULL,
    issn VARCHAR(9) NULL,
    publisher VARCHAR(255) NULL,
    publication_year VARCHAR(10) NULL,
    volume_issue VARCHAR(100) NULL,
    pages VARCHAR(50) NULL,
    library_item_id BIGINT UNSIGNED NULL COMMENT 'Our item (if lending)',
    copy_id BIGINT UNSIGNED NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'requested',
    request_date DATE NOT NULL,
    needed_by DATE NULL,
    shipped_date DATE NULL,
    received_date DATE NULL,
    due_date DATE NULL,
    return_date DATE NULL,
    shipping_method VARCHAR(50) NULL,
    tracking_number VARCHAR(100) NULL,
    cost DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_request_number (request_number),
    KEY idx_patron (patron_id),
    KEY idx_status (status),
    KEY idx_direction (direction),
    KEY idx_date (request_date),
    KEY idx_partner (partner_library),
    KEY idx_item (library_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. Library Circulation Settings (per material type loan rules)
-- ============================================================================

CREATE TABLE IF NOT EXISTS library_loan_rule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_type VARCHAR(50) NOT NULL,
    patron_type VARCHAR(30) NOT NULL DEFAULT '*' COMMENT '* = all patron types',
    loan_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 14,
    renewal_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 14,
    max_renewals SMALLINT UNSIGNED NOT NULL DEFAULT 2,
    fine_per_day DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    fine_cap DECIMAL(10,2) NULL COMMENT 'Max fine for this type',
    grace_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_loanable TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_type_patron (material_type, patron_type),
    KEY idx_material (material_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. Seed default loan rules
-- ============================================================================

INSERT IGNORE INTO library_loan_rule (material_type, patron_type, loan_period_days, renewal_period_days, max_renewals, fine_per_day, fine_cap, grace_period_days, is_loanable) VALUES
('monograph', '*', 21, 21, 2, 1.00, 50.00, 1, 1),
('serial', '*', 7, 7, 1, 2.00, 50.00, 0, 1),
('volume', '*', 21, 21, 2, 1.00, 50.00, 1, 1),
('issue', '*', 7, 7, 0, 2.00, 30.00, 0, 1),
('article', '*', 7, 7, 1, 1.00, 30.00, 0, 1),
('manuscript', '*', 1, 0, 0, 10.00, 100.00, 0, 0),
('map', '*', 7, 7, 1, 2.00, 50.00, 0, 1),
('pamphlet', '*', 14, 14, 2, 0.50, 20.00, 1, 1),
('score', '*', 14, 14, 2, 1.00, 50.00, 1, 1),
('electronic', '*', 0, 0, 0, 0.00, NULL, 0, 0),
('chapter', '*', 7, 7, 1, 1.00, 30.00, 0, 1);

-- ============================================================================
-- 15. Dropdown seed data for new taxonomies
-- ============================================================================

-- Patron types
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('patron_type', 'Patron Type', 'core', 'student', 'Student', 10, 0, 1),
('patron_type', 'Patron Type', 'core', 'staff', 'Staff', 20, 0, 1),
('patron_type', 'Patron Type', 'core', 'faculty', 'Faculty', 30, 0, 1),
('patron_type', 'Patron Type', 'core', 'public', 'Public', 40, 1, 1),
('patron_type', 'Patron Type', 'core', 'researcher', 'Researcher', 50, 0, 1),
('patron_type', 'Patron Type', 'core', 'institutional', 'Institutional', 60, 0, 1),
('patron_type', 'Patron Type', 'core', 'child', 'Child (Under 18)', 70, 0, 1),
('patron_type', 'Patron Type', 'core', 'honorary', 'Honorary Member', 80, 0, 1);

-- Borrowing status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('borrowing_status', 'Borrowing Status', 'core', 'active', 'Active', '#4caf50', 10, 1, 1),
('borrowing_status', 'Borrowing Status', 'core', 'suspended', 'Suspended', '#ff9800', 20, 0, 1),
('borrowing_status', 'Borrowing Status', 'core', 'expired', 'Expired', '#9e9e9e', 30, 0, 1),
('borrowing_status', 'Borrowing Status', 'core', 'blocked', 'Blocked (Fines)', '#f44336', 40, 0, 1),
('borrowing_status', 'Borrowing Status', 'core', 'inactive', 'Inactive', '#607d8b', 50, 0, 1);

-- Checkout status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('checkout_status', 'Checkout Status', 'core', 'active', 'Checked Out', '#2196f3', 10, 1, 1),
('checkout_status', 'Checkout Status', 'core', 'returned', 'Returned', '#4caf50', 20, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'overdue', 'Overdue', '#f44336', 30, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'lost', 'Lost', '#9c27b0', 40, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'claimed_returned', 'Claimed Returned', '#ff9800', 50, 0, 1),
('checkout_status', 'Checkout Status', 'core', 'damaged', 'Returned Damaged', '#795548', 60, 0, 1);

-- Hold status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('hold_status', 'Hold Status', 'core', 'pending', 'Pending', '#ff9800', 10, 1, 1),
('hold_status', 'Hold Status', 'core', 'available', 'Available for Pickup', '#4caf50', 20, 0, 1),
('hold_status', 'Hold Status', 'core', 'fulfilled', 'Fulfilled', '#2196f3', 30, 0, 1),
('hold_status', 'Hold Status', 'core', 'expired', 'Expired', '#9e9e9e', 40, 0, 1),
('hold_status', 'Hold Status', 'core', 'cancelled', 'Cancelled', '#607d8b', 50, 0, 1);

-- Fine type
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('fine_type', 'Fine Type', 'finance', 'overdue', 'Overdue Fine', 10, 1, 1),
('fine_type', 'Fine Type', 'finance', 'lost_item', 'Lost Item Replacement', 20, 0, 1),
('fine_type', 'Fine Type', 'finance', 'damaged', 'Damage Fee', 30, 0, 1),
('fine_type', 'Fine Type', 'finance', 'processing', 'Processing Fee', 40, 0, 1),
('fine_type', 'Fine Type', 'finance', 'replacement_card', 'Card Replacement', 50, 0, 1),
('fine_type', 'Fine Type', 'finance', 'ill_fee', 'ILL Service Fee', 60, 0, 1);

-- Fine status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('fine_status', 'Fine Status', 'finance', 'outstanding', 'Outstanding', '#f44336', 10, 1, 1),
('fine_status', 'Fine Status', 'finance', 'paid', 'Paid', '#4caf50', 20, 0, 1),
('fine_status', 'Fine Status', 'finance', 'partial', 'Partially Paid', '#ff9800', 30, 0, 1),
('fine_status', 'Fine Status', 'finance', 'waived', 'Waived', '#9e9e9e', 40, 0, 1),
('fine_status', 'Fine Status', 'finance', 'referred', 'Referred to Collections', '#9c27b0', 50, 0, 1);

-- Copy status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('copy_status', 'Copy Status', 'core', 'available', 'Available', '#4caf50', 10, 1, 1),
('copy_status', 'Copy Status', 'core', 'checked_out', 'Checked Out', '#2196f3', 20, 0, 1),
('copy_status', 'Copy Status', 'core', 'on_hold', 'On Hold', '#ff9800', 30, 0, 1),
('copy_status', 'Copy Status', 'core', 'in_transit', 'In Transit', '#00bcd4', 40, 0, 1),
('copy_status', 'Copy Status', 'core', 'in_processing', 'In Processing', '#795548', 50, 0, 1),
('copy_status', 'Copy Status', 'core', 'in_repair', 'In Repair', '#e91e63', 60, 0, 1),
('copy_status', 'Copy Status', 'core', 'missing', 'Missing', '#9c27b0', 70, 0, 1),
('copy_status', 'Copy Status', 'core', 'lost', 'Lost', '#f44336', 80, 0, 1),
('copy_status', 'Copy Status', 'core', 'withdrawn', 'Withdrawn', '#9e9e9e', 90, 0, 1),
('copy_status', 'Copy Status', 'core', 'reference', 'Reference Only', '#3f51b5', 100, 0, 1),
('copy_status', 'Copy Status', 'core', 'restricted', 'Restricted Access', '#ff5722', 110, 0, 1);

-- Order status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('library_order_status', 'Order Status', 'finance', 'draft', 'Draft', '#9e9e9e', 10, 1, 1),
('library_order_status', 'Order Status', 'finance', 'submitted', 'Submitted', '#2196f3', 20, 0, 1),
('library_order_status', 'Order Status', 'finance', 'approved', 'Approved', '#4caf50', 30, 0, 1),
('library_order_status', 'Order Status', 'finance', 'ordered', 'Ordered', '#00bcd4', 40, 0, 1),
('library_order_status', 'Order Status', 'finance', 'partial', 'Partially Received', '#ff9800', 50, 0, 1),
('library_order_status', 'Order Status', 'finance', 'received', 'Received', '#4caf50', 60, 0, 1),
('library_order_status', 'Order Status', 'finance', 'cancelled', 'Cancelled', '#f44336', 70, 0, 1);

-- Order type
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('library_order_type', 'Order Type', 'finance', 'purchase', 'Purchase', 10, 1, 1),
('library_order_type', 'Order Type', 'finance', 'standing_order', 'Standing Order', 20, 0, 1),
('library_order_type', 'Order Type', 'finance', 'gift', 'Gift/Donation', 30, 0, 1),
('library_order_type', 'Order Type', 'finance', 'exchange', 'Exchange', 40, 0, 1),
('library_order_type', 'Order Type', 'finance', 'deposit', 'Deposit', 50, 0, 1),
('library_order_type', 'Order Type', 'finance', 'approval', 'Approval Plan', 60, 0, 1);

-- Serial issue status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('serial_issue_status', 'Serial Issue Status', 'core', 'expected', 'Expected', '#9e9e9e', 10, 1, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'received', 'Received', '#4caf50', 20, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'missing', 'Missing', '#f44336', 30, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'claimed', 'Claimed', '#ff9800', 40, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'damaged', 'Damaged', '#795548', 50, 0, 1),
('serial_issue_status', 'Serial Issue Status', 'core', 'bound', 'Bound', '#3f51b5', 60, 0, 1);

-- Subscription status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('subscription_status', 'Subscription Status', 'core', 'active', 'Active', '#4caf50', 10, 1, 1),
('subscription_status', 'Subscription Status', 'core', 'pending', 'Pending Renewal', '#ff9800', 20, 0, 1),
('subscription_status', 'Subscription Status', 'core', 'cancelled', 'Cancelled', '#f44336', 30, 0, 1),
('subscription_status', 'Subscription Status', 'core', 'expired', 'Expired', '#9e9e9e', 40, 0, 1),
('subscription_status', 'Subscription Status', 'core', 'suspended', 'Suspended', '#795548', 50, 0, 1);

-- ILL status
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, sort_order, is_default, is_active) VALUES
('ill_status', 'ILL Status', 'core', 'requested', 'Requested', '#9e9e9e', 10, 1, 1),
('ill_status', 'ILL Status', 'core', 'approved', 'Approved', '#2196f3', 20, 0, 1),
('ill_status', 'ILL Status', 'core', 'shipped', 'Shipped', '#00bcd4', 30, 0, 1),
('ill_status', 'ILL Status', 'core', 'received', 'Received', '#4caf50', 40, 0, 1),
('ill_status', 'ILL Status', 'core', 'in_use', 'In Use', '#ff9800', 50, 0, 1),
('ill_status', 'ILL Status', 'core', 'returned', 'Returned', '#8bc34a', 60, 0, 1),
('ill_status', 'ILL Status', 'core', 'overdue', 'Overdue', '#f44336', 70, 0, 1),
('ill_status', 'ILL Status', 'core', 'cancelled', 'Cancelled', '#607d8b', 80, 0, 1),
('ill_status', 'ILL Status', 'core', 'denied', 'Denied', '#9c27b0', 90, 0, 1);

-- ILL direction
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('ill_direction', 'ILL Direction', 'core', 'borrowing', 'Borrowing', 10, 1, 1),
('ill_direction', 'ILL Direction', 'core', 'lending', 'Lending', 20, 0, 1);

-- Payment method
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('payment_method', 'Payment Method', 'finance', 'cash', 'Cash', 10, 1, 1),
('payment_method', 'Payment Method', 'finance', 'card', 'Card Payment', 20, 0, 1),
('payment_method', 'Payment Method', 'finance', 'eft', 'EFT/Bank Transfer', 30, 0, 1),
('payment_method', 'Payment Method', 'finance', 'online', 'Online Payment', 40, 0, 1),
('payment_method', 'Payment Method', 'finance', 'deduction', 'Salary Deduction', 50, 0, 1);

-- Library acquisition method (for items)
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'purchase', 'Purchase', 10, 1, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'donation', 'Donation', 20, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'gift', 'Gift', 30, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'bequest', 'Bequest', 40, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'exchange', 'Exchange', 50, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'deposit', 'Legal Deposit', 60, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'transfer', 'Transfer', 70, 0, 1),
('library_acquisition_method', 'Library Acquisition Method', 'finance', 'unknown', 'Unknown', 80, 0, 1);

-- Budget category
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, sort_order, is_default, is_active) VALUES
('budget_category', 'Budget Category', 'finance', 'monographs', 'Monographs', 10, 1, 1),
('budget_category', 'Budget Category', 'finance', 'serials', 'Serials & Periodicals', 20, 0, 1),
('budget_category', 'Budget Category', 'finance', 'electronic', 'Electronic Resources', 30, 0, 1),
('budget_category', 'Budget Category', 'finance', 'special_collections', 'Special Collections', 40, 0, 1),
('budget_category', 'Budget Category', 'finance', 'binding', 'Binding & Repair', 50, 0, 1),
('budget_category', 'Budget Category', 'finance', 'ill', 'Interlibrary Loan', 60, 0, 1),
('budget_category', 'Budget Category', 'finance', 'media', 'Audio/Visual Media', 70, 0, 1),
('budget_category', 'Budget Category', 'finance', 'general', 'General', 80, 0, 1);

-- ============================================================================
-- 16. Add column mapping to ahg_dropdown_column_map
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown_column_map (table_name, column_name, taxonomy) VALUES
('library_patron', 'patron_type', 'patron_type'),
('library_patron', 'borrowing_status', 'borrowing_status'),
('library_copy', 'status', 'copy_status'),
('library_copy', 'condition_grade', 'condition_grade'),
('library_copy', 'acquisition_method', 'library_acquisition_method'),
('library_checkout', 'status', 'checkout_status'),
('library_checkout', 'return_condition', 'condition_grade'),
('library_hold', 'status', 'hold_status'),
('library_fine', 'fine_type', 'fine_type'),
('library_fine', 'status', 'fine_status'),
('library_fine', 'payment_method', 'payment_method'),
('library_subscription', 'status', 'subscription_status'),
('library_serial_issue', 'status', 'serial_issue_status'),
('library_order', 'status', 'library_order_status'),
('library_order', 'order_type', 'library_order_type'),
('library_order_line', 'status', 'library_order_status'),
('library_budget', 'category', 'budget_category'),
('library_ill_request', 'status', 'ill_status'),
('library_ill_request', 'direction', 'ill_direction'),
('library_item', 'acquisition_method', 'library_acquisition_method'),
('library_item', 'condition_grade', 'condition_grade'),
('library_item', 'conservation_priority', 'conservation_priority'),
('library_item', 'recognition_status', 'recognition_status'),
('library_item', 'valuation_method', 'valuation_method');
