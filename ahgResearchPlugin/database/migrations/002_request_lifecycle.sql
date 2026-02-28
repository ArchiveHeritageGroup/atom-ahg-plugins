-- =========================================================================
-- Migration 002: Request Lifecycle — SLA, Triage, Correspondence
-- Issues #178 (Request Lifecycle) + #179 (Retrieval/Custody prep)
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. Correspondence table
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS research_request_correspondence (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    request_id      INT NOT NULL,
    request_type    VARCHAR(50) NOT NULL DEFAULT 'material' COMMENT 'material|reproduction',
    sender_type     VARCHAR(50) NOT NULL DEFAULT 'staff' COMMENT 'staff|researcher',
    sender_id       INT NULL,
    subject         VARCHAR(255) NULL,
    body            TEXT NOT NULL,
    is_internal     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Staff-only note, hidden from researcher',
    attachment_path VARCHAR(500) NULL,
    attachment_name VARCHAR(255) NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_request (request_id, request_type),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------------
-- 2. ALTER research_reproduction_request — add lifecycle columns
-- -------------------------------------------------------------------------
-- MySQL 8 does not support ADD COLUMN IF NOT EXISTS.
-- Use a procedure to conditionally add columns.
DELIMITER //
CREATE PROCEDURE _migration_002_repro()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'research_reproduction_request'
        AND COLUMN_NAME = 'triage_status') THEN
        ALTER TABLE research_reproduction_request
            ADD COLUMN triage_status VARCHAR(50) NULL DEFAULT NULL AFTER admin_notes,
            ADD COLUMN triage_by INT NULL DEFAULT NULL AFTER triage_status,
            ADD COLUMN triage_at DATETIME NULL DEFAULT NULL AFTER triage_by,
            ADD COLUMN triage_notes TEXT NULL AFTER triage_at,
            ADD COLUMN sla_due_date DATE NULL DEFAULT NULL AFTER triage_notes,
            ADD COLUMN assigned_to INT NULL DEFAULT NULL AFTER sla_due_date,
            ADD COLUMN closed_at DATETIME NULL DEFAULT NULL AFTER assigned_to,
            ADD COLUMN closed_by INT NULL DEFAULT NULL AFTER closed_at,
            ADD COLUMN closure_reason VARCHAR(100) NULL DEFAULT NULL AFTER closed_by;
    END IF;
END //
DELIMITER ;

CALL _migration_002_repro();
DROP PROCEDURE IF EXISTS _migration_002_repro;

-- -------------------------------------------------------------------------
-- 3. ALTER research_material_request — add lifecycle columns
-- -------------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE _migration_002_material()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'research_material_request'
        AND COLUMN_NAME = 'sla_due_date') THEN
        ALTER TABLE research_material_request
            ADD COLUMN sla_due_date DATE NULL DEFAULT NULL AFTER condition_notes,
            ADD COLUMN assigned_to INT NULL DEFAULT NULL AFTER sla_due_date,
            ADD COLUMN triage_status VARCHAR(50) NULL DEFAULT NULL AFTER assigned_to,
            ADD COLUMN triage_by INT NULL DEFAULT NULL AFTER triage_status,
            ADD COLUMN triage_at DATETIME NULL DEFAULT NULL AFTER triage_by;
    END IF;
END //
DELIMITER ;

CALL _migration_002_material();
DROP PROCEDURE IF EXISTS _migration_002_material;

-- -------------------------------------------------------------------------
-- 4. Dropdown seeds — request lifecycle statuses
-- -------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, icon, sort_order, is_default, is_active)
VALUES
    -- Request lifecycle status
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'submitted',        'Submitted',          '#3498db', 'paper-plane',          10, 0, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'pending_triage',   'Pending Triage',     '#f39c12', 'clipboard-question',   20, 1, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'triage_approved',  'Triage Approved',    '#27ae60', 'clipboard-check',      30, 0, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'triage_denied',    'Triage Denied',      '#e74c3c', 'ban',                  40, 0, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'needs_information','Needs Information',  '#9b59b6', 'circle-question',      50, 0, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'in_fulfilment',    'In Fulfilment',      '#2980b9', 'gears',                60, 0, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'delivered',        'Delivered',          '#27ae60', 'check-circle',         70, 0, 1),
    ('request_lifecycle_status', 'Request Lifecycle Status', 'research', 'closed',           'Closed',             '#6c757d', 'lock',                 80, 0, 1),

    -- Request type
    ('request_type', 'Request Type', 'research', 'reference_inquiry',   'Reference Inquiry',      '#17a2b8', 'magnifying-glass',  10, 0, 1),
    ('request_type', 'Request Type', 'research', 'reproduction_copy',   'Reproduction Copy',      '#6f42c1', 'copy',              20, 0, 1),
    ('request_type', 'Request Type', 'research', 'certified_copy',      'Certified Copy',         '#e83e8c', 'certificate',       30, 0, 1),
    ('request_type', 'Request Type', 'research', 'reading_room_access', 'Reading Room Access',    '#20c997', 'book-open',         40, 1, 1),
    ('request_type', 'Request Type', 'research', 'remote_access',       'Remote Access',          '#fd7e14', 'globe',             50, 0, 1),

    -- Triage decision
    ('request_triage_decision', 'Triage Decision', 'research', 'approved',          'Approved',           '#28a745', 'check',      10, 0, 1),
    ('request_triage_decision', 'Triage Decision', 'research', 'denied',            'Denied',             '#dc3545', 'times',      20, 0, 1),
    ('request_triage_decision', 'Triage Decision', 'research', 'needs_info',        'Needs Information',  '#ffc107', 'question',   30, 0, 1),

    -- Closure reason
    ('request_closure_reason', 'Closure Reason', 'research', 'fulfilled',          'Fulfilled',            '#28a745', 'check-circle',     10, 1, 1),
    ('request_closure_reason', 'Closure Reason', 'research', 'cancelled_by_user',  'Cancelled by User',    '#6c757d', 'user-slash',       20, 0, 1),
    ('request_closure_reason', 'Closure Reason', 'research', 'denied',             'Denied',               '#dc3545', 'ban',              30, 0, 1),
    ('request_closure_reason', 'Closure Reason', 'research', 'no_response',        'No Response',          '#fd7e14', 'clock',            40, 0, 1),
    ('request_closure_reason', 'Closure Reason', 'research', 'duplicate',          'Duplicate',            '#6c757d', 'clone',            50, 0, 1),

    -- Correspondence sender type
    ('correspondence_sender_type', 'Correspondence Sender', 'research', 'staff',      'Staff',      '#0d6efd', 'user-tie',    10, 0, 1),
    ('correspondence_sender_type', 'Correspondence Sender', 'research', 'researcher', 'Researcher', '#198754', 'user',        20, 0, 1),
    ('correspondence_sender_type', 'Correspondence Sender', 'research', 'system',     'System',     '#6c757d', 'robot',       30, 0, 1);

-- -------------------------------------------------------------------------
-- 5. SLA policy seed for research requests
-- -------------------------------------------------------------------------
INSERT IGNORE INTO ahg_workflow_sla_policy (name, queue_id, warning_days, due_days, escalation_days, escalation_action, is_active)
SELECT 'Research Request Fulfilment SLA', q.id, 7, 10, 14, 'notify_admin', 1
FROM research_request_queue q
WHERE q.code = 'new'
AND NOT EXISTS (
    SELECT 1 FROM ahg_workflow_sla_policy WHERE name = 'Research Request Fulfilment SLA'
);

-- Add workflow_history_action entries for research-specific events
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, icon, sort_order, is_active)
VALUES
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'request_submitted',     'Request Submitted',       '#3498db', 'paper-plane',        110, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'request_triaged',       'Request Triaged',         '#f39c12', 'clipboard-check',    120, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'request_assigned',      'Request Assigned',        '#2980b9', 'user-plus',          130, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'request_closed',        'Request Closed',          '#6c757d', 'lock',               140, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'correspondence_added',  'Correspondence Added',    '#17a2b8', 'envelope',           150, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'custody_checkout',      'Custody Checkout',        '#fd7e14', 'arrow-right-from-bracket', 160, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'custody_checkin',       'Custody Check-in',        '#28a745', 'arrow-right-to-bracket',   170, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'custody_transfer',      'Custody Transfer',        '#6f42c1', 'right-left',         180, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'custody_return_verified','Return Verified',        '#20c997', 'shield-check',       190, 1),
    ('workflow_history_action', 'Workflow History Action', 'workflow', 'location_updated',      'Location Updated',        '#0dcaf0', 'map-pin',            200, 1);
