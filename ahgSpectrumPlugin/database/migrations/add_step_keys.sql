-- ============================================================
-- Migration: give every workflow step a key
--
-- The step checklist and the linear-step gate both index $step['key']
-- (workflowSuccess.php:186,205,213 and actions.class.php:382-386), and only
-- acquisition and cataloguing ever had one. For the other 19 procedures the
-- checkboxes rendered with empty values, so spectrum_workflow_step_state could
-- never persist and the linear gate could never be satisfied.
--
-- Keys are derived from the step name, lowercased and underscored, so they are
-- stable and readable. Existing keys are left alone.
--
-- Idempotent: JSON_SET on a key that already holds the same value is a no-op.
-- ============================================================

-- audit
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'schedule', '$.steps[1].key', 'in_progress', '$.steps[2].key', 'findings', '$.steps[3].key', 'report', '$.steps[4].key', 'close')
WHERE procedure_type = 'audit' AND is_active = 1;

-- condition_checking
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'schedule', '$.steps[1].key', 'examine', '$.steps[2].key', 'document', '$.steps[3].key', 'report', '$.steps[4].key', 'review')
WHERE procedure_type = 'condition_checking' AND is_active = 1;

-- conservation
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'assessment', '$.steps[1].key', 'treatment_proposal', '$.steps[2].key', 'approval', '$.steps[3].key', 'treatment', '$.steps[4].key', 'complete')
WHERE procedure_type = 'conservation' AND is_active = 1;

-- deaccession
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'proposal', '$.steps[1].key', 'review', '$.steps[2].key', 'board_approval', '$.steps[3].key', 'process', '$.steps[4].key', 'complete')
WHERE procedure_type = 'deaccession' AND is_active = 1;

-- disposal
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'approval', '$.steps[1].key', 'select_method', '$.steps[2].key', 'disposal', '$.steps[3].key', 'documentation')
WHERE procedure_type = 'disposal' AND is_active = 1;

-- documentation_planning
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'plan', '$.steps[1].key', 'prioritize', '$.steps[2].key', 'in_progress', '$.steps[3].key', 'complete', '$.steps[4].key', 'review')
WHERE procedure_type = 'documentation_planning' AND is_active = 1;

-- insurance
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'valuation', '$.steps[1].key', 'get_quote', '$.steps[2].key', 'approve', '$.steps[3].key', 'coverage_active')
WHERE procedure_type = 'insurance' AND is_active = 1;

-- inventory_control
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'schedule_check', '$.steps[1].key', 'start_inventory', '$.steps[2].key', 'count_items', '$.steps[3].key', 'reconcile', '$.steps[4].key', 'complete')
WHERE procedure_type = 'inventory_control' AND is_active = 1;

-- loans_in
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'request', '$.steps[1].key', 'facility_report', '$.steps[2].key', 'insurance', '$.steps[3].key', 'condition_in', '$.steps[4].key', 'installation', '$.steps[5].key', 'condition_out', '$.steps[6].key', 'return')
WHERE procedure_type = 'loans_in' AND is_active = 1;

-- loans_out
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'request_received', '$.steps[1].key', 'review', '$.steps[2].key', 'facility_assessment', '$.steps[3].key', 'insurance', '$.steps[4].key', 'condition_report', '$.steps[5].key', 'packing', '$.steps[6].key', 'dispatch', '$.steps[7].key', 'return', '$.steps[8].key', 'final_condition')
WHERE procedure_type = 'loans_out' AND is_active = 1;

-- location_movement
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'request_move', '$.steps[1].key', 'approve', '$.steps[2].key', 'in_transit', '$.steps[3].key', 'arrival', '$.steps[4].key', 'verify_location')
WHERE procedure_type = 'location_movement' AND is_active = 1;

-- loss_damage
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'report_incident', '$.steps[1].key', 'investigation', '$.steps[2].key', 'damage_assessment', '$.steps[3].key', 'resolution')
WHERE procedure_type = 'loss_damage' AND is_active = 1;

-- object_entry
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'receive_object', '$.steps[1].key', 'document_receipt', '$.steps[2].key', 'initial_assessment', '$.steps[3].key', 'process_entry', '$.steps[4].key', 'complete')
WHERE procedure_type = 'object_entry' AND is_active = 1;

-- object_exit
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'exit_request', '$.steps[1].key', 'approval', '$.steps[2].key', 'preparation', '$.steps[3].key', 'dispatch', '$.steps[4].key', 'confirm_exit')
WHERE procedure_type = 'object_exit' AND is_active = 1;

-- reproduction
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'request', '$.steps[1].key', 'rights_check', '$.steps[2].key', 'approval', '$.steps[3].key', 'production', '$.steps[4].key', 'delivery')
WHERE procedure_type = 'reproduction' AND is_active = 1;

-- retrospective_documentation
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'identify_gaps', '$.steps[1].key', 'research', '$.steps[2].key', 'document', '$.steps[3].key', 'verify', '$.steps[4].key', 'complete')
WHERE procedure_type = 'retrospective_documentation' AND is_active = 1;

-- rights_management
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'research', '$.steps[1].key', 'document', '$.steps[2].key', 'clear_rights', '$.steps[3].key', 'monitor')
WHERE procedure_type = 'rights_management' AND is_active = 1;

-- risk_management
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'identify_risks', '$.steps[1].key', 'assess', '$.steps[2].key', 'mitigate', '$.steps[3].key', 'monitor')
WHERE procedure_type = 'risk_management' AND is_active = 1;

-- valuation
UPDATE spectrum_workflow_config
SET config_json = JSON_SET(config_json, '$.steps[0].key', 'request_valuation', '$.steps[1].key', 'appraisal', '$.steps[2].key', 'review', '$.steps[3].key', 'approve')
WHERE procedure_type = 'valuation' AND is_active = 1;
