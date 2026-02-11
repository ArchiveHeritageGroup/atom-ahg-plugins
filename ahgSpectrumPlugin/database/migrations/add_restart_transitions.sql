-- ============================================================
-- Migration: Add restart transitions to all workflow configs
-- This allows procedures to be restarted from their final state
-- History is preserved in spectrum_workflow_history for audit
-- ============================================================

-- acquisition: completed → proposed
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "proposed", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'acquisition' AND is_active = 1;

-- cataloguing: completed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'cataloguing' AND is_active = 1;

-- object_entry: completed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'object_entry' AND is_active = 1;

-- location_movement: verified → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["verified"]}' AS JSON))
WHERE procedure_type = 'location_movement' AND is_active = 1;

-- inventory_control: completed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'inventory_control' AND is_active = 1;

-- conservation: completed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'conservation' AND is_active = 1;

-- valuation: approved → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["approved"]}' AS JSON))
WHERE procedure_type = 'valuation' AND is_active = 1;

-- insurance: covered → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["covered"]}' AS JSON))
WHERE procedure_type = 'insurance' AND is_active = 1;

-- loss_damage: resolved → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["resolved"]}' AS JSON))
WHERE procedure_type = 'loss_damage' AND is_active = 1;

-- deaccession: completed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'deaccession' AND is_active = 1;

-- disposal: documented → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["documented"]}' AS JSON))
WHERE procedure_type = 'disposal' AND is_active = 1;

-- object_exit: confirmed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["confirmed"]}' AS JSON))
WHERE procedure_type = 'object_exit' AND is_active = 1;

-- loans_in: returned → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["returned"]}' AS JSON))
WHERE procedure_type = 'loans_in' AND is_active = 1;

-- loans_out: condition_checked → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["condition_checked"]}' AS JSON))
WHERE procedure_type = 'loans_out' AND is_active = 1;

-- condition_checking: reviewed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["reviewed"]}' AS JSON))
WHERE procedure_type = 'condition_checking' AND is_active = 1;

-- risk_management: monitored → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["monitored"]}' AS JSON))
WHERE procedure_type = 'risk_management' AND is_active = 1;

-- audit: closed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["closed"]}' AS JSON))
WHERE procedure_type = 'audit' AND is_active = 1;

-- rights_management: monitored → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["monitored"]}' AS JSON))
WHERE procedure_type = 'rights_management' AND is_active = 1;

-- reproduction: delivered → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["delivered"]}' AS JSON))
WHERE procedure_type = 'reproduction' AND is_active = 1;

-- documentation_planning: reviewed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["reviewed"]}' AS JSON))
WHERE procedure_type = 'documentation_planning' AND is_active = 1;

-- retrospective_documentation: completed → pending
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.transitions.restart', CAST('{"to": "pending", "from": ["completed"]}' AS JSON))
WHERE procedure_type = 'retrospective_documentation' AND is_active = 1;
