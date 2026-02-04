-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2C
-- Generated: 2026-02-04
--
-- Final part: NAZ, NMMZ, OAIS, Preservation, Privacy, Provenance,
-- Research, RIC, Rights, Object 3D, Numbering, and Backup types
-- ============================================================================

-- ============================================================================
-- NAZ (National Archives of Zimbabwe) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_closure_type', 'NAZ Closure Type', 'standard', 'Standard', '#007bff', 10, 1, NOW()),
('naz_closure_type', 'NAZ Closure Type', 'extended', 'Extended', '#fd7e14', 20, 1, NOW()),
('naz_closure_type', 'NAZ Closure Type', 'indefinite', 'Indefinite', '#dc3545', 30, 1, NOW()),
('naz_closure_type', 'NAZ Closure Type', 'ministerial', 'Ministerial', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_closure_status', 'NAZ Closure Status', 'active', 'Active', '#dc3545', 10, 1, NOW()),
('naz_closure_status', 'NAZ Closure Status', 'expired', 'Expired', '#6c757d', 20, 1, NOW()),
('naz_closure_status', 'NAZ Closure Status', 'extended', 'Extended', '#fd7e14', 30, 1, NOW()),
('naz_closure_status', 'NAZ Closure Status', 'released', 'Released', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_protection_type', 'NAZ Protection Type', 'cabinet', 'Cabinet', '#dc3545', 10, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'security', 'Security', '#fd7e14', 20, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'personal', 'Personal', '#ffc107', 30, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'legal', 'Legal', '#6f42c1', 40, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'commercial', 'Commercial', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_access_restriction', 'NAZ Access Restriction', 'open', 'Open', '#28a745', 10, 1, NOW()),
('naz_access_restriction', 'NAZ Access Restriction', 'restricted', 'Restricted', '#ffc107', 20, 1, NOW()),
('naz_access_restriction', 'NAZ Access Restriction', 'confidential', 'Confidential', '#fd7e14', 30, 1, NOW()),
('naz_access_restriction', 'NAZ Access Restriction', 'secret', 'Secret', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_classification', 'NAZ Classification', 'vital', 'Vital', '#dc3545', 10, 1, NOW()),
('naz_classification', 'NAZ Classification', 'important', 'Important', '#fd7e14', 20, 1, NOW()),
('naz_classification', 'NAZ Classification', 'useful', 'Useful', '#ffc107', 30, 1, NOW()),
('naz_classification', 'NAZ Classification', 'non-essential', 'Non-Essential', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('disposal_action', 'Disposal Action', 'destroy', 'Destroy', '#dc3545', 10, 1, NOW()),
('disposal_action', 'Disposal Action', 'transfer', 'Transfer', '#007bff', 20, 1, NOW()),
('disposal_action', 'Disposal Action', 'review', 'Review', '#ffc107', 30, 1, NOW()),
('disposal_action', 'Disposal Action', 'permanent', 'Permanent', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_schedule_status', 'NAZ Schedule Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'pending', 'Pending', '#ffc107', 20, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'superseded', 'Superseded', '#fd7e14', 40, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'archived', 'Archived', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_permit_type', 'NAZ Permit Type', 'general', 'General', '#28a745', 10, 1, NOW()),
('naz_permit_type', 'NAZ Permit Type', 'restricted', 'Restricted', '#ffc107', 20, 1, NOW()),
('naz_permit_type', 'NAZ Permit Type', 'special', 'Special', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_permit_status', 'NAZ Permit Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'active', 'Active', '#007bff', 40, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'expired', 'Expired', '#6c757d', 50, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'revoked', 'Revoked', '#343a40', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_researcher_type', 'NAZ Researcher Type', 'local', 'Local', '#28a745', 10, 1, NOW()),
('naz_researcher_type', 'NAZ Researcher Type', 'foreign', 'Foreign', '#007bff', 20, 1, NOW()),
('naz_researcher_type', 'NAZ Researcher Type', 'institutional', 'Institutional', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_researcher_status', 'NAZ Researcher Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('naz_researcher_status', 'NAZ Researcher Status', 'inactive', 'Inactive', '#6c757d', 20, 1, NOW()),
('naz_researcher_status', 'NAZ Researcher Status', 'suspended', 'Suspended', '#ffc107', 30, 1, NOW()),
('naz_researcher_status', 'NAZ Researcher Status', 'blacklisted', 'Blacklisted', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_transfer_status', 'NAZ Transfer Status', 'proposed', 'Proposed', '#6c757d', 10, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'scheduled', 'Scheduled', '#ffc107', 20, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'in_transit', 'In Transit', '#17a2b8', 30, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'received', 'Received', '#28a745', 40, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'accessioned', 'Accessioned', '#007bff', 50, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'rejected', 'Rejected', '#dc3545', 60, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'cancelled', 'Cancelled', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_transfer_type', 'NAZ Transfer Type', 'scheduled', 'Scheduled', '#007bff', 10, 1, NOW()),
('naz_transfer_type', 'NAZ Transfer Type', 'voluntary', 'Voluntary', '#28a745', 20, 1, NOW()),
('naz_transfer_type', 'NAZ Transfer Type', 'rescue', 'Rescue', '#dc3545', 30, 1, NOW()),
('naz_transfer_type', 'NAZ Transfer Type', 'donation', 'Donation', '#6f42c1', 40, 1, NOW());

-- ============================================================================
-- NMMZ (National Museums and Monuments of Zimbabwe) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('nmmz_condition_rating', 'NMMZ Condition Rating', 'excellent', 'Excellent', '#28a745', 10, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'good', 'Good', '#20c997', 20, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'fair', 'Fair', '#ffc107', 30, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'poor', 'Poor', '#fd7e14', 40, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'fragmentary', 'Fragmentary', '#dc3545', 50, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'critical', 'Critical', '#dc3545', 60, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'destroyed', 'Destroyed', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ownership_type', 'Ownership Type', 'state', 'State', '#007bff', 10, 1, NOW()),
('ownership_type', 'Ownership Type', 'museum', 'Museum', '#28a745', 20, 1, NOW()),
('ownership_type', 'Ownership Type', 'private', 'Private', '#fd7e14', 30, 1, NOW()),
('ownership_type', 'Ownership Type', 'communal', 'Communal', '#6f42c1', 40, 1, NOW()),
('ownership_type', 'Ownership Type', 'mixed', 'Mixed', '#ffc107', 50, 1, NOW()),
('ownership_type', 'Ownership Type', 'unknown', 'Unknown', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('antiquity_status', 'Antiquity Status', 'in_collection', 'In Collection', '#28a745', 10, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'on_loan', 'On Loan', '#007bff', 20, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'missing', 'Missing', '#dc3545', 30, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'repatriated', 'Repatriated', '#6f42c1', 40, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'destroyed', 'Destroyed', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('site_protection_status', 'Site Protection Status', 'protected', 'Protected', '#28a745', 10, 1, NOW()),
('site_protection_status', 'Site Protection Status', 'unprotected', 'Unprotected', '#ffc107', 20, 1, NOW()),
('site_protection_status', 'Site Protection Status', 'at_risk', 'At Risk', '#dc3545', 30, 1, NOW()),
('site_protection_status', 'Site Protection Status', 'destroyed', 'Destroyed', '#343a40', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_potential', 'Research Potential', 'high', 'High', '#28a745', 10, 1, NOW()),
('research_potential', 'Research Potential', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('research_potential', 'Research Potential', 'low', 'Low', '#fd7e14', 30, 1, NOW()),
('research_potential', 'Research Potential', 'exhausted', 'Exhausted', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('site_status', 'Site Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('site_status', 'Site Status', 'destroyed', 'Destroyed', '#dc3545', 20, 1, NOW()),
('site_status', 'Site Status', 'submerged', 'Submerged', '#17a2b8', 30, 1, NOW()),
('site_status', 'Site Status', 'built_over', 'Built Over', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('applicant_type', 'Applicant Type', 'individual', 'Individual', '#007bff', 10, 1, NOW()),
('applicant_type', 'Applicant Type', 'institution', 'Institution', '#28a745', 20, 1, NOW()),
('applicant_type', 'Applicant Type', 'dealer', 'Dealer', '#fd7e14', 30, 1, NOW()),
('applicant_type', 'Applicant Type', 'researcher', 'Researcher', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('export_purpose', 'Export Purpose', 'exhibition', 'Exhibition', '#6f42c1', 10, 1, NOW()),
('export_purpose', 'Export Purpose', 'research', 'Research', '#007bff', 20, 1, NOW()),
('export_purpose', 'Export Purpose', 'conservation', 'Conservation', '#17a2b8', 30, 1, NOW()),
('export_purpose', 'Export Purpose', 'sale', 'Sale', '#ffc107', 40, 1, NOW()),
('export_purpose', 'Export Purpose', 'personal', 'Personal', '#28a745', 50, 1, NOW()),
('export_purpose', 'Export Purpose', 'return', 'Return', '#fd7e14', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('export_permit_status', 'Export Permit Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'issued', 'Issued', '#007bff', 40, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'used', 'Used', '#6c757d', 50, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'expired', 'Expired', '#343a40', 60, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'cancelled', 'Cancelled', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('impact_level', 'Impact Level', 'none', 'None', '#28a745', 10, 1, NOW()),
('impact_level', 'Impact Level', 'low', 'Low', '#ffc107', 20, 1, NOW()),
('impact_level', 'Impact Level', 'moderate', 'Moderate', '#fd7e14', 30, 1, NOW()),
('impact_level', 'Impact Level', 'high', 'High', '#dc3545', 40, 1, NOW()),
('impact_level', 'Impact Level', 'severe', 'Severe', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hia_recommendation', 'HIA Recommendation', 'approve', 'Approve', '#28a745', 10, 1, NOW()),
('hia_recommendation', 'HIA Recommendation', 'approve_with_conditions', 'Approve with Conditions', '#ffc107', 20, 1, NOW()),
('hia_recommendation', 'HIA Recommendation', 'reject', 'Reject', '#dc3545', 30, 1, NOW()),
('hia_recommendation', 'HIA Recommendation', 'further_study', 'Further Study Required', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hia_status', 'HIA Status', 'submitted', 'Submitted', '#6c757d', 10, 1, NOW()),
('hia_status', 'HIA Status', 'under_review', 'Under Review', '#17a2b8', 20, 1, NOW()),
('hia_status', 'HIA Status', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('hia_status', 'HIA Status', 'rejected', 'Rejected', '#dc3545', 40, 1, NOW()),
('hia_status', 'HIA Status', 'expired', 'Expired', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('monument_legal_status', 'Monument Legal Status', 'gazetted', 'Gazetted', '#28a745', 10, 1, NOW()),
('monument_legal_status', 'Monument Legal Status', 'provisional', 'Provisional', '#ffc107', 20, 1, NOW()),
('monument_legal_status', 'Monument Legal Status', 'proposed', 'Proposed', '#17a2b8', 30, 1, NOW()),
('monument_legal_status', 'Monument Legal Status', 'delisted', 'Delisted', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('monument_protection_level', 'Monument Protection Level', 'national', 'National', '#dc3545', 10, 1, NOW()),
('monument_protection_level', 'Monument Protection Level', 'provincial', 'Provincial', '#fd7e14', 20, 1, NOW()),
('monument_protection_level', 'Monument Protection Level', 'local', 'Local', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('monument_status', 'Monument Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('monument_status', 'Monument Status', 'at_risk', 'At Risk', '#dc3545', 20, 1, NOW()),
('monument_status', 'Monument Status', 'destroyed', 'Destroyed', '#343a40', 30, 1, NOW()),
('monument_status', 'Monument Status', 'delisted', 'Delisted', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('world_heritage_status', 'World Heritage Status', 'inscribed', 'Inscribed', '#28a745', 10, 1, NOW()),
('world_heritage_status', 'World Heritage Status', 'tentative', 'Tentative', '#ffc107', 20, 1, NOW()),
('world_heritage_status', 'World Heritage Status', 'none', 'None', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('conservation_priority', 'Conservation Priority', 'high', 'High', '#dc3545', 10, 1, NOW()),
('conservation_priority', 'Conservation Priority', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('conservation_priority', 'Conservation Priority', 'low', 'Low', '#28a745', 30, 1, NOW());

-- ============================================================================
-- NUMBERING SCHEME TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('numbering_sector', 'Numbering Sector', 'archive', 'Archive', '#007bff', 10, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'library', 'Library', '#28a745', 20, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'museum', 'Museum', '#6f42c1', 30, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'gallery', 'Gallery', '#fd7e14', 40, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'dam', 'DAM', '#17a2b8', 50, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'all', 'All', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('sequence_reset', 'Sequence Reset', 'never', 'Never', '#28a745', 10, 1, NOW()),
('sequence_reset', 'Sequence Reset', 'yearly', 'Yearly', '#007bff', 20, 1, NOW()),
('sequence_reset', 'Sequence Reset', 'monthly', 'Monthly', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- OAIS (Open Archival Information System) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('checksum_type', 'Checksum Type', 'md5', 'MD5', '#6c757d', 10, 1, NOW()),
('checksum_type', 'Checksum Type', 'sha1', 'SHA-1', '#ffc107', 20, 1, NOW()),
('checksum_type', 'Checksum Type', 'sha256', 'SHA-256', '#28a745', 30, 1, NOW()),
('checksum_type', 'Checksum Type', 'sha512', 'SHA-512', '#007bff', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('package_type', 'Package Type', 'SIP', 'Submission Information Package', '#007bff', 10, 1, NOW()),
('package_type', 'Package Type', 'AIP', 'Archival Information Package', '#28a745', 20, 1, NOW()),
('package_type', 'Package Type', 'DIP', 'Dissemination Information Package', '#6f42c1', 30, 1, NOW()),
('package_type', 'Package Type', 'sip', 'SIP', '#007bff', 40, 1, NOW()),
('package_type', 'Package Type', 'aip', 'AIP', '#28a745', 50, 1, NOW()),
('package_type', 'Package Type', 'dip', 'DIP', '#6f42c1', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_level', 'Preservation Level', 'bit', 'Bit', '#6c757d', 10, 1, NOW()),
('preservation_level', 'Preservation Level', 'logical', 'Logical', '#007bff', 20, 1, NOW()),
('preservation_level', 'Preservation Level', 'semantic', 'Semantic', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('oais_package_status', 'OAIS Package Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'ingesting', 'Ingesting', '#17a2b8', 20, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'stored', 'Stored', '#28a745', 30, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'preserved', 'Preserved', '#007bff', 40, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'disseminated', 'Disseminated', '#6f42c1', 50, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'error', 'Error', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('package_content_type', 'Package Content Type', 'content', 'Content', '#007bff', 10, 1, NOW()),
('package_content_type', 'Package Content Type', 'metadata', 'Metadata', '#28a745', 20, 1, NOW()),
('package_content_type', 'Package Content Type', 'manifest', 'Manifest', '#6f42c1', 30, 1, NOW()),
('package_content_type', 'Package Content Type', 'signature', 'Signature', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('premis_event_outcome', 'PREMIS Event Outcome', 'success', 'Success', '#28a745', 10, 1, NOW()),
('premis_event_outcome', 'PREMIS Event Outcome', 'failure', 'Failure', '#dc3545', 20, 1, NOW()),
('premis_event_outcome', 'PREMIS Event Outcome', 'warning', 'Warning', '#ffc107', 30, 1, NOW()),
('premis_event_outcome', 'PREMIS Event Outcome', 'unknown', 'Unknown', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('premis_event_type', 'PREMIS Event Type', 'capture', 'Capture', '#007bff', 10, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'compression', 'Compression', '#28a745', 20, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'creation', 'Creation', '#6f42c1', 30, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'deaccession', 'Deaccession', '#dc3545', 40, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'decompression', 'Decompression', '#17a2b8', 50, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'decryption', 'Decryption', '#fd7e14', 60, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'deletion', 'Deletion', '#343a40', 70, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'digital_signature_validation', 'Digital Signature Validation', '#ffc107', 80, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'dissemination', 'Dissemination', '#e83e8c', 90, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'encryption', 'Encryption', '#20c997', 100, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'fixity_check', 'Fixity Check', '#28a745', 110, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'format_identification', 'Format Identification', '#007bff', 120, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'ingestion', 'Ingestion', '#6f42c1', 130, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'message_digest_calculation', 'Message Digest Calculation', '#17a2b8', 140, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'migration', 'Migration', '#fd7e14', 150, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'normalization', 'Normalization', '#ffc107', 160, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'replication', 'Replication', '#28a745', 170, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'validation', 'Validation', '#007bff', 180, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'virus_check', 'Virus Check', '#dc3545', 190, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_action_type', 'Preservation Action Type', 'migrate', 'Migrate', '#007bff', 10, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'normalize', 'Normalize', '#28a745', 20, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'emulate', 'Emulate', '#6f42c1', 30, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'preserve', 'Preserve', '#fd7e14', 40, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'none', 'None', '#6c757d', 50, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'monitor', 'Monitor', '#ffc107', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('pronom_risk_level', 'PRONOM Risk Level', 'low', 'Low', '#28a745', 10, 1, NOW()),
('pronom_risk_level', 'PRONOM Risk Level', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('pronom_risk_level', 'PRONOM Risk Level', 'high', 'High', '#fd7e14', 30, 1, NOW()),
('pronom_risk_level', 'PRONOM Risk Level', 'critical', 'Critical', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- OBJECT 3D TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('object_3d_audit_action', 'Object 3D Audit Action', 'upload', 'Upload', '#28a745', 10, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'update', 'Update', '#007bff', 20, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'delete', 'Delete', '#dc3545', 30, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'view', 'View', '#6c757d', 40, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'ar_view', 'AR View', '#6f42c1', 50, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'download', 'Download', '#17a2b8', 60, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'hotspot_add', 'Hotspot Add', '#ffc107', 70, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'hotspot_delete', 'Hotspot Delete', '#fd7e14', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hotspot_type', 'Hotspot Type', 'annotation', 'Annotation', '#007bff', 10, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'info', 'Information', '#28a745', 20, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'link', 'Link', '#6f42c1', 30, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'damage', 'Damage', '#dc3545', 40, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'detail', 'Detail', '#fd7e14', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('link_target', 'Link Target', '_self', 'Same Window', '#007bff', 10, 1, NOW()),
('link_target', 'Link Target', '_blank', 'New Window', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ar_placement', 'AR Placement', 'floor', 'Floor', '#007bff', 10, 1, NOW()),
('ar_placement', 'AR Placement', 'wall', 'Wall', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('model_format', 'Model Format', 'glb', 'GLB', '#28a745', 10, 1, NOW()),
('model_format', 'Model Format', 'gltf', 'GLTF', '#007bff', 20, 1, NOW()),
('model_format', 'Model Format', 'obj', 'OBJ', '#6f42c1', 30, 1, NOW()),
('model_format', 'Model Format', 'fbx', 'FBX', '#fd7e14', 40, 1, NOW()),
('model_format', 'Model Format', 'stl', 'STL', '#17a2b8', 50, 1, NOW()),
('model_format', 'Model Format', 'ply', 'PLY', '#ffc107', 60, 1, NOW()),
('model_format', 'Model Format', 'usdz', 'USDZ', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('texture_type', 'Texture Type', 'diffuse', 'Diffuse', '#007bff', 10, 1, NOW()),
('texture_type', 'Texture Type', 'normal', 'Normal', '#28a745', 20, 1, NOW()),
('texture_type', 'Texture Type', 'roughness', 'Roughness', '#6f42c1', 30, 1, NOW()),
('texture_type', 'Texture Type', 'metallic', 'Metallic', '#fd7e14', 40, 1, NOW()),
('texture_type', 'Texture Type', 'ao', 'Ambient Occlusion', '#17a2b8', 50, 1, NOW()),
('texture_type', 'Texture Type', 'emissive', 'Emissive', '#ffc107', 60, 1, NOW()),
('texture_type', 'Texture Type', 'environment', 'Environment', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_level', 'Access Level', 'view', 'View', '#28a745', 10, 1, NOW()),
('access_level', 'Access Level', 'download', 'Download', '#007bff', 20, 1, NOW()),
('access_level', 'Access Level', 'edit', 'Edit', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- PRESERVATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_type', 'Backup Type', 'database', 'Database', '#007bff', 10, 1, NOW()),
('backup_type', 'Backup Type', 'files', 'Files', '#28a745', 20, 1, NOW()),
('backup_type', 'Backup Type', 'full', 'Full', '#6f42c1', 30, 1, NOW()),
('backup_type', 'Backup Type', 'incremental', 'Incremental', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_verification_status', 'Backup Verification Status', 'valid', 'Valid', '#28a745', 10, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'invalid', 'Invalid', '#dc3545', 20, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'missing', 'Missing', '#343a40', 30, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'error', 'Error', '#fd7e14', 40, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'corrupted', 'Corrupted', '#dc3545', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('verification_status', 'Verification Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('verification_status', 'Verification Status', 'valid', 'Valid', '#28a745', 20, 1, NOW()),
('verification_status', 'Verification Status', 'invalid', 'Invalid', '#dc3545', 30, 1, NOW()),
('verification_status', 'Verification Status', 'error', 'Error', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('fixity_status', 'Fixity Status', 'pass', 'Pass', '#28a745', 10, 1, NOW()),
('fixity_status', 'Fixity Status', 'fail', 'Fail', '#dc3545', 20, 1, NOW()),
('fixity_status', 'Fixity Status', 'error', 'Error', '#fd7e14', 30, 1, NOW()),
('fixity_status', 'Fixity Status', 'missing', 'Missing', '#343a40', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_quality', 'Migration Quality', 'lossless', 'Lossless', '#28a745', 10, 1, NOW()),
('migration_quality', 'Migration Quality', 'minimal', 'Minimal Loss', '#ffc107', 20, 1, NOW()),
('migration_quality', 'Migration Quality', 'moderate', 'Moderate Loss', '#fd7e14', 30, 1, NOW()),
('migration_quality', 'Migration Quality', 'significant', 'Significant Loss', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_urgency', 'Migration Urgency', 'none', 'None', '#28a745', 10, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'low', 'Low', '#ffc107', 20, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'medium', 'Medium', '#fd7e14', 30, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'high', 'High', '#dc3545', 40, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'critical', 'Critical', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_scope', 'Migration Scope', 'all', 'All', '#6f42c1', 10, 1, NOW()),
('migration_scope', 'Migration Scope', 'repository', 'Repository', '#007bff', 20, 1, NOW()),
('migration_scope', 'Migration Scope', 'collection', 'Collection', '#28a745', 30, 1, NOW()),
('migration_scope', 'Migration Scope', 'custom', 'Custom', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_plan_status', 'Migration Plan Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'in_progress', 'In Progress', '#007bff', 30, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'completed', 'Completed', '#28a745', 40, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'failed', 'Failed', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('format_confidence', 'Format Confidence', 'low', 'Low', '#dc3545', 10, 1, NOW()),
('format_confidence', 'Format Confidence', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('format_confidence', 'Format Confidence', 'high', 'High', '#28a745', 30, 1, NOW()),
('format_confidence', 'Format Confidence', 'certain', 'Certain', '#007bff', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('package_format', 'Package Format', 'bagit', 'BagIt', '#007bff', 10, 1, NOW()),
('package_format', 'Package Format', 'zip', 'ZIP', '#28a745', 20, 1, NOW()),
('package_format', 'Package Format', 'tar', 'TAR', '#6f42c1', 30, 1, NOW()),
('package_format', 'Package Format', 'directory', 'Directory', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('policy_type', 'Policy Type', 'fixity', 'Fixity', '#007bff', 10, 1, NOW()),
('policy_type', 'Policy Type', 'format', 'Format', '#28a745', 20, 1, NOW()),
('policy_type', 'Policy Type', 'retention', 'Retention', '#6f42c1', 30, 1, NOW()),
('policy_type', 'Policy Type', 'replication', 'Replication', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('replication_operation', 'Replication Operation', 'sync', 'Sync', '#28a745', 10, 1, NOW()),
('replication_operation', 'Replication Operation', 'verify', 'Verify', '#007bff', 20, 1, NOW()),
('replication_operation', 'Replication Operation', 'restore', 'Restore', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('replication_target_type', 'Replication Target Type', 'local', 'Local', '#28a745', 10, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'sftp', 'SFTP', '#007bff', 20, 1, NOW()),
('replication_target_type', 'Replication Target Type', 's3', 'AWS S3', '#fd7e14', 30, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'azure', 'Azure Blob', '#17a2b8', 40, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'gcs', 'Google Cloud Storage', '#dc3545', 50, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'rsync', 'Rsync', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('virus_scan_status', 'Virus Scan Status', 'clean', 'Clean', '#28a745', 10, 1, NOW()),
('virus_scan_status', 'Virus Scan Status', 'infected', 'Infected', '#dc3545', 20, 1, NOW()),
('virus_scan_status', 'Virus Scan Status', 'error', 'Error', '#fd7e14', 30, 1, NOW()),
('virus_scan_status', 'Virus Scan Status', 'skipped', 'Skipped', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_schedule_type', 'Workflow Schedule Type', 'cron', 'Cron', '#007bff', 10, 1, NOW()),
('workflow_schedule_type', 'Workflow Schedule Type', 'interval', 'Interval', '#28a745', 20, 1, NOW()),
('workflow_schedule_type', 'Workflow Schedule Type', 'manual', 'Manual', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_workflow_type', 'Preservation Workflow Type', 'format_identification', 'Format Identification', '#007bff', 10, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'fixity_check', 'Fixity Check', '#28a745', 20, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'virus_scan', 'Virus Scan', '#dc3545', 30, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'format_conversion', 'Format Conversion', '#6f42c1', 40, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'backup_verification', 'Backup Verification', '#fd7e14', 50, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'replication', 'Replication', '#17a2b8', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_object_role', 'Preservation Object Role', 'payload', 'Payload', '#007bff', 10, 1, NOW()),
('preservation_object_role', 'Preservation Object Role', 'metadata', 'Metadata', '#28a745', 20, 1, NOW()),
('preservation_object_role', 'Preservation Object Role', 'manifest', 'Manifest', '#6f42c1', 30, 1, NOW()),
('preservation_object_role', 'Preservation Object Role', 'tagfile', 'Tag File', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('linking_agent_type', 'Linking Agent Type', 'user', 'User', '#007bff', 10, 1, NOW()),
('linking_agent_type', 'Linking Agent Type', 'system', 'System', '#6c757d', 20, 1, NOW()),
('linking_agent_type', 'Linking Agent Type', 'software', 'Software', '#28a745', 30, 1, NOW()),
('linking_agent_type', 'Linking Agent Type', 'organization', 'Organization', '#6f42c1', 40, 1, NOW());

-- ============================================================================
-- BACKUP TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_frequency', 'Backup Frequency', 'hourly', 'Hourly', '#dc3545', 10, 1, NOW()),
('backup_frequency', 'Backup Frequency', 'daily', 'Daily', '#fd7e14', 20, 1, NOW()),
('backup_frequency', 'Backup Frequency', 'weekly', 'Weekly', '#ffc107', 30, 1, NOW()),
('backup_frequency', 'Backup Frequency', 'monthly', 'Monthly', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_status', 'Backup Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('backup_status', 'Backup Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('backup_status', 'Backup Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('backup_status', 'Backup Status', 'failed', 'Failed', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- Show Phase 2C statistics
-- ============================================================================

SELECT 'Phase 2C Migration Complete' as status;
