-- =====================================================
-- Enhanced Loan Management Schema
-- Version: 1.1.0
-- Author: Johan Pieterse <johan@theahg.co.za>
-- =====================================================
-- Additional tables for enterprise GLAM loan management
-- Based on Spectrum 5.0 and CollectiveAccess features
-- =====================================================

-- =====================================================
-- FACILITY REPORTS (Borrower Venue Assessment)
-- =====================================================
-- Pre-loan assessment of the borrowing institution's facilities
-- Required for high-value or sensitive loans

CREATE TABLE IF NOT EXISTS loan_facility_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    -- Venue Information
    venue_name VARCHAR(255) NOT NULL,
    venue_address TEXT,
    venue_contact_name VARCHAR(255),
    venue_contact_email VARCHAR(255),
    venue_contact_phone VARCHAR(100),

    -- Assessment Date
    assessment_date DATE,
    assessed_by INT,

    -- Environmental Controls
    has_climate_control TINYINT(1) DEFAULT 0,
    temperature_min DECIMAL(5,2),
    temperature_max DECIMAL(5,2),
    humidity_min DECIMAL(5,2),
    humidity_max DECIMAL(5,2),
    has_uv_filtering TINYINT(1) DEFAULT 0,
    light_levels_lux INT,

    -- Security
    has_24hr_security TINYINT(1) DEFAULT 0,
    has_cctv TINYINT(1) DEFAULT 0,
    has_alarm_system TINYINT(1) DEFAULT 0,
    has_fire_suppression TINYINT(1) DEFAULT 0,
    fire_suppression_type VARCHAR(100),
    security_notes TEXT,

    -- Display/Storage
    display_case_type VARCHAR(100),
    mounting_method VARCHAR(100),
    barrier_distance DECIMAL(5,2),
    storage_type VARCHAR(100),

    -- Access
    public_access_hours TEXT,
    staff_supervision TINYINT(1) DEFAULT 0,
    photography_allowed TINYINT(1) DEFAULT 1,

    -- Overall Assessment
    overall_rating ENUM('excellent', 'good', 'acceptable', 'marginal', 'unacceptable') DEFAULT 'acceptable',
    recommendations TEXT,
    conditions_required TEXT,

    -- Approval
    approved TINYINT(1) DEFAULT 0,
    approved_by INT,
    approved_date DATETIME,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_facility_loan (loan_id),
    INDEX idx_facility_rating (overall_rating),
    INDEX idx_facility_approved (approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Facility report images
CREATE TABLE IF NOT EXISTS loan_facility_image (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_report_id BIGINT UNSIGNED NOT NULL,

    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    caption TEXT,
    image_type ENUM('exterior', 'interior', 'display_area', 'storage', 'security', 'climate_control', 'other') DEFAULT 'other',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (facility_report_id) REFERENCES loan_facility_report(id) ON DELETE CASCADE,
    INDEX idx_facility_image_report (facility_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONDITION REPORTS
-- =====================================================
-- Detailed condition documentation before and after loans

CREATE TABLE IF NOT EXISTS loan_condition_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    loan_object_id BIGINT UNSIGNED,
    information_object_id INT,

    -- Report Type
    report_type ENUM('pre_loan', 'post_loan', 'in_transit', 'periodic') NOT NULL DEFAULT 'pre_loan',

    -- Basic Info
    examination_date DATETIME NOT NULL,
    examiner_id INT,
    examiner_name VARCHAR(255),
    location VARCHAR(255),

    -- Overall Condition
    overall_condition ENUM('excellent', 'good', 'fair', 'poor', 'critical') NOT NULL DEFAULT 'good',
    condition_stable TINYINT(1) DEFAULT 1,

    -- Structural Condition
    structural_condition TEXT,
    surface_condition TEXT,

    -- Specific Issues
    has_damage TINYINT(1) DEFAULT 0,
    damage_description TEXT,
    has_previous_repairs TINYINT(1) DEFAULT 0,
    repair_description TEXT,
    has_active_deterioration TINYINT(1) DEFAULT 0,
    deterioration_description TEXT,

    -- Measurements (if applicable)
    height_cm DECIMAL(10,2),
    width_cm DECIMAL(10,2),
    depth_cm DECIMAL(10,2),
    weight_kg DECIMAL(10,2),

    -- Handling Requirements
    handling_requirements TEXT,
    mounting_requirements TEXT,
    environmental_requirements TEXT,

    -- Recommendations
    treatment_recommendations TEXT,
    display_recommendations TEXT,

    -- Sign-off
    signed_by_lender INT,
    signed_by_borrower INT,
    lender_signature_date DATETIME,
    borrower_signature_date DATETIME,

    -- PDF Export
    pdf_generated TINYINT(1) DEFAULT 0,
    pdf_path VARCHAR(500),

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_object_id) REFERENCES loan_object(id) ON DELETE SET NULL,
    INDEX idx_condition_loan (loan_id),
    INDEX idx_condition_object (loan_object_id),
    INDEX idx_condition_type (report_type),
    INDEX idx_condition_date (examination_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Condition report images
CREATE TABLE IF NOT EXISTS loan_condition_image (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condition_report_id BIGINT UNSIGNED NOT NULL,

    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    mime_type VARCHAR(100),

    -- Image details
    image_type ENUM('overall', 'detail', 'damage', 'measurement', 'comparison', 'other') DEFAULT 'overall',
    caption TEXT,
    annotation_data JSON,

    -- Position on object (for mapping)
    view_position ENUM('front', 'back', 'top', 'bottom', 'left', 'right', 'detail', 'other') DEFAULT 'front',

    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (condition_report_id) REFERENCES loan_condition_report(id) ON DELETE CASCADE,
    INDEX idx_condition_image_report (condition_report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COURIER/TRANSPORT MANAGEMENT
-- =====================================================

-- Courier/transport providers
CREATE TABLE IF NOT EXISTS loan_courier (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(100),
    address TEXT,
    website VARCHAR(255),

    -- Capabilities
    is_art_specialist TINYINT(1) DEFAULT 0,
    has_climate_control TINYINT(1) DEFAULT 0,
    has_gps_tracking TINYINT(1) DEFAULT 0,
    insurance_coverage DECIMAL(15,2),
    insurance_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Rating
    quality_rating DECIMAL(3,2),
    notes TEXT,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_courier_active (is_active),
    INDEX idx_courier_specialist (is_art_specialist)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipments
CREATE TABLE IF NOT EXISTS loan_shipment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    courier_id BIGINT UNSIGNED,

    -- Shipment Type
    shipment_type ENUM('outbound', 'return') NOT NULL DEFAULT 'outbound',

    -- Reference Numbers
    shipment_number VARCHAR(100),
    tracking_number VARCHAR(255),
    waybill_number VARCHAR(255),

    -- Route
    origin_address TEXT,
    destination_address TEXT,

    -- Dates
    scheduled_pickup DATETIME,
    actual_pickup DATETIME,
    scheduled_delivery DATETIME,
    actual_delivery DATETIME,

    -- Status
    status ENUM('planned', 'picked_up', 'in_transit', 'customs', 'out_for_delivery', 'delivered', 'failed', 'returned') DEFAULT 'planned',

    -- Handling
    handling_instructions TEXT,
    special_requirements TEXT,

    -- Cost
    shipping_cost DECIMAL(12,2),
    insurance_cost DECIMAL(12,2),
    customs_cost DECIMAL(12,2),
    total_cost DECIMAL(12,2),
    cost_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Documents
    customs_declaration_number VARCHAR(255),

    -- Couriers (if multiple handlers)
    courier_names TEXT,
    courier_contact VARCHAR(255),

    notes TEXT,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (courier_id) REFERENCES loan_courier(id) ON DELETE SET NULL,
    INDEX idx_shipment_loan (loan_id),
    INDEX idx_shipment_status (status),
    INDEX idx_shipment_tracking (tracking_number),
    INDEX idx_shipment_dates (scheduled_delivery)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shipment tracking events
CREATE TABLE IF NOT EXISTS loan_shipment_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id BIGINT UNSIGNED NOT NULL,

    event_time DATETIME NOT NULL,
    event_type VARCHAR(100),
    location VARCHAR(255),
    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (shipment_id) REFERENCES loan_shipment(id) ON DELETE CASCADE,
    INDEX idx_shipment_event (shipment_id, event_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

-- Notification templates
CREATE TABLE IF NOT EXISTS loan_notification_template (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,

    -- Template Content
    subject_template VARCHAR(500),
    body_template TEXT,

    -- Trigger
    trigger_event VARCHAR(100),
    trigger_days_before INT,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_notification_code (code),
    INDEX idx_notification_trigger (trigger_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification log
CREATE TABLE IF NOT EXISTS loan_notification_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED,

    notification_type VARCHAR(100),
    recipient_email VARCHAR(255),
    recipient_name VARCHAR(255),

    subject VARCHAR(500),
    body TEXT,

    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES loan_notification_template(id) ON DELETE SET NULL,
    INDEX idx_notification_loan (loan_id),
    INDEX idx_notification_status (status),
    INDEX idx_notification_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PACKING LISTS
-- =====================================================

CREATE TABLE IF NOT EXISTS loan_packing_list (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    shipment_id BIGINT UNSIGNED,

    list_number VARCHAR(100),

    -- Crate/Container Info
    crate_count INT DEFAULT 1,
    total_weight_kg DECIMAL(10,2),
    total_volume_cbm DECIMAL(10,3),

    -- Packing Details
    packing_date DATE,
    packed_by VARCHAR(255),

    -- Verification
    verified_by VARCHAR(255),
    verification_date DATE,

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    FOREIGN KEY (shipment_id) REFERENCES loan_shipment(id) ON DELETE SET NULL,
    INDEX idx_packing_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loan_packing_item (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    packing_list_id BIGINT UNSIGNED NOT NULL,
    loan_object_id BIGINT UNSIGNED,

    crate_number INT DEFAULT 1,
    item_number INT,

    object_description VARCHAR(500),

    -- Dimensions
    height_cm DECIMAL(10,2),
    width_cm DECIMAL(10,2),
    depth_cm DECIMAL(10,2),
    weight_kg DECIMAL(10,2),

    -- Packing Materials
    packing_materials TEXT,
    orientation VARCHAR(100),

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (packing_list_id) REFERENCES loan_packing_list(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_object_id) REFERENCES loan_object(id) ON DELETE SET NULL,
    INDEX idx_packing_item_list (packing_list_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COST TRACKING
-- =====================================================

CREATE TABLE IF NOT EXISTS loan_cost (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,

    cost_type ENUM('transport', 'insurance', 'conservation', 'framing', 'crating', 'customs', 'courier_fee', 'handling', 'photography', 'other') NOT NULL,
    description VARCHAR(500),

    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',

    -- Payment Info
    vendor VARCHAR(255),
    invoice_number VARCHAR(100),
    invoice_date DATE,
    paid TINYINT(1) DEFAULT 0,
    paid_date DATE,

    -- Who Pays
    paid_by ENUM('lender', 'borrower', 'shared') DEFAULT 'borrower',

    notes TEXT,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id) REFERENCES loan(id) ON DELETE CASCADE,
    INDEX idx_cost_loan (loan_id),
    INDEX idx_cost_type (cost_type),
    INDEX idx_cost_paid (paid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT NOTIFICATION TEMPLATES
-- =====================================================

INSERT INTO loan_notification_template (code, name, description, subject_template, body_template, trigger_event, trigger_days_before, is_active)
VALUES
('loan_due_30', 'Loan Due in 30 Days', 'Reminder sent 30 days before loan end date',
 'Loan {{loan_number}} Due in 30 Days',
 'Dear {{partner_contact_name}},\n\nThis is a reminder that loan {{loan_number}} is due to be returned on {{end_date}}.\n\nPlease begin making arrangements for the return of the loaned objects.\n\nBest regards,\n{{institution_name}}',
 'due_date', 30, 1),

('loan_due_14', 'Loan Due in 14 Days', 'Reminder sent 14 days before loan end date',
 'Loan {{loan_number}} Due in 14 Days - Action Required',
 'Dear {{partner_contact_name}},\n\nThis is a reminder that loan {{loan_number}} is due to be returned on {{end_date}}.\n\nPlease ensure all necessary arrangements are in place for the safe return of the objects.\n\nIf you require an extension, please contact us immediately.\n\nBest regards,\n{{institution_name}}',
 'due_date', 14, 1),

('loan_due_7', 'Loan Due in 7 Days', 'Final reminder sent 7 days before loan end date',
 'URGENT: Loan {{loan_number}} Due in 7 Days',
 'Dear {{partner_contact_name}},\n\nThis is a final reminder that loan {{loan_number}} is due to be returned on {{end_date}}.\n\nPlease confirm the return arrangements as soon as possible.\n\nBest regards,\n{{institution_name}}',
 'due_date', 7, 1),

('loan_overdue', 'Loan Overdue', 'Notification sent when loan is overdue',
 'OVERDUE: Loan {{loan_number}} - Immediate Action Required',
 'Dear {{partner_contact_name}},\n\nLoan {{loan_number}} was due to be returned on {{end_date}} and is now overdue.\n\nPlease contact us immediately to arrange the return of the loaned objects.\n\nBest regards,\n{{institution_name}}',
 'overdue', 0, 1),

('loan_approved', 'Loan Approved', 'Notification when loan request is approved',
 'Loan Request {{loan_number}} Approved',
 'Dear {{partner_contact_name}},\n\nYour loan request {{loan_number}} has been approved.\n\nLoan Period: {{start_date}} to {{end_date}}\nPurpose: {{purpose}}\n\nWe will be in touch regarding the loan agreement and next steps.\n\nBest regards,\n{{institution_name}}',
 'status_change', 0, 1),

('loan_dispatched', 'Objects Dispatched', 'Notification when objects are dispatched',
 'Loan {{loan_number}} - Objects Dispatched',
 'Dear {{partner_contact_name}},\n\nThe objects for loan {{loan_number}} have been dispatched.\n\nTracking Number: {{tracking_number}}\nExpected Delivery: {{scheduled_delivery}}\n\nPlease confirm receipt once the objects arrive.\n\nBest regards,\n{{institution_name}}',
 'status_change', 0, 1)

ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- INSERT DEFAULT COURIERS (South African)
-- =====================================================

INSERT INTO loan_courier (company_name, contact_email, is_art_specialist, has_climate_control, has_gps_tracking, notes, is_active)
VALUES
('Mtunzini Group', 'info@mtunzini.co.za', 1, 1, 1, 'Specialist art and heritage logistics in Southern Africa', 1),
('Crown Fine Art', 'southafrica@crownfineart.com', 1, 1, 1, 'International art logistics with SA presence', 1),
('DHL Express', 'info@dhl.co.za', 0, 0, 1, 'General courier with tracking', 1),
('RAM Hand-to-Hand', 'info@ram.co.za', 0, 0, 1, 'Secure courier service', 1),
('The Courier Guy', 'info@thecourierguy.co.za', 0, 0, 1, 'General courier with tracking', 1)
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name);
