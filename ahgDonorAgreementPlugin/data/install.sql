-- ============================================================
-- ahgDonorAgreementPlugin - Database Schema
-- DO NOT include INSERT INTO atom_plugin - handled by CLI
-- ============================================================

-- Agreement Types (reference table)
CREATE TABLE IF NOT EXISTS agreement_type (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    requires_witness TINYINT(1) DEFAULT 0,
    requires_legal_review TINYINT(1) DEFAULT 0,
    default_term_years INT NULL,
    sort_order INT DEFAULT 0,
    color VARCHAR(7) DEFAULT '#6c757d',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert standard agreement types (South African context)
INSERT IGNORE INTO agreement_type (name, code, description, requires_witness, requires_legal_review, sort_order, color) VALUES
('Deed of Gift', 'deed_of_gift', 'Unconditional transfer of ownership', 1, 1, 1, '#28a745'),
('Deed of Donation', 'deed_of_donation', 'Formal donation under SA law', 1, 1, 2, '#28a745'),
('Deposit Agreement', 'deposit', 'Materials deposited, ownership retained', 0, 0, 3, '#17a2b8'),
('Loan Agreement', 'loan', 'Temporary loan for exhibition/research', 0, 0, 4, '#ffc107'),
('Purchase Agreement', 'purchase', 'Acquisition through purchase', 0, 1, 5, '#6f42c1'),
('Bequest', 'bequest', 'Transfer through will or testament', 1, 1, 6, '#20c997'),
('Transfer Agreement', 'transfer', 'Inter-institutional transfer', 0, 1, 7, '#fd7e14'),
('Custody Agreement', 'custody', 'Temporary custody pending disposition', 0, 0, 8, '#6c757d'),
('License Agreement', 'license', 'Rights license without ownership transfer', 0, 1, 9, '#e83e8c'),
('MOU', 'mou', 'Memorandum of Understanding', 0, 0, 10, '#007bff');

-- Main Donor Agreement Table
CREATE TABLE IF NOT EXISTS donor_agreement (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    donor_id INT NULL,
    repository_id INT NULL,
    agreement_type_id INT UNSIGNED NULL,
    
    -- Parties
    donor_contact_info TEXT,
    institution_name VARCHAR(255),
    institution_contact_info TEXT,
    legal_representative VARCHAR(255),
    legal_representative_title VARCHAR(255),
    repository_representative VARCHAR(255),
    repository_representative_title VARCHAR(255),
    
    -- Status & Dates
    status ENUM('draft','pending_review','pending_signature','active','expired','terminated','superseded') DEFAULT 'draft',
    agreement_date DATE,
    effective_date DATE,
    expiry_date DATE,
    review_date DATE,
    termination_date DATE,
    termination_reason TEXT,
    
    -- Financial
    has_financial_terms TINYINT(1) DEFAULT 0,
    purchase_amount DECIMAL(15,2),
    currency VARCHAR(3) DEFAULT 'ZAR',
    payment_terms TEXT,
    
    -- Scope
    scope_description TEXT,
    extent_statement VARCHAR(255),
    transfer_method VARCHAR(100),
    transfer_date DATE,
    received_by VARCHAR(255),
    
    -- Terms
    general_terms TEXT,
    special_conditions TEXT,
    
    -- Signatures
    donor_signature_date DATE,
    donor_signature_name VARCHAR(255),
    repository_signature_date DATE,
    repository_signature_name VARCHAR(255),
    witness_name VARCHAR(255),
    witness_date DATE,
    
    -- Meta
    internal_notes TEXT,
    is_template TINYINT(1) DEFAULT 0,
    parent_agreement_id INT UNSIGNED NULL,
    supersedes_agreement_id INT UNSIGNED NULL,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_donor (donor_id),
    INDEX idx_repository (repository_id),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry_date),
    INDEX idx_review (review_date),
    INDEX idx_type (agreement_type_id),
    CONSTRAINT fk_da_agreement_type FOREIGN KEY (agreement_type_id) REFERENCES agreement_type(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agreement i18n
CREATE TABLE IF NOT EXISTS donor_agreement_i18n (
    id INT UNSIGNED NOT NULL,
    culture VARCHAR(7) NOT NULL DEFAULT 'en',
    title VARCHAR(255),
    scope_description TEXT,
    general_terms TEXT,
    special_conditions TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_dai_agreement FOREIGN KEY (id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rights granted
CREATE TABLE IF NOT EXISTS donor_agreement_right (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    right_type VARCHAR(100) NOT NULL,
    basis VARCHAR(100),
    start_date DATE,
    end_date DATE,
    granted_to VARCHAR(255),
    conditions TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agreement (donor_agreement_id),
    CONSTRAINT fk_dar_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restrictions
CREATE TABLE IF NOT EXISTS donor_agreement_restriction (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    restriction_type VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    review_date DATE,
    applies_to VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agreement (donor_agreement_id),
    INDEX idx_active (is_active),
    CONSTRAINT fk_dars_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents/Attachments
CREATE TABLE IF NOT EXISTS donor_agreement_document (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100),
    file_size INT UNSIGNED,
    description TEXT,
    uploaded_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agreement (donor_agreement_id),
    CONSTRAINT fk_dad_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reminders
CREATE TABLE IF NOT EXISTS donor_agreement_reminder (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    reminder_type VARCHAR(50) NOT NULL,
    reminder_date DATE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT,
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    is_recurring TINYINT(1) DEFAULT 0,
    recurrence_interval VARCHAR(20),
    next_reminder_date DATE,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME,
    completed_by INT UNSIGNED,
    notify_creator TINYINT(1) DEFAULT 1,
    notify_emails TEXT,
    created_by INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agreement (donor_agreement_id),
    INDEX idx_date (reminder_date),
    INDEX idx_completed (is_completed),
    CONSTRAINT fk_darm_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reminder Log
CREATE TABLE IF NOT EXISTS donor_agreement_reminder_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reminder_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    performed_by INT UNSIGNED,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reminder (reminder_id),
    CONSTRAINT fk_darml_reminder FOREIGN KEY (reminder_id) REFERENCES donor_agreement_reminder(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- History/Audit
CREATE TABLE IF NOT EXISTS donor_agreement_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_name VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    performed_by INT UNSIGNED,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agreement (donor_agreement_id),
    INDEX idx_action (action),
    CONSTRAINT fk_dah_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link to Accessions
CREATE TABLE IF NOT EXISTS donor_agreement_accession (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    accession_id INT NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_agreement_accession (donor_agreement_id, accession_id),
    INDEX idx_agreement (donor_agreement_id),
    INDEX idx_accession (accession_id),
    CONSTRAINT fk_daa_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link to Information Objects
CREATE TABLE IF NOT EXISTS donor_agreement_record (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_agreement_id INT UNSIGNED NOT NULL,
    information_object_id INT NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_agreement_record (donor_agreement_id, information_object_id),
    INDEX idx_agreement (donor_agreement_id),
    INDEX idx_io (information_object_id),
    CONSTRAINT fk_darec_agreement FOREIGN KEY (donor_agreement_id) REFERENCES donor_agreement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
