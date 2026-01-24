-- =====================================================
-- Loan Management Module Schema
-- Version: 1.0.0
-- Author: Johan Pieterse <johan@theahg.co.za>
-- =====================================================
-- Comprehensive loan management for GLAM institutions
-- Supports both loan out (lending) and loan in (borrowing)
-- =====================================================

-- =====================================================
-- Core Loan Tables
-- =====================================================

-- Main loan record
CREATE TABLE IF NOT EXISTS loan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Loan identification
    loan_number VARCHAR(50) NOT NULL UNIQUE,
    loan_type ENUM('out', 'in') NOT NULL,

    -- Basic information
    title VARCHAR(500),
    description TEXT,
    purpose ENUM('exhibition', 'research', 'conservation', 'photography', 'education', 'filming', 'long_term', 'other') DEFAULT 'exhibition',

    -- Partner institution (borrower for loan_out, lender for loan_in)
    partner_institution VARCHAR(500) NOT NULL,
    partner_contact_name VARCHAR(255),
    partner_contact_email VARCHAR(255),
    partner_contact_phone VARCHAR(100),
    partner_address TEXT,

    -- Key dates
    request_date DATETIME,
    start_date DATE,
    end_date DATE,
    return_date DATE,

    -- Insurance
    insurance_type ENUM('borrower', 'lender', 'shared', 'government', 'self') DEFAULT 'borrower',
    insurance_value DECIMAL(15,2),
    insurance_currency VARCHAR(3) DEFAULT 'ZAR',
    insurance_policy_number VARCHAR(100),
    insurance_provider VARCHAR(255),

    -- Fees
    loan_fee DECIMAL(12,2),
    loan_fee_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Approval
    internal_approver_id INT,
    approved_date DATETIME,

    -- Related exhibition (if applicable)
    exhibition_id BIGINT UNSIGNED,

    -- Notes
    notes TEXT,

    -- Tracking
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_loan_number (loan_number),
    INDEX idx_loan_type (loan_type),
    INDEX idx_loan_partner (partner_institution(100)),
    INDEX idx_loan_dates (start_date, end_date),
    INDEX idx_loan_return (return_date),
    INDEX idx_loan_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objects included in a loan (many-to-many)
CREATE TABLE IF NOT EXISTS loan_object (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    information_object_id INT NOT NULL,

    -- Object details (cached for external objects not in AtoM)
    object_title VARCHAR(500),
    object_identifier VARCHAR(255),

    -- Insurance
    insurance_value DECIMAL(15,2),

    -- Condition reporting
    condition_report_id BIGINT UNSIGNED,
    condition_on_departure TEXT,
    condition_on_return TEXT,

    -- Requirements
    special_requirements TEXT,
    display_requirements TEXT,

    -- Status tracking
    status ENUM('pending', 'approved', 'prepared', 'dispatched', 'received', 'on_display', 'packed', 'returned') DEFAULT 'pending',

    -- Dates
    dispatched_date DATE,
    received_date DATE,
    returned_date DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_loanobj_loan (loan_id),
    INDEX idx_loanobj_object (information_object_id),
    INDEX idx_loanobj_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents attached to loans
CREATE TABLE IF NOT EXISTS loan_document (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    document_type ENUM('agreement', 'condition_report', 'insurance', 'courier', 'correspondence', 'receipt', 'other') NOT NULL,

    file_path VARCHAR(500),
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    file_size BIGINT,

    description TEXT,

    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_loandoc_loan (loan_id),
    INDEX idx_loandoc_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan extension history
CREATE TABLE IF NOT EXISTS loan_extension (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    previous_end_date DATE NOT NULL,
    new_end_date DATE NOT NULL,

    reason TEXT,
    approved_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_loanext_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Workflow Tables (if not already created by framework)
-- =====================================================

-- Workflow definitions
CREATE TABLE IF NOT EXISTS workflow_definition (
    id VARCHAR(100) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    entity_type VARCHAR(100) NOT NULL,
    initial_state VARCHAR(100) NOT NULL,
    states JSON NOT NULL,
    transitions JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow instances
CREATE TABLE IF NOT EXISTS workflow_instance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,

    current_state VARCHAR(100) NOT NULL,
    is_complete TINYINT(1) DEFAULT 0,

    context JSON,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_wi_workflow (workflow_id),
    INDEX idx_wi_entity (entity_type, entity_id),
    INDEX idx_wi_state (current_state),
    UNIQUE KEY uk_workflow_entity (workflow_id, entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workflow history
CREATE TABLE IF NOT EXISTS workflow_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id BIGINT UNSIGNED NOT NULL,

    from_state VARCHAR(100),
    to_state VARCHAR(100) NOT NULL,
    transition_name VARCHAR(100),

    comment TEXT,
    performed_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (instance_id) REFERENCES workflow_instance(id) ON DELETE CASCADE,
    INDEX idx_wh_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Loan Workflow Definitions
-- =====================================================

INSERT INTO workflow_definition (id, name, description, entity_type, initial_state, states, transitions, is_active)
VALUES
('loan_out', 'Loan Out Workflow', 'Workflow for outgoing loans (lending objects)', 'loan', 'draft',
'["draft", "submitted", "under_review", "approved", "preparing", "dispatched", "on_loan", "return_requested", "returned", "closed", "cancelled"]',
'[
  {"name": "submit", "from": ["draft"], "to": "submitted", "label": "Submit for Review"},
  {"name": "review", "from": ["submitted"], "to": "under_review", "label": "Begin Review"},
  {"name": "approve", "from": ["under_review"], "to": "approved", "label": "Approve Loan"},
  {"name": "reject", "from": ["submitted", "under_review"], "to": "draft", "label": "Return to Draft"},
  {"name": "prepare", "from": ["approved"], "to": "preparing", "label": "Begin Preparation"},
  {"name": "dispatch", "from": ["preparing"], "to": "dispatched", "label": "Dispatch Objects"},
  {"name": "confirm_receipt", "from": ["dispatched"], "to": "on_loan", "label": "Confirm Receipt"},
  {"name": "request_return", "from": ["on_loan"], "to": "return_requested", "label": "Request Return"},
  {"name": "receive_return", "from": ["return_requested", "on_loan"], "to": "returned", "label": "Receive Return"},
  {"name": "close", "from": ["returned"], "to": "closed", "label": "Close Loan"},
  {"name": "cancel", "from": ["draft", "submitted", "under_review", "approved"], "to": "cancelled", "label": "Cancel Loan"}
]',
1),

('loan_in', 'Loan In Workflow', 'Workflow for incoming loans (borrowing objects)', 'loan', 'draft',
'["draft", "submitted", "approved", "awaiting_delivery", "received", "on_display", "packing", "returned", "closed", "cancelled"]',
'[
  {"name": "submit", "from": ["draft"], "to": "submitted", "label": "Submit Request"},
  {"name": "approve", "from": ["submitted"], "to": "approved", "label": "Mark Approved"},
  {"name": "reject", "from": ["submitted"], "to": "draft", "label": "Return to Draft"},
  {"name": "await_delivery", "from": ["approved"], "to": "awaiting_delivery", "label": "Await Delivery"},
  {"name": "receive", "from": ["awaiting_delivery"], "to": "received", "label": "Receive Objects"},
  {"name": "install", "from": ["received"], "to": "on_display", "label": "Install on Display"},
  {"name": "begin_packing", "from": ["on_display"], "to": "packing", "label": "Begin Packing"},
  {"name": "return", "from": ["packing", "received"], "to": "returned", "label": "Return Objects"},
  {"name": "close", "from": ["returned"], "to": "closed", "label": "Close Loan"},
  {"name": "cancel", "from": ["draft", "submitted", "approved"], "to": "cancelled", "label": "Cancel Request"}
]',
1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    states = VALUES(states),
    transitions = VALUES(transitions);
