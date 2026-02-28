-- ============================================================================
-- Editor Bridge — Form template + field enhancements for publish gate integration
-- ahgFormsPlugin migration 002
-- ============================================================================

-- Add descriptive_standard to form templates for standard-aware resolution
-- MySQL 8 does not support ADD COLUMN IF NOT EXISTS, use procedure
DELIMITER //
CREATE PROCEDURE ahg_forms_002_migrate()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_form_template' AND COLUMN_NAME = 'descriptive_standard'
    ) THEN
        ALTER TABLE ahg_form_template ADD COLUMN descriptive_standard VARCHAR(100) NULL AFTER form_type;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_form_field' AND COLUMN_NAME = 'publish_gate_rule_type'
    ) THEN
        ALTER TABLE ahg_form_field ADD COLUMN publish_gate_rule_type VARCHAR(100) NULL AFTER css_class;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_form_field' AND COLUMN_NAME = 'gate_severity'
    ) THEN
        ALTER TABLE ahg_form_field ADD COLUMN gate_severity VARCHAR(50) NULL AFTER publish_gate_rule_type;
    END IF;
END //
DELIMITER ;

CALL ahg_forms_002_migrate();
DROP PROCEDURE IF EXISTS ahg_forms_002_migrate;
