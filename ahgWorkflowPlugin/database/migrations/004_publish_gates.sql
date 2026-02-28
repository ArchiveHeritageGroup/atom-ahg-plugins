-- ============================================================================
-- Publish Gate Engine — Tables, Dropdowns, Default Rules
-- ahgWorkflowPlugin migration 004
-- ============================================================================

-- Publish gate rules: configurable conditions that must pass before publishing
CREATE TABLE IF NOT EXISTS ahg_publish_gate_rule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    rule_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) DEFAULT 'information_object',
    level_of_description_id INT NULL,
    material_type VARCHAR(100) NULL,
    repository_id INT NULL,
    field_name VARCHAR(255) NULL,
    rule_config TEXT NULL,
    error_message VARCHAR(500) NOT NULL,
    severity VARCHAR(50) DEFAULT 'blocker',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_entity_type (entity_type),
    INDEX idx_level (level_of_description_id),
    INDEX idx_repo (repository_id),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Publish gate evaluation results: cached results from evaluating rules on objects
CREATE TABLE IF NOT EXISTS ahg_publish_gate_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    rule_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    details TEXT NULL,
    evaluated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    evaluated_by INT NULL,
    INDEX idx_object (object_id),
    INDEX idx_rule (rule_id),
    INDEX idx_status (status),
    INDEX idx_evaluated (evaluated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Dropdown seeds
-- ============================================================================

-- Rule types
INSERT IGNORE INTO ahg_dropdown (taxonomy, code, label, color, icon, sort_order, is_active) VALUES
('publish_gate_rule_type', 'field_required', 'Field Required', '#007bff', 'fa-asterisk', 1, 1),
('publish_gate_rule_type', 'field_not_empty', 'Field Not Empty', '#17a2b8', 'fa-align-left', 2, 1),
('publish_gate_rule_type', 'has_digital_object', 'Has Digital Object', '#28a745', 'fa-image', 3, 1),
('publish_gate_rule_type', 'has_rights', 'Has Rights Statement', '#ffc107', 'fa-balance-scale', 4, 1),
('publish_gate_rule_type', 'has_access_condition', 'Has Access Conditions', '#fd7e14', 'fa-lock', 5, 1),
('publish_gate_rule_type', 'security_cleared', 'Security Clearance Passed', '#dc3545', 'fa-shield-alt', 6, 1),
('publish_gate_rule_type', 'iiif_ready', 'IIIF Ready', '#6f42c1', 'fa-images', 7, 1),
('publish_gate_rule_type', 'custom_sql', 'Custom SQL Check', '#6c757d', 'fa-database', 8, 1);

-- Severity levels
INSERT IGNORE INTO ahg_dropdown (taxonomy, code, label, color, icon, sort_order, is_active) VALUES
('publish_gate_severity', 'blocker', 'Blocker', '#dc3545', 'fa-ban', 1, 1),
('publish_gate_severity', 'warning', 'Warning', '#ffc107', 'fa-exclamation-triangle', 2, 1);

-- Gate result statuses
INSERT IGNORE INTO ahg_dropdown (taxonomy, code, label, color, icon, sort_order, is_active) VALUES
('publish_gate_status', 'passed', 'Passed', '#28a745', 'fa-check-circle', 1, 1),
('publish_gate_status', 'failed', 'Failed', '#dc3545', 'fa-times-circle', 2, 1),
('publish_gate_status', 'warning', 'Warning', '#ffc107', 'fa-exclamation-triangle', 3, 1),
('publish_gate_status', 'skipped', 'Skipped', '#6c757d', 'fa-minus-circle', 4, 1);

-- Workflow history action types for gate events
INSERT IGNORE INTO ahg_dropdown (taxonomy, code, label, color, icon, sort_order, is_active) VALUES
('workflow_history_action', 'gate_evaluated', 'Gate Evaluated', '#17a2b8', 'fa-clipboard-check', 30, 1),
('workflow_history_action', 'gate_passed', 'Gate Passed', '#28a745', 'fa-check-double', 31, 1),
('workflow_history_action', 'gate_failed', 'Gate Failed', '#dc3545', 'fa-times-circle', 32, 1),
('workflow_history_action', 'gate_overridden', 'Gate Overridden', '#fd7e14', 'fa-user-shield', 33, 1);

-- ============================================================================
-- Default gate rules
-- ============================================================================

INSERT IGNORE INTO ahg_publish_gate_rule (name, rule_type, entity_type, field_name, error_message, severity, is_active, sort_order) VALUES
('Title required', 'field_required', 'information_object', 'title', 'A title is required before publishing', 'blocker', 1, 10),
('Scope and content not empty', 'field_not_empty', 'information_object', 'scope_and_content', 'Scope and content should be filled in before publishing', 'warning', 1, 20),
('At least one digital object', 'has_digital_object', 'information_object', NULL, 'At least one digital object should be attached', 'warning', 1, 30),
('Rights statement assigned', 'has_rights', 'information_object', NULL, 'A rights statement should be assigned before publishing', 'warning', 1, 40),
('Access conditions set', 'has_access_condition', 'information_object', 'access_conditions', 'Access conditions must be defined before publishing', 'blocker', 1, 50),
('Security clearance passed', 'security_cleared', 'information_object', NULL, 'Security clearance check must pass before publishing', 'blocker', 1, 60);
