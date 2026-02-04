-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2B
-- Generated: 2026-02-04
--
-- Continuation of Phase 2 - Heritage, ICIP, IIIF, IPSAS, NAZ, NMMZ,
-- OAIS, Preservation, Privacy, Provenance, Research, Rights types
-- ============================================================================

-- ============================================================================
-- HERITAGE / GRAP TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('acquisition_method', 'Acquisition Method', 'purchase', 'Purchase', '#28a745', 10, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'donation', 'Donation', '#007bff', 20, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'bequest', 'Bequest', '#6f42c1', 30, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'transfer', 'Transfer', '#17a2b8', 40, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'found', 'Found', '#fd7e14', 50, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'exchange', 'Exchange', '#ffc107', 60, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'excavation', 'Excavation', '#e83e8c', 70, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'confiscation', 'Confiscation', '#dc3545', 80, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'unknown', 'Unknown', '#6c757d', 90, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'other', 'Other', '#868e96', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('measurement_basis', 'Measurement Basis', 'cost', 'Cost', '#007bff', 10, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'fair_value', 'Fair Value', '#28a745', 20, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'nominal', 'Nominal', '#ffc107', 30, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'not_practicable', 'Not Practicable', '#6c757d', 40, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'historical_cost', 'Historical Cost', '#6f42c1', 50, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'replacement_cost', 'Replacement Cost', '#17a2b8', 60, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'not_recognized', 'Not Recognized', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('valuation_method', 'Valuation Method', 'market', 'Market', '#28a745', 10, 1, NOW()),
('valuation_method', 'Valuation Method', 'cost', 'Cost', '#007bff', 20, 1, NOW()),
('valuation_method', 'Valuation Method', 'income', 'Income', '#6f42c1', 30, 1, NOW()),
('valuation_method', 'Valuation Method', 'expert', 'Expert', '#fd7e14', 40, 1, NOW()),
('valuation_method', 'Valuation Method', 'insurance', 'Insurance', '#dc3545', 50, 1, NOW()),
('valuation_method', 'Valuation Method', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('depreciation_policy', 'Depreciation Policy', 'not_depreciated', 'Not Depreciated', '#28a745', 10, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'straight_line', 'Straight Line', '#007bff', 20, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'reducing_balance', 'Reducing Balance', '#6f42c1', 30, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'units_of_production', 'Units of Production', '#fd7e14', 40, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'none', 'None', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('derecognition_reason', 'Derecognition Reason', 'disposal', 'Disposal', '#dc3545', 10, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'destruction', 'Destruction', '#343a40', 20, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'loss', 'Loss', '#fd7e14', 30, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'transfer', 'Transfer', '#007bff', 40, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'write_off', 'Write Off', '#6c757d', 50, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'theft', 'Theft', '#dc3545', 60, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'deaccession', 'Deaccession', '#ffc107', 70, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'sale', 'Sale', '#28a745', 80, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'donation', 'Donation', '#17a2b8', 90, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'other', 'Other', '#868e96', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_significance', 'Heritage Significance', 'exceptional', 'Exceptional', '#dc3545', 10, 1, NOW()),
('heritage_significance', 'Heritage Significance', 'high', 'High', '#fd7e14', 20, 1, NOW()),
('heritage_significance', 'Heritage Significance', 'medium', 'Medium', '#ffc107', 30, 1, NOW()),
('heritage_significance', 'Heritage Significance', 'low', 'Low', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('recognition_status', 'Recognition Status', 'recognised', 'Recognised', '#28a745', 10, 1, NOW()),
('recognition_status', 'Recognition Status', 'not_recognised', 'Not Recognised', '#dc3545', 20, 1, NOW()),
('recognition_status', 'Recognition Status', 'pending', 'Pending', '#ffc107', 30, 1, NOW()),
('recognition_status', 'Recognition Status', 'derecognised', 'Derecognised', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('revaluation_frequency', 'Revaluation Frequency', 'annual', 'Annual', '#dc3545', 10, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'triennial', 'Triennial', '#fd7e14', 20, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'quinquennial', 'Quinquennial', '#ffc107', 30, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'as_needed', 'As Needed', '#007bff', 40, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'not_applicable', 'Not Applicable', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_audit_action', 'Heritage Audit Action', 'create', 'Create', '#28a745', 10, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'update', 'Update', '#007bff', 20, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'delete', 'Delete', '#dc3545', 30, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'view', 'View', '#6c757d', 40, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'export', 'Export', '#6f42c1', 50, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'import', 'Import', '#17a2b8', 60, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'batch', 'Batch', '#fd7e14', 70, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'access', 'Access', '#ffc107', 80, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'system', 'System', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_batch_status', 'Heritage Batch Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'queued', 'Queued', '#17a2b8', 20, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'processing', 'Processing', '#007bff', 30, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'completed', 'Completed', '#28a745', 40, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'failed', 'Failed', '#dc3545', 50, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'cancelled', 'Cancelled', '#6c757d', 60, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'paused', 'Paused', '#fd7e14', 70, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'success', 'Success', '#28a745', 80, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'skipped', 'Skipped', '#868e96', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('compliance_check_type', 'Compliance Check Type', 'required_field', 'Required Field', '#dc3545', 10, 1, NOW()),
('compliance_check_type', 'Compliance Check Type', 'value_check', 'Value Check', '#ffc107', 20, 1, NOW()),
('compliance_check_type', 'Compliance Check Type', 'date_check', 'Date Check', '#17a2b8', 30, 1, NOW()),
('compliance_check_type', 'Compliance Check Type', 'custom', 'Custom', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('compliance_category', 'Compliance Category', 'recognition', 'Recognition', '#007bff', 10, 1, NOW()),
('compliance_category', 'Compliance Category', 'measurement', 'Measurement', '#28a745', 20, 1, NOW()),
('compliance_category', 'Compliance Category', 'disclosure', 'Disclosure', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('contribution_status', 'Contribution Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('contribution_status', 'Contribution Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('contribution_status', 'Contribution Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('contribution_status', 'Contribution Status', 'superseded', 'Superseded', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('trust_level', 'Trust Level', 'new', 'New', '#6c757d', 10, 1, NOW()),
('trust_level', 'Trust Level', 'contributor', 'Contributor', '#007bff', 20, 1, NOW()),
('trust_level', 'Trust Level', 'trusted', 'Trusted', '#28a745', 30, 1, NOW()),
('trust_level', 'Trust Level', 'expert', 'Expert', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('badge_criteria_type', 'Badge Criteria Type', 'contribution_count', 'Contribution Count', '#007bff', 10, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'approval_rate', 'Approval Rate', '#28a745', 20, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'points', 'Points', '#ffc107', 30, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'type_specific', 'Type Specific', '#6f42c1', 40, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'manual', 'Manual', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('curated_link_type', 'Curated Link Type', 'collection', 'Collection', '#007bff', 10, 1, NOW()),
('curated_link_type', 'Curated Link Type', 'search', 'Search', '#28a745', 20, 1, NOW()),
('curated_link_type', 'Curated Link Type', 'external', 'External', '#fd7e14', 30, 1, NOW()),
('curated_link_type', 'Curated Link Type', 'page', 'Page', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('entity_type', 'Entity Type', 'person', 'Person', '#007bff', 10, 1, NOW()),
('entity_type', 'Entity Type', 'organization', 'Organization', '#28a745', 20, 1, NOW()),
('entity_type', 'Entity Type', 'place', 'Place', '#fd7e14', 30, 1, NOW()),
('entity_type', 'Entity Type', 'date', 'Date', '#17a2b8', 40, 1, NOW()),
('entity_type', 'Entity Type', 'event', 'Event', '#6f42c1', 50, 1, NOW()),
('entity_type', 'Entity Type', 'work', 'Work', '#e83e8c', 60, 1, NOW()),
('entity_type', 'Entity Type', 'family', 'Family', '#ffc107', 70, 1, NOW()),
('entity_type', 'Entity Type', 'unknown', 'Unknown', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extraction_method', 'Extraction Method', 'taxonomy', 'Taxonomy', '#007bff', 10, 1, NOW()),
('extraction_method', 'Extraction Method', 'ner', 'NER', '#28a745', 20, 1, NOW()),
('extraction_method', 'Extraction Method', 'pattern', 'Pattern', '#6f42c1', 30, 1, NOW()),
('extraction_method', 'Extraction Method', 'manual', 'Manual', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('display_style', 'Display Style', 'grid', 'Grid', '#007bff', 10, 1, NOW()),
('display_style', 'Display Style', 'list', 'List', '#28a745', 20, 1, NOW()),
('display_style', 'Display Style', 'timeline', 'Timeline', '#6f42c1', 30, 1, NOW()),
('display_style', 'Display Style', 'map', 'Map', '#fd7e14', 40, 1, NOW()),
('display_style', 'Display Style', 'carousel', 'Carousel', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('source_type', 'Source Type', 'taxonomy', 'Taxonomy', '#007bff', 10, 1, NOW()),
('source_type', 'Source Type', 'authority', 'Authority', '#28a745', 20, 1, NOW()),
('source_type', 'Source Type', 'field', 'Field', '#6f42c1', 30, 1, NOW()),
('source_type', 'Source Type', 'facet', 'Facet', '#fd7e14', 40, 1, NOW()),
('source_type', 'Source Type', 'custom', 'Custom', '#6c757d', 50, 1, NOW()),
('source_type', 'Source Type', 'iiif', 'IIIF', '#17a2b8', 60, 1, NOW()),
('source_type', 'Source Type', 'archival', 'Archival', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('cta_style', 'CTA Style', 'primary', 'Primary', '#007bff', 10, 1, NOW()),
('cta_style', 'CTA Style', 'secondary', 'Secondary', '#6c757d', 20, 1, NOW()),
('cta_style', 'CTA Style', 'outline', 'Outline', '#ffc107', 30, 1, NOW()),
('cta_style', 'CTA Style', 'light', 'Light', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hero_media_type', 'Hero Media Type', 'image', 'Image', '#28a745', 10, 1, NOW()),
('hero_media_type', 'Hero Media Type', 'video', 'Video', '#6f42c1', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('overlay_type', 'Overlay Type', 'none', 'None', '#6c757d', 10, 1, NOW()),
('overlay_type', 'Overlay Type', 'gradient', 'Gradient', '#007bff', 20, 1, NOW()),
('overlay_type', 'Overlay Type', 'solid', 'Solid', '#343a40', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('text_position', 'Text Position', 'left', 'Left', '#007bff', 10, 1, NOW()),
('text_position', 'Text Position', 'center', 'Center', '#28a745', 20, 1, NOW()),
('text_position', 'Text Position', 'right', 'Right', '#6f42c1', 30, 1, NOW()),
('text_position', 'Text Position', 'bottom-left', 'Bottom Left', '#fd7e14', 40, 1, NOW()),
('text_position', 'Text Position', 'bottom-right', 'Bottom Right', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('journal_type', 'Journal Type', 'recognition', 'Recognition', '#28a745', 10, 1, NOW()),
('journal_type', 'Journal Type', 'revaluation', 'Revaluation', '#007bff', 20, 1, NOW()),
('journal_type', 'Journal Type', 'depreciation', 'Depreciation', '#fd7e14', 30, 1, NOW()),
('journal_type', 'Journal Type', 'impairment', 'Impairment', '#dc3545', 40, 1, NOW()),
('journal_type', 'Journal Type', 'impairment_reversal', 'Impairment Reversal', '#28a745', 50, 1, NOW()),
('journal_type', 'Journal Type', 'derecognition', 'Derecognition', '#343a40', 60, 1, NOW()),
('journal_type', 'Journal Type', 'adjustment', 'Adjustment', '#ffc107', 70, 1, NOW()),
('journal_type', 'Journal Type', 'transfer', 'Transfer', '#17a2b8', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hero_effect', 'Hero Effect', 'kenburns', 'Ken Burns', '#007bff', 10, 1, NOW()),
('hero_effect', 'Hero Effect', 'fade', 'Fade', '#28a745', 20, 1, NOW()),
('hero_effect', 'Hero Effect', 'none', 'None', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('term_relationship', 'Term Relationship', 'synonym', 'Synonym', '#28a745', 10, 1, NOW()),
('term_relationship', 'Term Relationship', 'broader', 'Broader', '#007bff', 20, 1, NOW()),
('term_relationship', 'Term Relationship', 'narrower', 'Narrower', '#6f42c1', 30, 1, NOW()),
('term_relationship', 'Term Relationship', 'related', 'Related', '#fd7e14', 40, 1, NOW()),
('term_relationship', 'Term Relationship', 'spelling', 'Spelling', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('learned_term_source', 'Learned Term Source', 'user_behavior', 'User Behavior', '#007bff', 10, 1, NOW()),
('learned_term_source', 'Learned Term Source', 'admin', 'Admin', '#dc3545', 20, 1, NOW()),
('learned_term_source', 'Learned Term Source', 'taxonomy', 'Taxonomy', '#28a745', 30, 1, NOW()),
('learned_term_source', 'Learned Term Source', 'external', 'External', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('movement_type', 'Movement Type', 'loan_out', 'Loan Out', '#fd7e14', 10, 1, NOW()),
('movement_type', 'Movement Type', 'loan_return', 'Loan Return', '#28a745', 20, 1, NOW()),
('movement_type', 'Movement Type', 'transfer', 'Transfer', '#007bff', 30, 1, NOW()),
('movement_type', 'Movement Type', 'exhibition', 'Exhibition', '#6f42c1', 40, 1, NOW()),
('movement_type', 'Movement Type', 'conservation', 'Conservation', '#17a2b8', 50, 1, NOW()),
('movement_type', 'Movement Type', 'storage_change', 'Storage Change', '#ffc107', 60, 1, NOW()),
('movement_type', 'Movement Type', 'other', 'Other', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('popia_flag_type', 'POPIA Flag Type', 'personal_info', 'Personal Info', '#ffc107', 10, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'sensitive', 'Sensitive', '#dc3545', 20, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'children', 'Children', '#e83e8c', 30, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'health', 'Health', '#fd7e14', 40, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'biometric', 'Biometric', '#6f42c1', 50, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'criminal', 'Criminal', '#343a40', 60, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'financial', 'Financial', '#28a745', 70, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'political', 'Political', '#007bff', 80, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'religious', 'Religious', '#17a2b8', 90, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'sexual', 'Sexual', '#e83e8c', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('detected_by', 'Detected By', 'automatic', 'Automatic', '#007bff', 10, 1, NOW()),
('detected_by', 'Detected By', 'manual', 'Manual', '#28a745', 20, 1, NOW()),
('detected_by', 'Detected By', 'review', 'Review', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('suggestion_type', 'Suggestion Type', 'query', 'Query', '#007bff', 10, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'title', 'Title', '#28a745', 20, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'subject', 'Subject', '#6f42c1', 30, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'creator', 'Creator', '#fd7e14', 40, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'place', 'Place', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tenant_status', 'Tenant Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('tenant_status', 'Tenant Status', 'suspended', 'Suspended', '#dc3545', 20, 1, NOW()),
('tenant_status', 'Tenant Status', 'trial', 'Trial', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tenant_role', 'Tenant Role', 'owner', 'Owner', '#dc3545', 10, 1, NOW()),
('tenant_role', 'Tenant Role', 'super_user', 'Super User', '#fd7e14', 20, 1, NOW()),
('tenant_role', 'Tenant Role', 'editor', 'Editor', '#ffc107', 30, 1, NOW()),
('tenant_role', 'Tenant Role', 'contributor', 'Contributor', '#28a745', 40, 1, NOW()),
('tenant_role', 'Tenant Role', 'viewer', 'Viewer', '#6c757d', 50, 1, NOW());

-- ============================================================================
-- ICIP (Indigenous Cultural Intellectual Property) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('icip_restriction_type', 'ICIP Restriction Type', 'community_permission_required', 'Community Permission Required', '#dc3545', 10, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'gender_restricted_male', 'Gender Restricted (Male)', '#007bff', 20, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'gender_restricted_female', 'Gender Restricted (Female)', '#e83e8c', 30, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'initiated_only', 'Initiated Only', '#6f42c1', 40, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'seasonal', 'Seasonal', '#28a745', 50, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'mourning_period', 'Mourning Period', '#343a40', 60, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'repatriation_pending', 'Repatriation Pending', '#fd7e14', 70, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'under_consultation', 'Under Consultation', '#ffc107', 80, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'elder_approval_required', 'Elder Approval Required', '#17a2b8', 90, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'custom', 'Custom', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('australian_state', 'Australian State/Territory', 'NSW', 'New South Wales', '#007bff', 10, 1, NOW()),
('australian_state', 'Australian State/Territory', 'VIC', 'Victoria', '#28a745', 20, 1, NOW()),
('australian_state', 'Australian State/Territory', 'QLD', 'Queensland', '#6f42c1', 30, 1, NOW()),
('australian_state', 'Australian State/Territory', 'WA', 'Western Australia', '#fd7e14', 40, 1, NOW()),
('australian_state', 'Australian State/Territory', 'SA', 'South Australia', '#dc3545', 50, 1, NOW()),
('australian_state', 'Australian State/Territory', 'TAS', 'Tasmania', '#17a2b8', 60, 1, NOW()),
('australian_state', 'Australian State/Territory', 'NT', 'Northern Territory', '#ffc107', 70, 1, NOW()),
('australian_state', 'Australian State/Territory', 'ACT', 'Australian Capital Territory', '#e83e8c', 80, 1, NOW()),
('australian_state', 'Australian State/Territory', 'External', 'External Territory', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_status', 'Consent Status', 'not_required', 'Not Required', '#6c757d', 10, 1, NOW()),
('consent_status', 'Consent Status', 'pending_consultation', 'Pending Consultation', '#ffc107', 20, 1, NOW()),
('consent_status', 'Consent Status', 'consultation_in_progress', 'Consultation In Progress', '#17a2b8', 30, 1, NOW()),
('consent_status', 'Consent Status', 'conditional_consent', 'Conditional Consent', '#fd7e14', 40, 1, NOW()),
('consent_status', 'Consent Status', 'full_consent', 'Full Consent', '#28a745', 50, 1, NOW()),
('consent_status', 'Consent Status', 'restricted_consent', 'Restricted Consent', '#ffc107', 60, 1, NOW()),
('consent_status', 'Consent Status', 'denied', 'Denied', '#dc3545', 70, 1, NOW()),
('consent_status', 'Consent Status', 'unknown', 'Unknown', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consultation_method', 'Consultation Method', 'in_person', 'In Person', '#28a745', 10, 1, NOW()),
('consultation_method', 'Consultation Method', 'phone', 'Phone', '#007bff', 20, 1, NOW()),
('consultation_method', 'Consultation Method', 'video', 'Video', '#6f42c1', 30, 1, NOW()),
('consultation_method', 'Consultation Method', 'email', 'Email', '#17a2b8', 40, 1, NOW()),
('consultation_method', 'Consultation Method', 'letter', 'Letter', '#fd7e14', 50, 1, NOW()),
('consultation_method', 'Consultation Method', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consultation_type', 'Consultation Type', 'initial_contact', 'Initial Contact', '#007bff', 10, 1, NOW()),
('consultation_type', 'Consultation Type', 'consent_request', 'Consent Request', '#28a745', 20, 1, NOW()),
('consultation_type', 'Consultation Type', 'access_request', 'Access Request', '#6f42c1', 30, 1, NOW()),
('consultation_type', 'Consultation Type', 'repatriation', 'Repatriation', '#dc3545', 40, 1, NOW()),
('consultation_type', 'Consultation Type', 'digitisation', 'Digitisation', '#17a2b8', 50, 1, NOW()),
('consultation_type', 'Consultation Type', 'exhibition', 'Exhibition', '#fd7e14', 60, 1, NOW()),
('consultation_type', 'Consultation Type', 'publication', 'Publication', '#ffc107', 70, 1, NOW()),
('consultation_type', 'Consultation Type', 'research', 'Research', '#e83e8c', 80, 1, NOW()),
('consultation_type', 'Consultation Type', 'general', 'General', '#6c757d', 90, 1, NOW()),
('consultation_type', 'Consultation Type', 'follow_up', 'Follow Up', '#20c997', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consultation_status', 'Consultation Status', 'scheduled', 'Scheduled', '#ffc107', 10, 1, NOW()),
('consultation_status', 'Consultation Status', 'completed', 'Completed', '#28a745', 20, 1, NOW()),
('consultation_status', 'Consultation Status', 'cancelled', 'Cancelled', '#dc3545', 30, 1, NOW()),
('consultation_status', 'Consultation Status', 'follow_up_required', 'Follow Up Required', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tk_label_applied_by', 'TK Label Applied By', 'community', 'Community', '#28a745', 10, 1, NOW()),
('tk_label_applied_by', 'TK Label Applied By', 'institution', 'Institution', '#007bff', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tk_label_category', 'TK Label Category', 'TK', 'Traditional Knowledge', '#dc3545', 10, 1, NOW()),
('tk_label_category', 'TK Label Category', 'BC', 'Biocultural', '#28a745', 20, 1, NOW());

-- ============================================================================
-- IIIF TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('iiif_motivation', 'IIIF Motivation', 'commenting', 'Commenting', '#007bff', 10, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'tagging', 'Tagging', '#28a745', 20, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'describing', 'Describing', '#6f42c1', 30, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'linking', 'Linking', '#fd7e14', 40, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'transcribing', 'Transcribing', '#17a2b8', 50, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'identifying', 'Identifying', '#ffc107', 60, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'supplementing', 'Supplementing', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('iiif_viewing_hint', 'IIIF Viewing Hint', 'individuals', 'Individuals', '#007bff', 10, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'paged', 'Paged', '#28a745', 20, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'continuous', 'Continuous', '#6f42c1', 30, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'multi-part', 'Multi-part', '#fd7e14', 40, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'top', 'Top', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('iiif_item_type', 'IIIF Item Type', 'manifest', 'Manifest', '#007bff', 10, 1, NOW()),
('iiif_item_type', 'IIIF Item Type', 'collection', 'Collection', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ocr_block_type', 'OCR Block Type', 'word', 'Word', '#007bff', 10, 1, NOW()),
('ocr_block_type', 'OCR Block Type', 'line', 'Line', '#28a745', 20, 1, NOW()),
('ocr_block_type', 'OCR Block Type', 'paragraph', 'Paragraph', '#6f42c1', 30, 1, NOW()),
('ocr_block_type', 'OCR Block Type', 'region', 'Region', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ocr_format', 'OCR Format', 'plain', 'Plain Text', '#007bff', 10, 1, NOW()),
('ocr_format', 'OCR Format', 'alto', 'ALTO XML', '#28a745', 20, 1, NOW()),
('ocr_format', 'OCR Format', 'hocr', 'hOCR', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- PHYSICAL LOCATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('physical_access_status', 'Physical Access Status', 'available', 'Available', '#28a745', 10, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'in_use', 'In Use', '#007bff', 20, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'restricted', 'Restricted', '#dc3545', 30, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'offsite', 'Offsite', '#fd7e14', 40, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'missing', 'Missing', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('physical_object_status', 'Physical Object Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('physical_object_status', 'Physical Object Status', 'full', 'Full', '#ffc107', 20, 1, NOW()),
('physical_object_status', 'Physical Object Status', 'maintenance', 'Maintenance', '#17a2b8', 30, 1, NOW()),
('physical_object_status', 'Physical Object Status', 'decommissioned', 'Decommissioned', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- IPSAS TYPES (Heritage Asset Accounting)
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_asset_type', 'IPSAS Asset Type', 'heritage', 'Heritage', '#6f42c1', 10, 1, NOW()),
('ipsas_asset_type', 'IPSAS Asset Type', 'operational', 'Operational', '#007bff', 20, 1, NOW()),
('ipsas_asset_type', 'IPSAS Asset Type', 'mixed', 'Mixed', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_calculation_method', 'IPSAS Calculation Method', 'straight_line', 'Straight Line', '#007bff', 10, 1, NOW()),
('ipsas_calculation_method', 'IPSAS Calculation Method', 'reducing_balance', 'Reducing Balance', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_fy_status', 'IPSAS FY Status', 'open', 'Open', '#28a745', 10, 1, NOW()),
('ipsas_fy_status', 'IPSAS FY Status', 'closed', 'Closed', '#dc3545', 20, 1, NOW()),
('ipsas_fy_status', 'IPSAS FY Status', 'audited', 'Audited', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_asset_status', 'Heritage Asset Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'on_loan', 'On Loan', '#007bff', 20, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'in_storage', 'In Storage', '#6c757d', 30, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'under_conservation', 'Under Conservation', '#17a2b8', 40, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'disposed', 'Disposed', '#dc3545', 50, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'lost', 'Lost', '#343a40', 60, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'destroyed', 'Destroyed', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_insurance_status', 'IPSAS Insurance Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('ipsas_insurance_status', 'IPSAS Insurance Status', 'expired', 'Expired', '#dc3545', 20, 1, NOW()),
('ipsas_insurance_status', 'IPSAS Insurance Status', 'cancelled', 'Cancelled', '#6c757d', 30, 1, NOW()),
('ipsas_insurance_status', 'IPSAS Insurance Status', 'pending_renewal', 'Pending Renewal', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_valuation_type', 'IPSAS Valuation Type', 'initial', 'Initial', '#28a745', 10, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'revaluation', 'Revaluation', '#007bff', 20, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'impairment', 'Impairment', '#dc3545', 30, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'reversal', 'Reversal', '#ffc107', 40, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'disposal', 'Disposal', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('valuer_type', 'Valuer Type', 'internal', 'Internal', '#007bff', 10, 1, NOW()),
('valuer_type', 'Valuer Type', 'external', 'External', '#28a745', 20, 1, NOW()),
('valuer_type', 'Valuer Type', 'government', 'Government', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- LOAN TYPES (General)
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_purpose', 'Loan Purpose', 'exhibition', 'Exhibition', '#6f42c1', 10, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'research', 'Research', '#007bff', 20, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'conservation', 'Conservation', '#17a2b8', 30, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'photography', 'Photography', '#fd7e14', 40, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'education', 'Education', '#ffc107', 50, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'filming', 'Filming', '#e83e8c', 60, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'long_term', 'Long Term', '#28a745', 70, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'other', 'Other', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_document_type', 'Loan Document Type', 'agreement', 'Agreement', '#28a745', 10, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'facilities_report', 'Facilities Report', '#007bff', 20, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'condition_report', 'Condition Report', '#17a2b8', 30, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'insurance_certificate', 'Insurance Certificate', '#dc3545', 40, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'receipt', 'Receipt', '#ffc107', 50, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'correspondence', 'Correspondence', '#6f42c1', 60, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'photograph', 'Photograph', '#fd7e14', 70, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'other', 'Other', '#6c757d', 80, 1, NOW());

-- ============================================================================
-- MEDIA TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('derivative_type', 'Derivative Type', 'thumbnail', 'Thumbnail', '#007bff', 10, 1, NOW()),
('derivative_type', 'Derivative Type', 'poster', 'Poster', '#28a745', 20, 1, NOW()),
('derivative_type', 'Derivative Type', 'preview', 'Preview', '#6f42c1', 30, 1, NOW()),
('derivative_type', 'Derivative Type', 'waveform', 'Waveform', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('media_type', 'Media Type', 'audio', 'Audio', '#fd7e14', 10, 1, NOW()),
('media_type', 'Media Type', 'video', 'Video', '#6f42c1', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('media_task_type', 'Media Task Type', 'metadata_extraction', 'Metadata Extraction', '#007bff', 10, 1, NOW()),
('media_task_type', 'Media Task Type', 'transcription', 'Transcription', '#28a745', 20, 1, NOW()),
('media_task_type', 'Media Task Type', 'waveform', 'Waveform', '#fd7e14', 30, 1, NOW()),
('media_task_type', 'Media Task Type', 'thumbnail', 'Thumbnail', '#6f42c1', 40, 1, NOW());

-- ============================================================================
-- METADATA TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('export_status', 'Export Status', 'success', 'Success', '#28a745', 10, 1, NOW()),
('export_status', 'Export Status', 'failed', 'Failed', '#dc3545', 20, 1, NOW()),
('export_status', 'Export Status', 'partial', 'Partial', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extraction_operation', 'Extraction Operation', 'extract', 'Extract', '#007bff', 10, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'face_detect', 'Face Detect', '#28a745', 20, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'face_match', 'Face Match', '#6f42c1', 30, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'index_face', 'Index Face', '#fd7e14', 40, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'bulk', 'Bulk', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('triggered_by', 'Triggered By', 'upload', 'Upload', '#28a745', 10, 1, NOW()),
('triggered_by', 'Triggered By', 'job', 'Job', '#007bff', 20, 1, NOW()),
('triggered_by', 'Triggered By', 'manual', 'Manual', '#6f42c1', 30, 1, NOW()),
('triggered_by', 'Triggered By', 'api', 'API', '#fd7e14', 40, 1, NOW()),
('triggered_by', 'Triggered By', 'scheduler', 'Scheduler', '#17a2b8', 50, 1, NOW()),
('triggered_by', 'Triggered By', 'user', 'User', '#ffc107', 60, 1, NOW()),
('triggered_by', 'Triggered By', 'system', 'System', '#6c757d', 70, 1, NOW()),
('triggered_by', 'Triggered By', 'cron', 'Cron', '#e83e8c', 80, 1, NOW()),
('triggered_by', 'Triggered By', 'cli', 'CLI', '#343a40', 90, 1, NOW());

-- ============================================================================
-- Show Phase 2B statistics
-- ============================================================================

SELECT 'Phase 2B Migration Complete' as status;
