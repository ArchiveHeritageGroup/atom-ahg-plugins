-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2
-- Generated: 2026-02-04
--
-- This script migrates remaining ENUM values not covered in Phase 1
-- to the ahg_dropdown system for centralized vocabulary management.
--
-- Run this AFTER Phase 1 migration completes.
-- ============================================================================

-- ============================================================================
-- ACCESS REQUEST TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_type', 'Access Request Type', 'clearance', 'Security Clearance', '#dc3545', 10, 1, NOW()),
('access_request_type', 'Access Request Type', 'object', 'Object Access', '#007bff', 20, 1, NOW()),
('access_request_type', 'Access Request Type', 'repository', 'Repository Access', '#28a745', 30, 1, NOW()),
('access_request_type', 'Access Request Type', 'authority', 'Authority Record Access', '#6f42c1', 40, 1, NOW()),
('access_request_type', 'Access Request Type', 'researcher', 'Researcher Registration', '#fd7e14', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_scope', 'Access Request Scope', 'single', 'Single Item', '#007bff', 10, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'with_children', 'With Children', '#28a745', 20, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'collection', 'Entire Collection', '#6f42c1', 30, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'repository_all', 'All Repository Items', '#fd7e14', 40, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'renewal', 'Renewal', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_status', 'Access Request Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('access_request_status', 'Access Request Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('access_request_status', 'Access Request Status', 'denied', 'Denied', '#dc3545', 30, 1, NOW()),
('access_request_status', 'Access Request Status', 'cancelled', 'Cancelled', '#6c757d', 40, 1, NOW()),
('access_request_status', 'Access Request Status', 'expired', 'Expired', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_action', 'Access Request Action', 'created', 'Created', '#28a745', 10, 1, NOW()),
('access_request_action', 'Access Request Action', 'updated', 'Updated', '#007bff', 20, 1, NOW()),
('access_request_action', 'Access Request Action', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('access_request_action', 'Access Request Action', 'denied', 'Denied', '#dc3545', 40, 1, NOW()),
('access_request_action', 'Access Request Action', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW()),
('access_request_action', 'Access Request Action', 'expired', 'Expired', '#343a40', 60, 1, NOW()),
('access_request_action', 'Access Request Action', 'escalated', 'Escalated', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_object_type', 'Access Object Type', 'information_object', 'Information Object', '#007bff', 10, 1, NOW()),
('access_object_type', 'Access Object Type', 'repository', 'Repository', '#28a745', 20, 1, NOW()),
('access_object_type', 'Access Object Type', 'actor', 'Actor', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- AGREEMENT RIGHTS VOCABULARY
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('agreement_rights_category', 'Agreement Rights Category', 'usage', 'Usage Rights', '#007bff', 10, 1, NOW()),
('agreement_rights_category', 'Agreement Rights Category', 'restriction', 'Restriction', '#dc3545', 20, 1, NOW()),
('agreement_rights_category', 'Agreement Rights Category', 'condition', 'Condition', '#ffc107', 30, 1, NOW()),
('agreement_rights_category', 'Agreement Rights Category', 'license', 'License', '#28a745', 40, 1, NOW());

-- ============================================================================
-- BOT LIST CATEGORIES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('bot_category', 'Bot Category', 'search_engine', 'Search Engine', '#28a745', 10, 1, NOW()),
('bot_category', 'Bot Category', 'social', 'Social Media', '#007bff', 20, 1, NOW()),
('bot_category', 'Bot Category', 'monitoring', 'Monitoring', '#17a2b8', 30, 1, NOW()),
('bot_category', 'Bot Category', 'crawler', 'Crawler', '#6f42c1', 40, 1, NOW()),
('bot_category', 'Bot Category', 'spam', 'Spam', '#dc3545', 50, 1, NOW()),
('bot_category', 'Bot Category', 'other', 'Other', '#6c757d', 60, 1, NOW());

-- ============================================================================
-- EXTENSION SYSTEM TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extension_protection_level', 'Extension Protection Level', 'core', 'Core', '#dc3545', 10, 1, NOW()),
('extension_protection_level', 'Extension Protection Level', 'system', 'System', '#fd7e14', 20, 1, NOW()),
('extension_protection_level', 'Extension Protection Level', 'theme', 'Theme', '#6f42c1', 30, 1, NOW()),
('extension_protection_level', 'Extension Protection Level', 'extension', 'Extension', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extension_status', 'Extension Status', 'installed', 'Installed', '#17a2b8', 10, 1, NOW()),
('extension_status', 'Extension Status', 'enabled', 'Enabled', '#28a745', 20, 1, NOW()),
('extension_status', 'Extension Status', 'disabled', 'Disabled', '#6c757d', 30, 1, NOW()),
('extension_status', 'Extension Status', 'pending_removal', 'Pending Removal', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extension_audit_action', 'Extension Audit Action', 'discovered', 'Discovered', '#17a2b8', 10, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'installed', 'Installed', '#28a745', 20, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'enabled', 'Enabled', '#007bff', 30, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'disabled', 'Disabled', '#6c757d', 40, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'uninstalled', 'Uninstalled', '#dc3545', 50, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'upgraded', 'Upgraded', '#28a745', 60, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'downgraded', 'Downgraded', '#fd7e14', 70, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'backup_created', 'Backup Created', '#6f42c1', 80, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'backup_restored', 'Backup Restored', '#20c997', 90, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'data_deleted', 'Data Deleted', '#343a40', 100, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'config_changed', 'Config Changed', '#ffc107', 110, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'error', 'Error', '#dc3545', 120, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('menu_location', 'Menu Location', 'main', 'Main Menu', '#007bff', 10, 1, NOW()),
('menu_location', 'Menu Location', 'admin', 'Admin Menu', '#dc3545', 20, 1, NOW()),
('menu_location', 'Menu Location', 'user', 'User Menu', '#28a745', 30, 1, NOW()),
('menu_location', 'Menu Location', 'footer', 'Footer Menu', '#6c757d', 40, 1, NOW()),
('menu_location', 'Menu Location', 'mobile', 'Mobile Menu', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('widget_type', 'Widget Type', 'stat_card', 'Stat Card', '#007bff', 10, 1, NOW()),
('widget_type', 'Widget Type', 'chart', 'Chart', '#28a745', 20, 1, NOW()),
('widget_type', 'Widget Type', 'list', 'List', '#6f42c1', 30, 1, NOW()),
('widget_type', 'Widget Type', 'table', 'Table', '#fd7e14', 40, 1, NOW()),
('widget_type', 'Widget Type', 'html', 'HTML', '#17a2b8', 50, 1, NOW()),
('widget_type', 'Widget Type', 'custom', 'Custom', '#6c757d', 60, 1, NOW()),
('widget_type', 'Widget Type', 'count', 'Count', '#ffc107', 70, 1, NOW()),
('widget_type', 'Widget Type', 'stat', 'Stat', '#e83e8c', 80, 1, NOW());

-- ============================================================================
-- ISBN/LIBRARY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('isbn_response_format', 'ISBN Response Format', 'json', 'JSON', '#007bff', 10, 1, NOW()),
('isbn_response_format', 'ISBN Response Format', 'xml', 'XML', '#28a745', 20, 1, NOW()),
('isbn_response_format', 'ISBN Response Format', 'marcxml', 'MARCXML', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('landing_page_status', 'Landing Page Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('landing_page_status', 'Landing Page Status', 'published', 'Published', '#28a745', 20, 1, NOW()),
('landing_page_status', 'Landing Page Status', 'archived', 'Archived', '#343a40', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('library_heading_type', 'Library Heading Type', 'topical', 'Topical', '#007bff', 10, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'personal', 'Personal', '#28a745', 20, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'corporate', 'Corporate', '#6f42c1', 30, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'geographic', 'Geographic', '#fd7e14', 40, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'genre', 'Genre/Form', '#17a2b8', 50, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'meeting', 'Meeting', '#e83e8c', 60, 1, NOW());

-- ============================================================================
-- CDPA/PRIVACY COMPLIANCE TYPES (Zimbabwe)
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_type', 'Breach Type', 'unauthorized_access', 'Unauthorized Access', '#dc3545', 10, 1, NOW()),
('breach_type', 'Breach Type', 'data_loss', 'Data Loss', '#fd7e14', 20, 1, NOW()),
('breach_type', 'Breach Type', 'data_theft', 'Data Theft', '#dc3545', 30, 1, NOW()),
('breach_type', 'Breach Type', 'accidental_disclosure', 'Accidental Disclosure', '#ffc107', 40, 1, NOW()),
('breach_type', 'Breach Type', 'system_breach', 'System Breach', '#dc3545', 50, 1, NOW()),
('breach_type', 'Breach Type', 'confidentiality', 'Confidentiality Breach', '#e83e8c', 60, 1, NOW()),
('breach_type', 'Breach Type', 'integrity', 'Integrity Breach', '#6f42c1', 70, 1, NOW()),
('breach_type', 'Breach Type', 'availability', 'Availability Breach', '#17a2b8', 80, 1, NOW()),
('breach_type', 'Breach Type', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_status', 'Breach Status', 'investigating', 'Investigating', '#ffc107', 10, 1, NOW()),
('breach_status', 'Breach Status', 'contained', 'Contained', '#17a2b8', 20, 1, NOW()),
('breach_status', 'Breach Status', 'resolved', 'Resolved', '#28a745', 30, 1, NOW()),
('breach_status', 'Breach Status', 'ongoing', 'Ongoing', '#dc3545', 40, 1, NOW()),
('breach_status', 'Breach Status', 'detected', 'Detected', '#fd7e14', 50, 1, NOW()),
('breach_status', 'Breach Status', 'closed', 'Closed', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_method', 'Consent Method', 'written', 'Written', '#28a745', 10, 1, NOW()),
('consent_method', 'Consent Method', 'electronic', 'Electronic', '#007bff', 20, 1, NOW()),
('consent_method', 'Consent Method', 'verbal', 'Verbal', '#ffc107', 30, 1, NOW()),
('consent_method', 'Consent Method', 'opt_in', 'Opt-in', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('controller_tier', 'Controller Tier', 'tier1', 'Tier 1', '#dc3545', 10, 1, NOW()),
('controller_tier', 'Controller Tier', 'tier2', 'Tier 2', '#fd7e14', 20, 1, NOW()),
('controller_tier', 'Controller Tier', 'tier3', 'Tier 3', '#ffc107', 30, 1, NOW()),
('controller_tier', 'Controller Tier', 'tier4', 'Tier 4', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dsar_request_type', 'DSAR Request Type', 'access', 'Access Request', '#007bff', 10, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'rectification', 'Rectification', '#28a745', 20, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'erasure', 'Erasure', '#dc3545', 30, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'object', 'Object to Processing', '#fd7e14', 40, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'portability', 'Data Portability', '#17a2b8', 50, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'restriction', 'Restriction', '#6f42c1', 60, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'withdraw_consent', 'Withdraw Consent', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dsar_status', 'DSAR Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('dsar_status', 'DSAR Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('dsar_status', 'DSAR Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('dsar_status', 'DSAR Status', 'rejected', 'Rejected', '#dc3545', 40, 1, NOW()),
('dsar_status', 'DSAR Status', 'extended', 'Extended', '#17a2b8', 50, 1, NOW()),
('dsar_status', 'DSAR Status', 'received', 'Received', '#6c757d', 60, 1, NOW()),
('dsar_status', 'DSAR Status', 'verified', 'Verified', '#28a745', 70, 1, NOW()),
('dsar_status', 'DSAR Status', 'pending_info', 'Pending Info', '#fd7e14', 80, 1, NOW()),
('dsar_status', 'DSAR Status', 'withdrawn', 'Withdrawn', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dpia_status', 'DPIA Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('dpia_status', 'DPIA Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('dpia_status', 'DPIA Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('dpia_status', 'DPIA Status', 'approved', 'Approved', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('legal_basis', 'Legal Basis', 'consent', 'Consent', '#28a745', 10, 1, NOW()),
('legal_basis', 'Legal Basis', 'contract', 'Contract', '#007bff', 20, 1, NOW()),
('legal_basis', 'Legal Basis', 'legal_obligation', 'Legal Obligation', '#dc3545', 30, 1, NOW()),
('legal_basis', 'Legal Basis', 'vital_interest', 'Vital Interest', '#fd7e14', 40, 1, NOW()),
('legal_basis', 'Legal Basis', 'public_interest', 'Public Interest', '#6f42c1', 50, 1, NOW()),
('legal_basis', 'Legal Basis', 'legitimate_interest', 'Legitimate Interest', '#17a2b8', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('storage_location', 'Storage Location', 'zimbabwe', 'Zimbabwe', '#28a745', 10, 1, NOW()),
('storage_location', 'Storage Location', 'international', 'International', '#007bff', 20, 1, NOW()),
('storage_location', 'Storage Location', 'both', 'Both', '#ffc107', 30, 1, NOW());

-- ============================================================================
-- CONDITION REPORT TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('damage_severity', 'Damage Severity', 'minor', 'Minor', '#28a745', 10, 1, NOW()),
('damage_severity', 'Damage Severity', 'moderate', 'Moderate', '#ffc107', 20, 1, NOW()),
('damage_severity', 'Damage Severity', 'severe', 'Severe', '#dc3545', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_image_type', 'Condition Image Type', 'general', 'General', '#007bff', 10, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'detail', 'Detail', '#28a745', 20, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'damage', 'Damage', '#dc3545', 30, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'before', 'Before', '#6c757d', 40, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'after', 'After', '#28a745', 50, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'raking', 'Raking Light', '#ffc107', 60, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'uv', 'UV', '#6f42c1', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_report_context', 'Condition Report Context', 'acquisition', 'Acquisition', '#28a745', 10, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'loan_out', 'Loan Out', '#007bff', 20, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'loan_in', 'Loan In', '#17a2b8', 30, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'loan_return', 'Loan Return', '#20c997', 40, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'exhibition', 'Exhibition', '#6f42c1', 50, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'storage', 'Storage', '#6c757d', 60, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'conservation', 'Conservation', '#fd7e14', 70, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'routine', 'Routine', '#ffc107', 80, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'incident', 'Incident', '#dc3545', 90, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'insurance', 'Insurance', '#e83e8c', 100, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'deaccession', 'Deaccession', '#343a40', 110, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'pre_loan', 'Pre-Loan', '#007bff', 120, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'post_loan', 'Post-Loan', '#28a745', 130, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'in_transit', 'In Transit', '#17a2b8', 140, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'periodic', 'Periodic', '#6c757d', 150, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_vocabulary_type', 'Condition Vocabulary Type', 'damage_type', 'Damage Type', '#dc3545', 10, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'severity', 'Severity', '#fd7e14', 20, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'condition', 'Condition', '#28a745', 30, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'priority', 'Priority', '#ffc107', 40, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'material', 'Material', '#007bff', 50, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'location_zone', 'Location Zone', '#6f42c1', 60, 1, NOW());

-- ============================================================================
-- CONTACT INFORMATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preferred_contact_method', 'Preferred Contact Method', 'email', 'Email', '#007bff', 10, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'phone', 'Phone', '#28a745', 20, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'cell', 'Cell/Mobile', '#17a2b8', 30, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'fax', 'Fax', '#6c757d', 40, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'mail', 'Post/Mail', '#fd7e14', 50, 1, NOW());

-- ============================================================================
-- DAM (DIGITAL ASSET MANAGEMENT) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dam_link_type', 'DAM Link Type', 'ESAT', 'ESAT', '#007bff', 10, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'IMDb', 'IMDb', '#ffc107', 20, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'SAFILM', 'SA Film', '#28a745', 30, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'NFVSA', 'NFVSA', '#6f42c1', 40, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Wikipedia', 'Wikipedia', '#6c757d', 50, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Wikidata', 'Wikidata', '#dc3545', 60, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'VIAF', 'VIAF', '#17a2b8', 70, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'YouTube', 'YouTube', '#dc3545', 80, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Vimeo', 'Vimeo', '#17a2b8', 90, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Archive_org', 'Archive.org', '#28a745', 100, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'BFI', 'BFI', '#007bff', 110, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'AFI', 'AFI', '#fd7e14', 120, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Letterboxd', 'Letterboxd', '#fd7e14', 130, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'MUBI', 'MUBI', '#e83e8c', 140, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Filmography', 'Filmography', '#6f42c1', 150, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Review', 'Review', '#ffc107', 160, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Academic', 'Academic', '#28a745', 170, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Press', 'Press', '#17a2b8', 180, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Other', 'Other', '#6c757d', 190, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dam_access_status', 'DAM Access Status', 'available', 'Available', '#28a745', 10, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'restricted', 'Restricted', '#dc3545', 20, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'preservation_only', 'Preservation Only', '#6f42c1', 30, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'digitized_available', 'Digitized Available', '#007bff', 40, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'on_request', 'On Request', '#ffc107', 50, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'staff_only', 'Staff Only', '#fd7e14', 60, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'unknown', 'Unknown', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dam_format_type', 'DAM Format Type', '35mm', '35mm Film', '#007bff', 10, 1, NOW()),
('dam_format_type', 'DAM Format Type', '16mm', '16mm Film', '#28a745', 20, 1, NOW()),
('dam_format_type', 'DAM Format Type', '8mm', '8mm Film', '#17a2b8', 30, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Super8', 'Super 8', '#20c997', 40, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'VHS', 'VHS', '#6c757d', 50, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Betacam', 'Betacam', '#6f42c1', 60, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'U-matic', 'U-matic', '#fd7e14', 70, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'DV', 'DV', '#ffc107', 80, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'DVD', 'DVD', '#dc3545', 90, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Blu-ray', 'Blu-ray', '#007bff', 100, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'LaserDisc', 'LaserDisc', '#343a40', 110, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Digital_File', 'Digital File', '#28a745', 120, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'DCP', 'DCP', '#e83e8c', 130, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'ProRes', 'ProRes', '#6f42c1', 140, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Nitrate', 'Nitrate', '#dc3545', 150, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Safety', 'Safety Film', '#28a745', 160, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Polyester', 'Polyester', '#17a2b8', 170, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Audio_Reel', 'Audio Reel', '#fd7e14', 180, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Audio_Cassette', 'Audio Cassette', '#ffc107', 190, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Vinyl', 'Vinyl', '#343a40', 200, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'CD', 'CD', '#6c757d', 210, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Other', 'Other', '#868e96', 220, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('color_type', 'Color Type', 'color', 'Color', '#28a745', 10, 1, NOW()),
('color_type', 'Color Type', 'black_and_white', 'Black & White', '#343a40', 20, 1, NOW()),
('color_type', 'Color Type', 'mixed', 'Mixed', '#6f42c1', 30, 1, NOW()),
('color_type', 'Color Type', 'colorized', 'Colorized', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('license_type', 'License Type', 'rights_managed', 'Rights Managed', '#dc3545', 10, 1, NOW()),
('license_type', 'License Type', 'royalty_free', 'Royalty Free', '#28a745', 20, 1, NOW()),
('license_type', 'License Type', 'creative_commons', 'Creative Commons', '#007bff', 30, 1, NOW()),
('license_type', 'License Type', 'public_domain', 'Public Domain', '#6c757d', 40, 1, NOW()),
('license_type', 'License Type', 'editorial', 'Editorial Use Only', '#ffc107', 50, 1, NOW()),
('license_type', 'License Type', 'other', 'Other', '#868e96', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('model_release_status', 'Model Release Status', 'none', 'None', '#dc3545', 10, 1, NOW()),
('model_release_status', 'Model Release Status', 'not_applicable', 'Not Applicable', '#6c757d', 20, 1, NOW()),
('model_release_status', 'Model Release Status', 'unlimited', 'Unlimited', '#28a745', 30, 1, NOW()),
('model_release_status', 'Model Release Status', 'limited', 'Limited', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('property_release_status', 'Property Release Status', 'none', 'None', '#dc3545', 10, 1, NOW()),
('property_release_status', 'Property Release Status', 'not_applicable', 'Not Applicable', '#6c757d', 20, 1, NOW()),
('property_release_status', 'Property Release Status', 'unlimited', 'Unlimited', '#28a745', 30, 1, NOW()),
('property_release_status', 'Property Release Status', 'limited', 'Limited', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('version_link_type', 'Version Link Type', 'language', 'Language Version', '#007bff', 10, 1, NOW()),
('version_link_type', 'Version Link Type', 'format', 'Format Version', '#28a745', 20, 1, NOW()),
('version_link_type', 'Version Link Type', 'restoration', 'Restoration', '#6f42c1', 30, 1, NOW()),
('version_link_type', 'Version Link Type', 'directors_cut', 'Director\'s Cut', '#fd7e14', 40, 1, NOW()),
('version_link_type', 'Version Link Type', 'censored', 'Censored', '#dc3545', 50, 1, NOW()),
('version_link_type', 'Version Link Type', 'other', 'Other', '#6c757d', 60, 1, NOW());

-- ============================================================================
-- DIGITAL OBJECT TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('identification_source', 'Identification Source', 'auto', 'Automatic', '#007bff', 10, 1, NOW()),
('identification_source', 'Identification Source', 'manual', 'Manual', '#28a745', 20, 1, NOW()),
('identification_source', 'Identification Source', 'verified', 'Verified', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('file_type', 'File Type', 'image', 'Image', '#28a745', 10, 1, NOW()),
('file_type', 'File Type', 'pdf', 'PDF', '#dc3545', 20, 1, NOW()),
('file_type', 'File Type', 'office', 'Office Document', '#007bff', 30, 1, NOW()),
('file_type', 'File Type', 'video', 'Video', '#6f42c1', 40, 1, NOW()),
('file_type', 'File Type', 'audio', 'Audio', '#fd7e14', 50, 1, NOW()),
('file_type', 'File Type', 'other', 'Other', '#6c757d', 60, 1, NOW());

-- ============================================================================
-- DISPLAY PROFILE TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('display_data_type', 'Display Data Type', 'text', 'Text', '#007bff', 10, 1, NOW()),
('display_data_type', 'Display Data Type', 'textarea', 'Textarea', '#28a745', 20, 1, NOW()),
('display_data_type', 'Display Data Type', 'date', 'Date', '#fd7e14', 30, 1, NOW()),
('display_data_type', 'Display Data Type', 'daterange', 'Date Range', '#ffc107', 40, 1, NOW()),
('display_data_type', 'Display Data Type', 'number', 'Number', '#17a2b8', 50, 1, NOW()),
('display_data_type', 'Display Data Type', 'select', 'Select', '#6f42c1', 60, 1, NOW()),
('display_data_type', 'Display Data Type', 'multiselect', 'Multi-select', '#e83e8c', 70, 1, NOW()),
('display_data_type', 'Display Data Type', 'relation', 'Relation', '#20c997', 80, 1, NOW()),
('display_data_type', 'Display Data Type', 'file', 'File', '#343a40', 90, 1, NOW()),
('display_data_type', 'Display Data Type', 'actor', 'Actor', '#6c757d', 100, 1, NOW()),
('display_data_type', 'Display Data Type', 'term', 'Term', '#868e96', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('display_field_group', 'Display Field Group', 'identity', 'Identity', '#007bff', 10, 1, NOW()),
('display_field_group', 'Display Field Group', 'description', 'Description', '#28a745', 20, 1, NOW()),
('display_field_group', 'Display Field Group', 'context', 'Context', '#6f42c1', 30, 1, NOW()),
('display_field_group', 'Display Field Group', 'access', 'Access', '#fd7e14', 40, 1, NOW()),
('display_field_group', 'Display Field Group', 'technical', 'Technical', '#17a2b8', 50, 1, NOW()),
('display_field_group', 'Display Field Group', 'admin', 'Admin', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('card_size', 'Card Size', 'small', 'Small', '#28a745', 10, 1, NOW()),
('card_size', 'Card Size', 'medium', 'Medium', '#007bff', 20, 1, NOW()),
('card_size', 'Card Size', 'large', 'Large', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('sort_direction', 'Sort Direction', 'asc', 'Ascending', '#28a745', 10, 1, NOW()),
('sort_direction', 'Sort Direction', 'desc', 'Descending', '#dc3545', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('layout_mode', 'Layout Mode', 'detail', 'Detail', '#007bff', 10, 1, NOW()),
('layout_mode', 'Layout Mode', 'hierarchy', 'Hierarchy', '#28a745', 20, 1, NOW()),
('layout_mode', 'Layout Mode', 'grid', 'Grid', '#6f42c1', 30, 1, NOW()),
('layout_mode', 'Layout Mode', 'gallery', 'Gallery', '#fd7e14', 40, 1, NOW()),
('layout_mode', 'Layout Mode', 'list', 'List', '#17a2b8', 50, 1, NOW()),
('layout_mode', 'Layout Mode', 'card', 'Card', '#ffc107', 60, 1, NOW()),
('layout_mode', 'Layout Mode', 'masonry', 'Masonry', '#e83e8c', 70, 1, NOW()),
('layout_mode', 'Layout Mode', 'catalog', 'Catalog', '#20c997', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('thumbnail_position', 'Thumbnail Position', 'left', 'Left', '#007bff', 10, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'right', 'Right', '#28a745', 20, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'top', 'Top', '#6f42c1', 30, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'background', 'Background', '#fd7e14', 40, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'inline', 'Inline', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('thumbnail_size', 'Thumbnail Size', 'none', 'None', '#6c757d', 10, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'small', 'Small', '#28a745', 20, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'medium', 'Medium', '#007bff', 30, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'large', 'Large', '#6f42c1', 40, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'hero', 'Hero', '#fd7e14', 50, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'full', 'Full', '#dc3545', 60, 1, NOW());

-- ============================================================================
-- DONOR AGREEMENT SPECIFIC TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_agreement_status', 'Donor Agreement Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'pending_review', 'Pending Review', '#ffc107', 20, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'pending_signature', 'Pending Signature', '#17a2b8', 30, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'active', 'Active', '#28a745', 40, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'expired', 'Expired', '#dc3545', 50, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'terminated', 'Terminated', '#343a40', 60, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'superseded', 'Superseded', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_document_type', 'Donor Document Type', 'signed_agreement', 'Signed Agreement', '#28a745', 10, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'draft', 'Draft', '#6c757d', 20, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'amendment', 'Amendment', '#007bff', 30, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'addendum', 'Addendum', '#17a2b8', 40, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'schedule', 'Schedule', '#6f42c1', 50, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'correspondence', 'Correspondence', '#ffc107', 60, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'appraisal_report', 'Appraisal Report', '#fd7e14', 70, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'inventory', 'Inventory', '#20c997', 80, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'deed_of_gift', 'Deed of Gift', '#28a745', 90, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'transfer_form', 'Transfer Form', '#007bff', 100, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'receipt', 'Receipt', '#17a2b8', 110, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'payment_record', 'Payment Record', '#e83e8c', 120, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'legal_opinion', 'Legal Opinion', '#dc3545', 130, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'board_resolution', 'Board Resolution', '#343a40', 140, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'donor_id', 'Donor ID', '#6c757d', 150, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'provenance_evidence', 'Provenance Evidence', '#6f42c1', 160, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'valuation', 'Valuation', '#ffc107', 170, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'insurance', 'Insurance', '#fd7e14', 180, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'photo', 'Photo', '#28a745', 190, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'other', 'Other', '#868e96', 200, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_relationship_type', 'Donor Relationship Type', 'covers', 'Covers', '#28a745', 10, 1, NOW()),
('donor_relationship_type', 'Donor Relationship Type', 'partially_covers', 'Partially Covers', '#ffc107', 20, 1, NOW()),
('donor_relationship_type', 'Donor Relationship Type', 'references', 'References', '#007bff', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_reminder_type', 'Donor Reminder Type', 'expiry_warning', 'Expiry Warning', '#dc3545', 10, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'review_due', 'Review Due', '#ffc107', 20, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'renewal_required', 'Renewal Required', '#fd7e14', 30, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'restriction_ending', 'Restriction Ending', '#17a2b8', 40, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'payment_due', 'Payment Due', '#007bff', 50, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'donor_contact', 'Donor Contact', '#28a745', 60, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'anniversary', 'Anniversary', '#6f42c1', 70, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'audit', 'Audit', '#e83e8c', 80, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'preservation_check', 'Preservation Check', '#20c997', 90, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'custom', 'Custom', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('popia_category', 'POPIA Category', 'special_personal', 'Special Personal', '#dc3545', 10, 1, NOW()),
('popia_category', 'POPIA Category', 'personal', 'Personal', '#fd7e14', 20, 1, NOW()),
('popia_category', 'POPIA Category', 'children', 'Children', '#e83e8c', 30, 1, NOW()),
('popia_category', 'POPIA Category', 'criminal', 'Criminal', '#343a40', 40, 1, NOW()),
('popia_category', 'POPIA Category', 'biometric', 'Biometric', '#6f42c1', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_restriction_type', 'Donor Restriction Type', 'closure', 'Closure', '#dc3545', 10, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'partial_closure', 'Partial Closure', '#fd7e14', 20, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'redaction', 'Redaction', '#ffc107', 30, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'permission_only', 'Permission Only', '#17a2b8', 40, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'researcher_only', 'Researcher Only', '#007bff', 50, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'onsite_only', 'Onsite Only', '#28a745', 60, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'no_copying', 'No Copying', '#6f42c1', 70, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'no_publication', 'No Publication', '#e83e8c', 80, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'anonymization', 'Anonymization', '#20c997', 90, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'time_embargo', 'Time Embargo', '#343a40', 100, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'review_required', 'Review Required', '#ffc107', 110, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'security_clearance', 'Security Clearance', '#dc3545', 120, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'popia_restricted', 'POPIA Restricted', '#e83e8c', 130, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'legal_hold', 'Legal Hold', '#343a40', 140, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'cultural_protocol', 'Cultural Protocol', '#6f42c1', 150, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_right_permission', 'Donor Right Permission', 'granted', 'Granted', '#28a745', 10, 1, NOW()),
('donor_right_permission', 'Donor Right Permission', 'restricted', 'Restricted', '#ffc107', 20, 1, NOW()),
('donor_right_permission', 'Donor Right Permission', 'prohibited', 'Prohibited', '#dc3545', 30, 1, NOW()),
('donor_right_permission', 'Donor Right Permission', 'conditional', 'Conditional', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_right_type', 'Donor Right Type', 'replicate', 'Replicate', '#007bff', 10, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'migrate', 'Migrate', '#28a745', 20, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'modify', 'Modify', '#6f42c1', 30, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'use', 'Use', '#ffc107', 40, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'disseminate', 'Disseminate', '#17a2b8', 50, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'delete', 'Delete', '#dc3545', 60, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'display', 'Display', '#20c997', 70, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'publish', 'Publish', '#fd7e14', 80, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'digitize', 'Digitize', '#e83e8c', 90, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'reproduce', 'Reproduce', '#343a40', 100, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'loan', 'Loan', '#6c757d', 110, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'exhibit', 'Exhibit', '#28a745', 120, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'broadcast', 'Broadcast', '#007bff', 130, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'commercial_use', 'Commercial Use', '#dc3545', 140, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'derivative_works', 'Derivative Works', '#6f42c1', 150, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_applies_to', 'Donor Rights Applies To', 'all_items', 'All Items', '#28a745', 10, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'specific_items', 'Specific Items', '#007bff', 20, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'digital_only', 'Digital Only', '#6f42c1', 30, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'physical_only', 'Physical Only', '#fd7e14', 40, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'metadata_only', 'Metadata Only', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_relationship', 'Provenance Relationship', 'donated', 'Donated', '#28a745', 10, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'deposited', 'Deposited', '#007bff', 20, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'loaned', 'Loaned', '#17a2b8', 30, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'purchased', 'Purchased', '#ffc107', 40, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'transferred', 'Transferred', '#6f42c1', 50, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'bequeathed', 'Bequeathed', '#fd7e14', 60, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'gifted', 'Gifted', '#20c997', 70, 1, NOW());

-- ============================================================================
-- EMBARGO TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_type', 'Embargo Type', 'full', 'Full Embargo', '#dc3545', 10, 1, NOW()),
('embargo_type', 'Embargo Type', 'metadata_only', 'Metadata Only', '#fd7e14', 20, 1, NOW()),
('embargo_type', 'Embargo Type', 'digital_object', 'Digital Object Only', '#ffc107', 30, 1, NOW()),
('embargo_type', 'Embargo Type', 'custom', 'Custom', '#6c757d', 40, 1, NOW()),
('embargo_type', 'Embargo Type', 'digital_only', 'Digital Only', '#17a2b8', 50, 1, NOW()),
('embargo_type', 'Embargo Type', 'metadata_hidden', 'Metadata Hidden', '#6f42c1', 60, 1, NOW()),
('embargo_type', 'Embargo Type', 'partial', 'Partial', '#ffc107', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_status', 'Embargo Status', 'active', 'Active', '#dc3545', 10, 1, NOW()),
('embargo_status', 'Embargo Status', 'expired', 'Expired', '#6c757d', 20, 1, NOW()),
('embargo_status', 'Embargo Status', 'lifted', 'Lifted', '#28a745', 30, 1, NOW()),
('embargo_status', 'Embargo Status', 'pending', 'Pending', '#ffc107', 40, 1, NOW()),
('embargo_status', 'Embargo Status', 'extended', 'Extended', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_audit_action', 'Embargo Audit Action', 'created', 'Created', '#28a745', 10, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'modified', 'Modified', '#007bff', 20, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'lifted', 'Lifted', '#28a745', 30, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'extended', 'Extended', '#ffc107', 40, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'exception_added', 'Exception Added', '#17a2b8', 50, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'exception_removed', 'Exception Removed', '#dc3545', 60, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'reviewed', 'Reviewed', '#6f42c1', 70, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'notification_sent', 'Notification Sent', '#20c997', 80, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'auto_released', 'Auto Released', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_exception_type', 'Embargo Exception Type', 'user', 'User', '#007bff', 10, 1, NOW()),
('embargo_exception_type', 'Embargo Exception Type', 'group', 'Group', '#28a745', 20, 1, NOW()),
('embargo_exception_type', 'Embargo Exception Type', 'ip_range', 'IP Range', '#6f42c1', 30, 1, NOW()),
('embargo_exception_type', 'Embargo Exception Type', 'repository', 'Repository', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_reason', 'Embargo Reason', 'donor_restriction', 'Donor Restriction', '#dc3545', 10, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'copyright', 'Copyright', '#fd7e14', 20, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'privacy', 'Privacy', '#e83e8c', 30, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'legal', 'Legal', '#343a40', 40, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'commercial', 'Commercial', '#ffc107', 50, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'research', 'Research', '#007bff', 60, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'cultural', 'Cultural', '#6f42c1', 70, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'security', 'Security', '#dc3545', 80, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'other', 'Other', '#6c757d', 90, 1, NOW());

-- ============================================================================
-- EXHIBITION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_type', 'Exhibition Type', 'permanent', 'Permanent', '#28a745', 10, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'temporary', 'Temporary', '#007bff', 20, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'traveling', 'Traveling', '#6f42c1', 30, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'online', 'Online', '#17a2b8', 40, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'pop_up', 'Pop-up', '#fd7e14', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_status', 'Exhibition Status', 'concept', 'Concept', '#6c757d', 10, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'planning', 'Planning', '#ffc107', 20, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'preparation', 'Preparation', '#17a2b8', 30, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'installation', 'Installation', '#007bff', 40, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'open', 'Open', '#28a745', 50, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'closing', 'Closing', '#fd7e14', 60, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'closed', 'Closed', '#343a40', 70, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'archived', 'Archived', '#6c757d', 80, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'canceled', 'Canceled', '#dc3545', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_checklist_type', 'Exhibition Checklist Type', 'planning', 'Planning', '#ffc107', 10, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'preparation', 'Preparation', '#17a2b8', 20, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'installation', 'Installation', '#007bff', 30, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'opening', 'Opening', '#28a745', 40, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'during', 'During', '#6f42c1', 50, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'closing', 'Closing', '#fd7e14', 60, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'deinstallation', 'Deinstallation', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_checklist_status', 'Exhibition Checklist Status', 'not_started', 'Not Started', '#6c757d', 10, 1, NOW()),
('exhibition_checklist_status', 'Exhibition Checklist Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('exhibition_checklist_status', 'Exhibition Checklist Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('exhibition_checklist_status', 'Exhibition Checklist Status', 'overdue', 'Overdue', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_event_type', 'Exhibition Event Type', 'opening', 'Opening', '#28a745', 10, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'closing', 'Closing', '#dc3545', 20, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'tour', 'Tour', '#007bff', 30, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'lecture', 'Lecture', '#6f42c1', 40, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'workshop', 'Workshop', '#fd7e14', 50, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'performance', 'Performance', '#e83e8c', 60, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'family', 'Family Event', '#20c997', 70, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'school', 'School Event', '#ffc107', 80, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'vip', 'VIP Event', '#343a40', 90, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'press', 'Press Event', '#17a2b8', 100, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'other', 'Other', '#6c757d', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_event_status', 'Exhibition Event Status', 'scheduled', 'Scheduled', '#ffc107', 10, 1, NOW()),
('exhibition_event_status', 'Exhibition Event Status', 'confirmed', 'Confirmed', '#28a745', 20, 1, NOW()),
('exhibition_event_status', 'Exhibition Event Status', 'canceled', 'Canceled', '#dc3545', 30, 1, NOW()),
('exhibition_event_status', 'Exhibition Event Status', 'completed', 'Completed', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_gallery_type', 'Exhibition Gallery Type', 'gallery', 'Gallery', '#007bff', 10, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'hall', 'Hall', '#28a745', 20, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'room', 'Room', '#6f42c1', 30, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'corridor', 'Corridor', '#fd7e14', 40, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'outdoor', 'Outdoor', '#28a745', 50, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'foyer', 'Foyer', '#17a2b8', 60, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'stairwell', 'Stairwell', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_media_type', 'Exhibition Media Type', 'image', 'Image', '#28a745', 10, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'video', 'Video', '#6f42c1', 20, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'audio', 'Audio', '#fd7e14', 30, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'document', 'Document', '#007bff', 40, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'floorplan', 'Floorplan', '#17a2b8', 50, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'poster', 'Poster', '#e83e8c', 60, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'press', 'Press', '#20c997', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_media_usage', 'Exhibition Media Usage', 'promotional', 'Promotional', '#28a745', 10, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'installation', 'Installation', '#007bff', 20, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'documentation', 'Documentation', '#6f42c1', 30, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'press', 'Press', '#17a2b8', 40, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'catalog', 'Catalog', '#fd7e14', 50, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'internal', 'Internal', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_object_status', 'Exhibition Object Status', 'proposed', 'Proposed', '#ffc107', 10, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'confirmed', 'Confirmed', '#28a745', 20, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'on_loan_request', 'On Loan Request', '#17a2b8', 30, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'installed', 'Installed', '#007bff', 40, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'removed', 'Removed', '#dc3545', 50, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'returned', 'Returned', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('security_level', 'Security Level', 'standard', 'Standard', '#28a745', 10, 1, NOW()),
('security_level', 'Security Level', 'enhanced', 'Enhanced', '#ffc107', 20, 1, NOW()),
('security_level', 'Security Level', 'maximum', 'Maximum', '#dc3545', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_section_type', 'Exhibition Section Type', 'gallery', 'Gallery', '#007bff', 10, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'room', 'Room', '#28a745', 20, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'alcove', 'Alcove', '#6f42c1', 30, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'corridor', 'Corridor', '#fd7e14', 40, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'outdoor', 'Outdoor', '#28a745', 50, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'virtual', 'Virtual', '#17a2b8', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_narrative_type', 'Exhibition Narrative Type', 'thematic', 'Thematic', '#007bff', 10, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'chronological', 'Chronological', '#28a745', 20, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'biographical', 'Biographical', '#6f42c1', 30, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'geographical', 'Geographical', '#fd7e14', 40, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'technique', 'Technique', '#17a2b8', 50, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'custom', 'Custom', '#6c757d', 60, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'general', 'General', '#ffc107', 70, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'guided_tour', 'Guided Tour', '#e83e8c', 80, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'self_guided', 'Self-Guided', '#20c997', 90, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'educational', 'Educational', '#343a40', 100, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'accessible', 'Accessible', '#28a745', 110, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'highlights', 'Highlights', '#dc3545', 120, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('reading_level', 'Reading Level', 'basic', 'Basic', '#28a745', 10, 1, NOW()),
('reading_level', 'Reading Level', 'intermediate', 'Intermediate', '#007bff', 20, 1, NOW()),
('reading_level', 'Reading Level', 'advanced', 'Advanced', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('target_audience', 'Target Audience', 'general', 'General', '#007bff', 10, 1, NOW()),
('target_audience', 'Target Audience', 'children', 'Children', '#ffc107', 20, 1, NOW()),
('target_audience', 'Target Audience', 'students', 'Students', '#17a2b8', 30, 1, NOW()),
('target_audience', 'Target Audience', 'specialists', 'Specialists', '#6f42c1', 40, 1, NOW()),
('target_audience', 'Target Audience', 'all', 'All', '#28a745', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('venue_type', 'Venue Type', 'internal', 'Internal', '#007bff', 10, 1, NOW()),
('venue_type', 'Venue Type', 'partner', 'Partner', '#28a745', 20, 1, NOW()),
('venue_type', 'Venue Type', 'external', 'External', '#fd7e14', 30, 1, NOW()),
('venue_type', 'Venue Type', 'online', 'Online', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('accessibility_rating', 'Accessibility Rating', 'none', 'None', '#dc3545', 10, 1, NOW()),
('accessibility_rating', 'Accessibility Rating', 'partial', 'Partial', '#ffc107', 20, 1, NOW()),
('accessibility_rating', 'Accessibility Rating', 'full', 'Full', '#28a745', 30, 1, NOW());

-- ============================================================================
-- GALLERY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('artist_type', 'Artist Type', 'individual', 'Individual', '#007bff', 10, 1, NOW()),
('artist_type', 'Artist Type', 'collective', 'Collective', '#28a745', 20, 1, NOW()),
('artist_type', 'Artist Type', 'studio', 'Studio', '#6f42c1', 30, 1, NOW()),
('artist_type', 'Artist Type', 'anonymous', 'Anonymous', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('bibliography_entry_type', 'Bibliography Entry Type', 'book', 'Book', '#007bff', 10, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'catalog', 'Catalog', '#28a745', 20, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'article', 'Article', '#6f42c1', 30, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'review', 'Review', '#fd7e14', 40, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'interview', 'Interview', '#17a2b8', 50, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'thesis', 'Thesis', '#e83e8c', 60, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'website', 'Website', '#20c997', 70, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'video', 'Video', '#ffc107', 80, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('gallery_exhibition_type', 'Gallery Exhibition Type', 'solo', 'Solo', '#007bff', 10, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'group', 'Group', '#28a745', 20, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'duo', 'Duo', '#6f42c1', 30, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'retrospective', 'Retrospective', '#fd7e14', 40, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'survey', 'Survey', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('facility_report_type', 'Facility Report Type', 'incoming', 'Incoming', '#28a745', 10, 1, NOW()),
('facility_report_type', 'Facility Report Type', 'outgoing', 'Outgoing', '#fd7e14', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('insurance_policy_type', 'Insurance Policy Type', 'all_risk', 'All Risk', '#28a745', 10, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'named_perils', 'Named Perils', '#007bff', 20, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'transit', 'Transit', '#6f42c1', 30, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'exhibition', 'Exhibition', '#fd7e14', 40, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'permanent_collection', 'Permanent Collection', '#17a2b8', 50, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'all_risks', 'All Risks', '#28a745', 60, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'blanket', 'Blanket', '#ffc107', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('gallery_loan_type', 'Gallery Loan Type', 'incoming', 'Incoming', '#28a745', 10, 1, NOW()),
('gallery_loan_type', 'Gallery Loan Type', 'outgoing', 'Outgoing', '#fd7e14', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('gallery_loan_status', 'Gallery Loan Status', 'inquiry', 'Inquiry', '#6c757d', 10, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'requested', 'Requested', '#ffc107', 20, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'agreed', 'Agreed', '#007bff', 40, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'in_transit_out', 'In Transit Out', '#17a2b8', 50, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'on_loan', 'On Loan', '#6f42c1', 60, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'in_transit_return', 'In Transit Return', '#fd7e14', 70, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'returned', 'Returned', '#343a40', 80, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'cancelled', 'Cancelled', '#dc3545', 90, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'declined', 'Declined', '#dc3545', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('valuation_type', 'Valuation Type', 'insurance', 'Insurance', '#dc3545', 10, 1, NOW()),
('valuation_type', 'Valuation Type', 'market', 'Market', '#28a745', 20, 1, NOW()),
('valuation_type', 'Valuation Type', 'replacement', 'Replacement', '#007bff', 30, 1, NOW()),
('valuation_type', 'Valuation Type', 'auction_estimate', 'Auction Estimate', '#ffc107', 40, 1, NOW()),
('valuation_type', 'Valuation Type', 'probate', 'Probate', '#6f42c1', 50, 1, NOW()),
('valuation_type', 'Valuation Type', 'donation', 'Donation', '#17a2b8', 60, 1, NOW());

-- ============================================================================
-- GETTY VOCABULARY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('getty_vocabulary', 'Getty Vocabulary', 'aat', 'Art & Architecture Thesaurus', '#007bff', 10, 1, NOW()),
('getty_vocabulary', 'Getty Vocabulary', 'tgn', 'Thesaurus of Geographic Names', '#28a745', 20, 1, NOW()),
('getty_vocabulary', 'Getty Vocabulary', 'ulan', 'Union List of Artist Names', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('getty_link_status', 'Getty Link Status', 'confirmed', 'Confirmed', '#28a745', 10, 1, NOW()),
('getty_link_status', 'Getty Link Status', 'suggested', 'Suggested', '#ffc107', 20, 1, NOW()),
('getty_link_status', 'Getty Link Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('getty_link_status', 'Getty Link Status', 'pending', 'Pending', '#6c757d', 40, 1, NOW());

-- ============================================================================
-- Show migration statistics
-- ============================================================================

SELECT 'Phase 2 Migration Complete' as status;
SELECT taxonomy, taxonomy_label, COUNT(*) as term_count
FROM ahg_dropdown
GROUP BY taxonomy, taxonomy_label
ORDER BY taxonomy_label;
