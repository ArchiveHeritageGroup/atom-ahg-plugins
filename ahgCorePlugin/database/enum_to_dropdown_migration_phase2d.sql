-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2D (Final)
-- Generated: 2026-02-04
--
-- Final part: Privacy, Provenance, Research, RIC, Rights types
-- ============================================================================

-- ============================================================================
-- PRIVACY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_notification_method', 'Breach Notification Method', 'email', 'Email', '#007bff', 10, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'letter', 'Letter', '#28a745', 20, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'portal', 'Portal', '#6f42c1', 30, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'phone', 'Phone', '#fd7e14', 40, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'in_person', 'In Person', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_notification_type', 'Breach Notification Type', 'regulator', 'Regulator', '#dc3545', 10, 1, NOW()),
('breach_notification_type', 'Breach Notification Type', 'data_subject', 'Data Subject', '#007bff', 20, 1, NOW()),
('breach_notification_type', 'Breach Notification Type', 'internal', 'Internal', '#6c757d', 30, 1, NOW()),
('breach_notification_type', 'Breach Notification Type', 'third_party', 'Third Party', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('risk_to_rights', 'Risk to Rights', 'unlikely', 'Unlikely', '#28a745', 10, 1, NOW()),
('risk_to_rights', 'Risk to Rights', 'possible', 'Possible', '#ffc107', 20, 1, NOW()),
('risk_to_rights', 'Risk to Rights', 'likely', 'Likely', '#fd7e14', 30, 1, NOW()),
('risk_to_rights', 'Risk to Rights', 'high', 'High', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('complaint_status', 'Complaint Status', 'received', 'Received', '#6c757d', 10, 1, NOW()),
('complaint_status', 'Complaint Status', 'investigating', 'Investigating', '#17a2b8', 20, 1, NOW()),
('complaint_status', 'Complaint Status', 'resolved', 'Resolved', '#28a745', 30, 1, NOW()),
('complaint_status', 'Complaint Status', 'escalated', 'Escalated', '#dc3545', 40, 1, NOW()),
('complaint_status', 'Complaint Status', 'closed', 'Closed', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_type', 'Consent Type', 'processing', 'Processing', '#007bff', 10, 1, NOW()),
('consent_type', 'Consent Type', 'marketing', 'Marketing', '#28a745', 20, 1, NOW()),
('consent_type', 'Consent Type', 'profiling', 'Profiling', '#6f42c1', 30, 1, NOW()),
('consent_type', 'Consent Type', 'third_party', 'Third Party', '#fd7e14', 40, 1, NOW()),
('consent_type', 'Consent Type', 'cookies', 'Cookies', '#17a2b8', 50, 1, NOW()),
('consent_type', 'Consent Type', 'research', 'Research', '#ffc107', 60, 1, NOW()),
('consent_type', 'Consent Type', 'special_category', 'Special Category', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_log_action', 'Consent Log Action', 'granted', 'Granted', '#28a745', 10, 1, NOW()),
('consent_log_action', 'Consent Log Action', 'withdrawn', 'Withdrawn', '#dc3545', 20, 1, NOW()),
('consent_log_action', 'Consent Log Action', 'expired', 'Expired', '#6c757d', 30, 1, NOW()),
('consent_log_action', 'Consent Log Action', 'renewed', 'Renewed', '#007bff', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('data_type', 'Data Type', 'personal', 'Personal', '#ffc107', 10, 1, NOW()),
('data_type', 'Data Type', 'special_category', 'Special Category', '#dc3545', 20, 1, NOW()),
('data_type', 'Data Type', 'children', 'Children', '#e83e8c', 30, 1, NOW()),
('data_type', 'Data Type', 'criminal', 'Criminal', '#343a40', 40, 1, NOW()),
('data_type', 'Data Type', 'financial', 'Financial', '#28a745', 50, 1, NOW()),
('data_type', 'Data Type', 'health', 'Health', '#fd7e14', 60, 1, NOW()),
('data_type', 'Data Type', 'biometric', 'Biometric', '#6f42c1', 70, 1, NOW()),
('data_type', 'Data Type', 'genetic', 'Genetic', '#17a2b8', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('storage_format', 'Storage Format', 'electronic', 'Electronic', '#007bff', 10, 1, NOW()),
('storage_format', 'Storage Format', 'paper', 'Paper', '#28a745', 20, 1, NOW()),
('storage_format', 'Storage Format', 'both', 'Both', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dsar_outcome', 'DSAR Outcome', 'granted', 'Granted', '#28a745', 10, 1, NOW()),
('dsar_outcome', 'DSAR Outcome', 'partially_granted', 'Partially Granted', '#ffc107', 20, 1, NOW()),
('dsar_outcome', 'DSAR Outcome', 'refused', 'Refused', '#dc3545', 30, 1, NOW()),
('dsar_outcome', 'DSAR Outcome', 'not_applicable', 'Not Applicable', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('paia_access_form', 'PAIA Access Form', 'inspect', 'Inspect', '#007bff', 10, 1, NOW()),
('paia_access_form', 'PAIA Access Form', 'copy', 'Copy', '#28a745', 20, 1, NOW()),
('paia_access_form', 'PAIA Access Form', 'both', 'Both', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('paia_section', 'PAIA Section', 'section_18', 'Section 18', '#007bff', 10, 1, NOW()),
('paia_section', 'PAIA Section', 'section_22', 'Section 22', '#28a745', 20, 1, NOW()),
('paia_section', 'PAIA Section', 'section_23', 'Section 23', '#6f42c1', 30, 1, NOW()),
('paia_section', 'PAIA Section', 'section_50', 'Section 50', '#fd7e14', 40, 1, NOW()),
('paia_section', 'PAIA Section', 'section_77', 'Section 77', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('paia_status', 'PAIA Status', 'received', 'Received', '#6c757d', 10, 1, NOW()),
('paia_status', 'PAIA Status', 'processing', 'Processing', '#17a2b8', 20, 1, NOW()),
('paia_status', 'PAIA Status', 'granted', 'Granted', '#28a745', 30, 1, NOW()),
('paia_status', 'PAIA Status', 'partially_granted', 'Partially Granted', '#ffc107', 40, 1, NOW()),
('paia_status', 'PAIA Status', 'refused', 'Refused', '#dc3545', 50, 1, NOW()),
('paia_status', 'PAIA Status', 'transferred', 'Transferred', '#6f42c1', 60, 1, NOW()),
('paia_status', 'PAIA Status', 'appealed', 'Appealed', '#fd7e14', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_file_type', 'Redaction File Type', 'pdf', 'PDF', '#dc3545', 10, 1, NOW()),
('redaction_file_type', 'Redaction File Type', 'image', 'Image', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('retention_disposal_action', 'Retention Disposal Action', 'destroy', 'Destroy', '#dc3545', 10, 1, NOW()),
('retention_disposal_action', 'Retention Disposal Action', 'archive', 'Archive', '#28a745', 20, 1, NOW()),
('retention_disposal_action', 'Retention Disposal Action', 'anonymize', 'Anonymize', '#6f42c1', 30, 1, NOW()),
('retention_disposal_action', 'Retention Disposal Action', 'review', 'Review', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_region_type', 'Redaction Region Type', 'rectangle', 'Rectangle', '#007bff', 10, 1, NOW()),
('redaction_region_type', 'Redaction Region Type', 'polygon', 'Polygon', '#28a745', 20, 1, NOW()),
('redaction_region_type', 'Redaction Region Type', 'freehand', 'Freehand', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_source', 'Redaction Source', 'manual', 'Manual', '#28a745', 10, 1, NOW()),
('redaction_source', 'Redaction Source', 'auto_ner', 'Auto NER', '#007bff', 20, 1, NOW()),
('redaction_source', 'Redaction Source', 'auto_pii', 'Auto PII', '#6f42c1', 30, 1, NOW()),
('redaction_source', 'Redaction Source', 'imported', 'Imported', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_status', 'Redaction Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('redaction_status', 'Redaction Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('redaction_status', 'Redaction Status', 'applied', 'Applied', '#007bff', 30, 1, NOW()),
('redaction_status', 'Redaction Status', 'rejected', 'Rejected', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- PROVENANCE TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_agent_type', 'Provenance Agent Type', 'person', 'Person', '#007bff', 10, 1, NOW()),
('provenance_agent_type', 'Provenance Agent Type', 'organization', 'Organization', '#28a745', 20, 1, NOW()),
('provenance_agent_type', 'Provenance Agent Type', 'family', 'Family', '#6f42c1', 30, 1, NOW()),
('provenance_agent_type', 'Provenance Agent Type', 'unknown', 'Unknown', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_document_type', 'Provenance Document Type', 'deed_of_gift', 'Deed of Gift', '#28a745', 10, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'bill_of_sale', 'Bill of Sale', '#007bff', 20, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'invoice', 'Invoice', '#17a2b8', 30, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'receipt', 'Receipt', '#ffc107', 40, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'auction_catalog', 'Auction Catalog', '#6f42c1', 50, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'exhibition_catalog', 'Exhibition Catalog', '#e83e8c', 60, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'inventory', 'Inventory', '#20c997', 70, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'insurance_record', 'Insurance Record', '#dc3545', 80, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'photograph', 'Photograph', '#fd7e14', 90, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'correspondence', 'Correspondence', '#6c757d', 100, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'certificate', 'Certificate', '#28a745', 110, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'customs_document', 'Customs Document', '#007bff', 120, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'export_license', 'Export License', '#17a2b8', 130, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'import_permit', 'Import Permit', '#6f42c1', 140, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'appraisal', 'Appraisal', '#ffc107', 150, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'condition_report', 'Condition Report', '#fd7e14', 160, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'newspaper_clipping', 'Newspaper Clipping', '#868e96', 170, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'publication', 'Publication', '#343a40', 180, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'oral_history', 'Oral History', '#e83e8c', 190, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'affidavit', 'Affidavit', '#dc3545', 200, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'legal_document', 'Legal Document', '#343a40', 210, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'other', 'Other', '#6c757d', 220, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_certainty', 'Provenance Certainty', 'certain', 'Certain', '#28a745', 10, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'probable', 'Probable', '#007bff', 20, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'possible', 'Possible', '#ffc107', 30, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'uncertain', 'Uncertain', '#fd7e14', 40, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'unknown', 'Unknown', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('date_qualifier', 'Date Qualifier', 'circa', 'Circa', '#6c757d', 10, 1, NOW()),
('date_qualifier', 'Date Qualifier', 'before', 'Before', '#007bff', 20, 1, NOW()),
('date_qualifier', 'Date Qualifier', 'after', 'After', '#28a745', 30, 1, NOW()),
('date_qualifier', 'Date Qualifier', 'by', 'By', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_owner_type', 'Provenance Owner Type', 'person', 'Person', '#007bff', 10, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'family', 'Family', '#28a745', 20, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'dealer', 'Dealer', '#fd7e14', 30, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'auction_house', 'Auction House', '#ffc107', 40, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'museum', 'Museum', '#6f42c1', 50, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'corporate', 'Corporate', '#17a2b8', 60, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'government', 'Government', '#dc3545', 70, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'religious', 'Religious', '#e83e8c', 80, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'artist', 'Artist', '#20c997', 90, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'unknown', 'Unknown', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_transfer_type', 'Provenance Transfer Type', 'sale', 'Sale', '#28a745', 10, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'auction', 'Auction', '#ffc107', 20, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'gift', 'Gift', '#007bff', 30, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'bequest', 'Bequest', '#6f42c1', 40, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'inheritance', 'Inheritance', '#17a2b8', 50, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'commission', 'Commission', '#fd7e14', 60, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'exchange', 'Exchange', '#e83e8c', 70, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'seizure', 'Seizure', '#dc3545', 80, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'restitution', 'Restitution', '#343a40', 90, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'transfer', 'Transfer', '#20c997', 100, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'loan', 'Loan', '#868e96', 110, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'found', 'Found', '#6c757d', 120, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'created', 'Created', '#28a745', 130, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'unknown', 'Unknown', '#6c757d', 140, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_event_type', 'Provenance Event Type', 'creation', 'Creation', '#28a745', 10, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'commission', 'Commission', '#007bff', 20, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'sale', 'Sale', '#ffc107', 30, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'purchase', 'Purchase', '#28a745', 40, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'auction', 'Auction', '#fd7e14', 50, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'gift', 'Gift', '#6f42c1', 60, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'donation', 'Donation', '#17a2b8', 70, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'bequest', 'Bequest', '#e83e8c', 80, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'inheritance', 'Inheritance', '#20c997', 90, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'descent', 'Descent', '#343a40', 100, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'loan_out', 'Loan Out', '#dc3545', 110, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'loan_return', 'Loan Return', '#28a745', 120, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'deposit', 'Deposit', '#007bff', 130, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'withdrawal', 'Withdrawal', '#fd7e14', 140, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'transfer', 'Transfer', '#6f42c1', 150, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'exchange', 'Exchange', '#17a2b8', 160, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'theft', 'Theft', '#dc3545', 170, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'recovery', 'Recovery', '#28a745', 180, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'confiscation', 'Confiscation', '#343a40', 190, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'restitution', 'Restitution', '#6f42c1', 200, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'repatriation', 'Repatriation', '#e83e8c', 210, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'discovery', 'Discovery', '#ffc107', 220, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'excavation', 'Excavation', '#fd7e14', 230, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'import', 'Import', '#17a2b8', 240, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'export', 'Export', '#20c997', 250, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'authentication', 'Authentication', '#007bff', 260, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'appraisal', 'Appraisal', '#28a745', 270, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'conservation', 'Conservation', '#6f42c1', 280, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'restoration', 'Restoration', '#fd7e14', 290, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'accessioning', 'Accessioning', '#ffc107', 300, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'deaccessioning', 'Deaccessioning', '#dc3545', 310, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'unknown', 'Unknown', '#6c757d', 320, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'other', 'Other', '#868e96', 330, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('evidence_type', 'Evidence Type', 'documentary', 'Documentary', '#28a745', 10, 1, NOW()),
('evidence_type', 'Evidence Type', 'physical', 'Physical', '#007bff', 20, 1, NOW()),
('evidence_type', 'Evidence Type', 'oral', 'Oral', '#6f42c1', 30, 1, NOW()),
('evidence_type', 'Evidence Type', 'circumstantial', 'Circumstantial', '#ffc107', 40, 1, NOW()),
('evidence_type', 'Evidence Type', 'none', 'None', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_acquisition_type', 'Provenance Acquisition Type', 'donation', 'Donation', '#28a745', 10, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'purchase', 'Purchase', '#007bff', 20, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'bequest', 'Bequest', '#6f42c1', 30, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'transfer', 'Transfer', '#17a2b8', 40, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'loan', 'Loan', '#ffc107', 50, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'deposit', 'Deposit', '#fd7e14', 60, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'exchange', 'Exchange', '#e83e8c', 70, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'field_collection', 'Field Collection', '#20c997', 80, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'unknown', 'Unknown', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('cultural_property_status', 'Cultural Property Status', 'none', 'None', '#6c757d', 10, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'claimed', 'Claimed', '#ffc107', 20, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'disputed', 'Disputed', '#dc3545', 30, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'repatriated', 'Repatriated', '#6f42c1', 40, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'cleared', 'Cleared', '#28a745', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('custody_type', 'Custody Type', 'permanent', 'Permanent', '#28a745', 10, 1, NOW()),
('custody_type', 'Custody Type', 'temporary', 'Temporary', '#ffc107', 20, 1, NOW()),
('custody_type', 'Custody Type', 'loan', 'Loan', '#007bff', 30, 1, NOW()),
('custody_type', 'Custody Type', 'deposit', 'Deposit', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_status', 'Research Status', 'not_started', 'Not Started', '#6c757d', 10, 1, NOW()),
('research_status', 'Research Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('research_status', 'Research Status', 'complete', 'Complete', '#28a745', 30, 1, NOW()),
('research_status', 'Research Status', 'inconclusive', 'Inconclusive', '#ffc107', 40, 1, NOW());

-- ============================================================================
-- REPORT TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('report_category', 'Report Category', 'collection', 'Collection', '#007bff', 10, 1, NOW()),
('report_category', 'Report Category', 'acquisition', 'Acquisition', '#28a745', 20, 1, NOW()),
('report_category', 'Report Category', 'access', 'Access', '#6f42c1', 30, 1, NOW()),
('report_category', 'Report Category', 'preservation', 'Preservation', '#fd7e14', 40, 1, NOW()),
('report_category', 'Report Category', 'researcher', 'Researcher', '#17a2b8', 50, 1, NOW()),
('report_category', 'Report Category', 'compliance', 'Compliance', '#dc3545', 60, 1, NOW()),
('report_category', 'Report Category', 'statistics', 'Statistics', '#ffc107', 70, 1, NOW()),
('report_category', 'Report Category', 'custom', 'Custom', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('report_frequency', 'Report Frequency', 'daily', 'Daily', '#dc3545', 10, 1, NOW()),
('report_frequency', 'Report Frequency', 'weekly', 'Weekly', '#fd7e14', 20, 1, NOW()),
('report_frequency', 'Report Frequency', 'monthly', 'Monthly', '#ffc107', 30, 1, NOW()),
('report_frequency', 'Report Frequency', 'quarterly', 'Quarterly', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('report_format', 'Report Format', 'pdf', 'PDF', '#dc3545', 10, 1, NOW()),
('report_format', 'Report Format', 'xlsx', 'Excel', '#28a745', 20, 1, NOW()),
('report_format', 'Report Format', 'csv', 'CSV', '#007bff', 30, 1, NOW());

-- ============================================================================
-- RIC (Records in Context) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_detection_method', 'Orphan Detection Method', 'integrity_check', 'Integrity Check', '#007bff', 10, 1, NOW()),
('orphan_detection_method', 'Orphan Detection Method', 'sync_failure', 'Sync Failure', '#dc3545', 20, 1, NOW()),
('orphan_detection_method', 'Orphan Detection Method', 'manual', 'Manual', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_status', 'Orphan Status', 'detected', 'Detected', '#ffc107', 10, 1, NOW()),
('orphan_status', 'Orphan Status', 'reviewed', 'Reviewed', '#17a2b8', 20, 1, NOW()),
('orphan_status', 'Orphan Status', 'cleaned', 'Cleaned', '#28a745', 30, 1, NOW()),
('orphan_status', 'Orphan Status', 'retained', 'Retained', '#6f42c1', 40, 1, NOW()),
('orphan_status', 'Orphan Status', 'restored', 'Restored', '#007bff', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ric_operation', 'RIC Operation', 'create', 'Create', '#28a745', 10, 1, NOW()),
('ric_operation', 'RIC Operation', 'update', 'Update', '#007bff', 20, 1, NOW()),
('ric_operation', 'RIC Operation', 'delete', 'Delete', '#dc3545', 30, 1, NOW()),
('ric_operation', 'RIC Operation', 'move', 'Move', '#6f42c1', 40, 1, NOW()),
('ric_operation', 'RIC Operation', 'resync', 'Resync', '#17a2b8', 50, 1, NOW()),
('ric_operation', 'RIC Operation', 'cleanup', 'Cleanup', '#ffc107', 60, 1, NOW()),
('ric_operation', 'RIC Operation', 'integrity_check', 'Integrity Check', '#fd7e14', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('sync_status', 'Sync Status', 'synced', 'Synced', '#28a745', 10, 1, NOW()),
('sync_status', 'Sync Status', 'pending', 'Pending', '#ffc107', 20, 1, NOW()),
('sync_status', 'Sync Status', 'failed', 'Failed', '#dc3545', 30, 1, NOW()),
('sync_status', 'Sync Status', 'deleted', 'Deleted', '#343a40', 40, 1, NOW()),
('sync_status', 'Sync Status', 'orphaned', 'Orphaned', '#6c757d', 50, 1, NOW());

-- ============================================================================
-- RIGHTS TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('derivative_rule_type', 'Derivative Rule Type', 'watermark', 'Watermark', '#007bff', 10, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'redaction', 'Redaction', '#dc3545', 20, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'resize', 'Resize', '#28a745', 30, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'format_conversion', 'Format Conversion', '#6f42c1', 40, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'metadata_strip', 'Metadata Strip', '#ffc107', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('watermark_position', 'Watermark Position', 'center', 'Center', '#007bff', 10, 1, NOW()),
('watermark_position', 'Watermark Position', 'top_left', 'Top Left', '#28a745', 20, 1, NOW()),
('watermark_position', 'Watermark Position', 'top_right', 'Top Right', '#6f42c1', 30, 1, NOW()),
('watermark_position', 'Watermark Position', 'bottom_left', 'Bottom Left', '#fd7e14', 40, 1, NOW()),
('watermark_position', 'Watermark Position', 'bottom_right', 'Bottom Right', '#17a2b8', 50, 1, NOW()),
('watermark_position', 'Watermark Position', 'tile', 'Tile', '#ffc107', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_grant_act', 'Rights Grant Act', 'render', 'Render', '#007bff', 10, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'disseminate', 'Disseminate', '#28a745', 20, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'replicate', 'Replicate', '#6f42c1', 30, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'migrate', 'Migrate', '#fd7e14', 40, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'modify', 'Modify', '#17a2b8', 50, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'delete', 'Delete', '#dc3545', 60, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'print', 'Print', '#ffc107', 70, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'use', 'Use', '#e83e8c', 80, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'publish', 'Publish', '#20c997', 90, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'excerpt', 'Excerpt', '#343a40', 100, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'annotate', 'Annotate', '#6c757d', 110, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'move', 'Move', '#868e96', 120, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'sell', 'Sell', '#28a745', 130, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_restriction', 'Rights Restriction', 'allow', 'Allow', '#28a745', 10, 1, NOW()),
('rights_restriction', 'Rights Restriction', 'disallow', 'Disallow', '#dc3545', 20, 1, NOW()),
('rights_restriction', 'Rights Restriction', 'conditional', 'Conditional', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_search_source', 'Orphan Search Source', 'database', 'Database', '#007bff', 10, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'registry', 'Registry', '#28a745', 20, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'publisher', 'Publisher', '#6f42c1', 30, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'author_society', 'Author Society', '#fd7e14', 40, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'archive', 'Archive', '#17a2b8', 50, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'library', 'Library', '#ffc107', 60, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'internet', 'Internet', '#e83e8c', 70, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'newspaper', 'Newspaper', '#20c997', 80, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_work_status', 'Orphan Work Status', 'in_progress', 'In Progress', '#007bff', 10, 1, NOW()),
('orphan_work_status', 'Orphan Work Status', 'completed', 'Completed', '#28a745', 20, 1, NOW()),
('orphan_work_status', 'Orphan Work Status', 'rights_holder_found', 'Rights Holder Found', '#6f42c1', 30, 1, NOW()),
('orphan_work_status', 'Orphan Work Status', 'abandoned', 'Abandoned', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('work_type', 'Work Type', 'literary', 'Literary', '#007bff', 10, 1, NOW()),
('work_type', 'Work Type', 'dramatic', 'Dramatic', '#28a745', 20, 1, NOW()),
('work_type', 'Work Type', 'musical', 'Musical', '#6f42c1', 30, 1, NOW()),
('work_type', 'Work Type', 'artistic', 'Artistic', '#fd7e14', 40, 1, NOW()),
('work_type', 'Work Type', 'film', 'Film', '#17a2b8', 50, 1, NOW()),
('work_type', 'Work Type', 'sound_recording', 'Sound Recording', '#ffc107', 60, 1, NOW()),
('work_type', 'Work Type', 'broadcast', 'Broadcast', '#e83e8c', 70, 1, NOW()),
('work_type', 'Work Type', 'typographical', 'Typographical', '#20c997', 80, 1, NOW()),
('work_type', 'Work Type', 'database', 'Database', '#343a40', 90, 1, NOW()),
('work_type', 'Work Type', 'photograph', 'Photograph', '#dc3545', 100, 1, NOW()),
('work_type', 'Work Type', 'other', 'Other', '#6c757d', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_basis', 'Rights Basis', 'copyright', 'Copyright', '#007bff', 10, 1, NOW()),
('rights_basis', 'Rights Basis', 'license', 'License', '#28a745', 20, 1, NOW()),
('rights_basis', 'Rights Basis', 'statute', 'Statute', '#6f42c1', 30, 1, NOW()),
('rights_basis', 'Rights Basis', 'donor', 'Donor', '#fd7e14', 40, 1, NOW()),
('rights_basis', 'Rights Basis', 'policy', 'Policy', '#17a2b8', 50, 1, NOW()),
('rights_basis', 'Rights Basis', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('copyright_status', 'Copyright Status', 'copyrighted', 'Copyrighted', '#dc3545', 10, 1, NOW()),
('copyright_status', 'Copyright Status', 'public_domain', 'Public Domain', '#28a745', 20, 1, NOW()),
('copyright_status', 'Copyright Status', 'unknown', 'Unknown', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_statement_category', 'Rights Statement Category', 'in-copyright', 'In Copyright', '#dc3545', 10, 1, NOW()),
('rights_statement_category', 'Rights Statement Category', 'no-copyright', 'No Copyright', '#28a745', 20, 1, NOW()),
('rights_statement_category', 'Rights Statement Category', 'other', 'Other', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('territory_type', 'Territory Type', 'include', 'Include', '#28a745', 10, 1, NOW()),
('territory_type', 'Territory Type', 'exclude', 'Exclude', '#dc3545', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tk_rights_category', 'TK Rights Category', 'tk', 'Traditional Knowledge', '#dc3545', 10, 1, NOW()),
('tk_rights_category', 'TK Rights Category', 'bc', 'Biocultural', '#28a745', 20, 1, NOW()),
('tk_rights_category', 'TK Rights Category', 'attribution', 'Attribution', '#007bff', 30, 1, NOW());

-- ============================================================================
-- HERITAGE ACCESS TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_access_action', 'Heritage Access Action', 'view', 'View', '#6c757d', 10, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'view_metadata', 'View Metadata', '#007bff', 20, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'download', 'Download', '#28a745', 30, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'download_master', 'Download Master', '#6f42c1', 40, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'print', 'Print', '#fd7e14', 50, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'all', 'All', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_access_applies_to', 'Heritage Access Applies To', 'all', 'All', '#28a745', 10, 1, NOW()),
('heritage_access_applies_to', 'Heritage Access Applies To', 'anonymous', 'Anonymous', '#6c757d', 20, 1, NOW()),
('heritage_access_applies_to', 'Heritage Access Applies To', 'authenticated', 'Authenticated', '#007bff', 30, 1, NOW()),
('heritage_access_applies_to', 'Heritage Access Applies To', 'trust_level', 'Trust Level', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_rule_type', 'Heritage Rule Type', 'allow', 'Allow', '#28a745', 10, 1, NOW()),
('heritage_rule_type', 'Heritage Rule Type', 'deny', 'Deny', '#dc3545', 20, 1, NOW()),
('heritage_rule_type', 'Heritage Rule Type', 'require_approval', 'Require Approval', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('analytics_category', 'Analytics Category', 'content', 'Content', '#007bff', 10, 1, NOW()),
('analytics_category', 'Analytics Category', 'search', 'Search', '#28a745', 20, 1, NOW()),
('analytics_category', 'Analytics Category', 'access', 'Access', '#6f42c1', 30, 1, NOW()),
('analytics_category', 'Analytics Category', 'quality', 'Quality', '#fd7e14', 40, 1, NOW()),
('analytics_category', 'Analytics Category', 'system', 'System', '#17a2b8', 50, 1, NOW()),
('analytics_category', 'Analytics Category', 'opportunity', 'Opportunity', '#ffc107', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('analytics_severity', 'Analytics Severity', 'info', 'Info', '#17a2b8', 10, 1, NOW()),
('analytics_severity', 'Analytics Severity', 'warning', 'Warning', '#ffc107', 20, 1, NOW()),
('analytics_severity', 'Analytics Severity', 'critical', 'Critical', '#dc3545', 30, 1, NOW()),
('analytics_severity', 'Analytics Severity', 'success', 'Success', '#28a745', 40, 1, NOW());

-- ============================================================================
-- EMAIL/NOTIFICATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('email_setting_type', 'Email Setting Type', 'text', 'Text', '#007bff', 10, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'email', 'Email', '#28a745', 20, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'number', 'Number', '#6f42c1', 30, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'boolean', 'Boolean', '#fd7e14', 40, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'textarea', 'Textarea', '#17a2b8', 50, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'password', 'Password', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('event_type', 'Event Type', 'view', 'View', '#6c757d', 10, 1, NOW()),
('event_type', 'Event Type', 'download', 'Download', '#28a745', 20, 1, NOW()),
('event_type', 'Event Type', 'search', 'Search', '#007bff', 30, 1, NOW()),
('event_type', 'Event Type', 'login', 'Login', '#6f42c1', 40, 1, NOW()),
('event_type', 'Event Type', 'api', 'API', '#fd7e14', 50, 1, NOW());

-- ============================================================================
-- Final statistics
-- ============================================================================

SELECT 'Phase 2D Migration Complete - ALL ENUM TYPES MIGRATED' as status;
SELECT COUNT(DISTINCT taxonomy) as total_taxonomies, COUNT(*) as total_terms FROM ahg_dropdown;
