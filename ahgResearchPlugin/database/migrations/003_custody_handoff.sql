-- =========================================================================
-- Migration 003: Custody Handoff — Chain of Custody for Material Requests
-- Issue #179 (Retrieval Queue + Custody Movement Trail)
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. Custody handoff table
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS research_custody_handoff (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    material_request_id  INT NOT NULL,
    handoff_type         VARCHAR(50) NOT NULL COMMENT 'checkout|checkin|transfer|return_to_storage|condition_check',
    from_handler_id      INT NULL COMMENT 'Staff/researcher releasing',
    to_handler_id        INT NULL COMMENT 'Staff/researcher receiving',
    from_location        VARCHAR(255) NULL,
    to_location          VARCHAR(255) NULL,
    condition_at_handoff VARCHAR(50) NULL COMMENT 'excellent|good|fair|poor|critical',
    condition_notes      TEXT NULL,
    signature_confirmed  TINYINT(1) NOT NULL DEFAULT 0,
    confirmed_at         DATETIME NULL,
    confirmed_by         INT NULL,
    barcode_scanned      VARCHAR(100) NULL,
    spectrum_movement_id INT NULL COMMENT 'FK to spectrum_movement if created',
    notes                TEXT NULL,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by           INT NULL,

    INDEX idx_request (material_request_id),
    INDEX idx_type (handoff_type),
    INDEX idx_spectrum (spectrum_movement_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------------
-- 2. ALTER research_material_request — add custody columns
-- -------------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE _migration_003_material()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'research_material_request'
        AND COLUMN_NAME = 'checkout_confirmed_at') THEN
        ALTER TABLE research_material_request
            ADD COLUMN checkout_confirmed_at DATETIME NULL DEFAULT NULL AFTER triage_by,
            ADD COLUMN checkout_confirmed_by INT NULL DEFAULT NULL AFTER checkout_confirmed_at,
            ADD COLUMN return_condition VARCHAR(50) NULL DEFAULT NULL AFTER checkout_confirmed_by,
            ADD COLUMN return_verified_by INT NULL DEFAULT NULL AFTER return_condition,
            ADD COLUMN return_verified_at DATETIME NULL DEFAULT NULL AFTER return_verified_by;
    END IF;
END //
DELIMITER ;

CALL _migration_003_material();
DROP PROCEDURE IF EXISTS _migration_003_material;

-- -------------------------------------------------------------------------
-- 3. Dropdown seeds — custody types and conditions
-- -------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, taxonomy_section, code, label, color, icon, sort_order, is_default, is_active)
VALUES
    -- Custody handoff type
    ('custody_handoff_type', 'Custody Handoff Type', 'research', 'checkout',          'Checkout',           '#fd7e14', 'arrow-right-from-bracket', 10, 0, 1),
    ('custody_handoff_type', 'Custody Handoff Type', 'research', 'checkin',           'Check-in',           '#28a745', 'arrow-right-to-bracket',   20, 0, 1),
    ('custody_handoff_type', 'Custody Handoff Type', 'research', 'transfer',          'Transfer',           '#6f42c1', 'right-left',               30, 0, 1),
    ('custody_handoff_type', 'Custody Handoff Type', 'research', 'return_to_storage', 'Return to Storage',  '#0dcaf0', 'warehouse',                40, 0, 1),
    ('custody_handoff_type', 'Custody Handoff Type', 'research', 'condition_check',   'Condition Check',    '#ffc107', 'stethoscope',              50, 0, 1),

    -- Custody condition (mirrors information_object_physical_location.condition_status values)
    ('custody_condition', 'Custody Condition', 'research', 'excellent', 'Excellent', '#28a745', 'star',           10, 0, 1),
    ('custody_condition', 'Custody Condition', 'research', 'good',      'Good',      '#20c997', 'thumbs-up',     20, 1, 1),
    ('custody_condition', 'Custody Condition', 'research', 'fair',      'Fair',      '#ffc107', 'minus-circle',  30, 0, 1),
    ('custody_condition', 'Custody Condition', 'research', 'poor',      'Poor',      '#fd7e14', 'exclamation-triangle', 40, 0, 1),
    ('custody_condition', 'Custody Condition', 'research', 'critical',  'Critical',  '#dc3545', 'skull-crossbones',     50, 0, 1);
