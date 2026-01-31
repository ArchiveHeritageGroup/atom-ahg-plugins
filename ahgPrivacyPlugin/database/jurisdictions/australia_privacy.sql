-- =====================================================
-- Privacy Act 1988 - Australian Privacy Principles (APPs)
-- Australia
-- Regulator: Office of the Australian Information Commissioner (OAIC)
-- Effective: 12 March 2014 (APP amendments)
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'notifiable_data_breaches', true,
      'cdr_enabled', true,
      'id_types', JSON_ARRAY('tfn', 'medicare', 'passport', 'drivers_license'),
      'languages', JSON_ARRAY('en')
    )
WHERE `code` = 'australia_privacy';

-- =====================================================
-- Lawful Bases (Australian Privacy Principles)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('australia_privacy', 'consent', 'Consent', 'Individual has consented to the collection', 'APP 3.3', 1, 0, 1),
('australia_privacy', 'reasonably_necessary', 'Reasonably Necessary', 'Collection is reasonably necessary for entity functions', 'APP 3.2', 0, 0, 2),
('australia_privacy', 'directly_related', 'Directly Related Purpose', 'Use for purpose directly related to primary purpose', 'APP 6.2(a)', 0, 0, 3),
('australia_privacy', 'expected', 'Reasonable Expectation', 'Individual would reasonably expect secondary use', 'APP 6.2(b)', 0, 1, 4),
('australia_privacy', 'required_by_law', 'Required by Law', 'Collection required or authorised by law', 'APP 3.4(a)', 0, 0, 5),
('australia_privacy', 'enforcement', 'Enforcement Related', 'Enforcement related activity by enforcement body', 'APP 3.4(d)', 0, 0, 6),
('australia_privacy', 'health_safety', 'Health or Safety', 'Necessary to prevent serious threat to life, health or safety', 'APP 6.2(c)', 0, 0, 7)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (Sensitive Information - APP 3.3)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('australia_privacy', 'racial_ethnic', 'Racial or Ethnic Origin', 'Information about racial or ethnic origin', 'Privacy Act s.6', 1, 1),
('australia_privacy', 'political', 'Political Opinions', 'Political opinions or membership of political association', 'Privacy Act s.6', 1, 2),
('australia_privacy', 'religious', 'Religious Beliefs', 'Religious beliefs or affiliations', 'Privacy Act s.6', 1, 3),
('australia_privacy', 'philosophical', 'Philosophical Beliefs', 'Philosophical beliefs', 'Privacy Act s.6', 1, 4),
('australia_privacy', 'trade_union', 'Trade Union Membership', 'Membership of professional or trade association/union', 'Privacy Act s.6', 1, 5),
('australia_privacy', 'sexual', 'Sexual Orientation', 'Sexual orientation or practices', 'Privacy Act s.6', 1, 6),
('australia_privacy', 'criminal', 'Criminal Record', 'Criminal record of an individual', 'Privacy Act s.6', 1, 7),
('australia_privacy', 'health', 'Health Information', 'Health information including genetic information', 'Privacy Act s.6', 1, 8),
('australia_privacy', 'biometric', 'Biometric Information', 'Biometric information for automated identification', 'Privacy Act s.6', 1, 9),
('australia_privacy', 'biometric_template', 'Biometric Templates', 'Biometric templates for automated identification', 'Privacy Act s.6', 1, 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (APP Individual Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('australia_privacy', 'access', 'Access Request', 'Right to access personal information', 'APP 12', 30, 1, 1),
('australia_privacy', 'correction', 'Correction Request', 'Right to correct personal information', 'APP 13', 30, 0, 2),
('australia_privacy', 'complaint', 'Privacy Complaint', 'Right to complain about handling of personal information', 'APP 1.4', 30, 0, 3),
('australia_privacy', 'anonymity', 'Anonymity Request', 'Option to not identify or use pseudonym', 'APP 2', 30, 0, 4),
('australia_privacy', 'opt_out_marketing', 'Opt-Out of Marketing', 'Right to opt out of direct marketing', 'APP 7', 30, 0, 5),
('australia_privacy', 'source_disclosure', 'Source Disclosure', 'Right to know source of indirect collection', 'APP 5.2', 30, 0, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
-- DSAR Rules
('australia_privacy', 'dsar', 'AU_DSAR_001', '30-Day Response', 'Access requests must be responded to within 30 days', 'date_check', 'due_date', 'Response deadline exceeded (30 days)', 'APP 12.4', 'error', 1),
('australia_privacy', 'dsar', 'AU_DSAR_002', 'Reasonable Fee', 'Fee must not be excessive and not apply to correction', 'value_check', 'fee_required', 'Fee must be reasonable and disclosed', 'APP 12.8', 'warning', 2),
('australia_privacy', 'dsar', 'AU_DSAR_003', 'Written Refusal', 'Written reasons must be given if access refused', 'required_field', 'refusal_reason', 'Written reasons required for refusal', 'APP 12.8', 'error', 3),

-- Breach Rules (Notifiable Data Breaches scheme)
('australia_privacy', 'breach', 'AU_BRE_001', 'OAIC Notification', 'Eligible data breach must be notified to OAIC', 'value_check', 'regulator_notified', 'OAIC notification required', 'Part IIIC s.26WK', 'error', 1),
('australia_privacy', 'breach', 'AU_BRE_002', 'Individual Notification', 'Affected individuals must be notified', 'required_field', 'subjects_notified', 'Individuals must be notified', 'Part IIIC s.26WL', 'error', 2),
('australia_privacy', 'breach', 'AU_BRE_003', '30-Day Assessment', 'Assessment must be completed within 30 days', 'date_check', 'assessment_deadline', 'Assessment must be completed within 30 days', 'Part IIIC s.26WH', 'error', 3),
('australia_privacy', 'breach', 'AU_BRE_004', 'Breach Documentation', 'Records must be maintained of all breaches', 'required_field', 'description', 'Breach must be documented', 'Part IIIC', 'error', 4),

-- ROPA/APP Rules
('australia_privacy', 'ropa', 'AU_ROPA_001', 'APP Privacy Policy', 'Must maintain clear and current APP privacy policy', 'required_field', 'privacy_policy', 'APP privacy policy required', 'APP 1.3', 'error', 1),
('australia_privacy', 'ropa', 'AU_ROPA_002', 'Collection Notice', 'Must notify individual of collection matters', 'required_field', 'collection_notice', 'Collection notice required', 'APP 5', 'error', 2),
('australia_privacy', 'ropa', 'AU_ROPA_003', 'Purpose Limitation', 'Purpose of collection must be specified', 'required_field', 'purpose', 'Purpose must be specified', 'APP 3.2', 'error', 3),
('australia_privacy', 'ropa', 'AU_ROPA_004', 'Data Quality', 'Personal information must be accurate, complete and up to date', 'required_field', 'data_quality', 'Data quality measures required', 'APP 10', 'warning', 4),
('australia_privacy', 'ropa', 'AU_ROPA_005', 'Security Measures', 'Reasonable steps to protect from misuse, interference, loss', 'required_field', 'security_measures', 'Security measures required', 'APP 11', 'error', 5),
('australia_privacy', 'ropa', 'AU_ROPA_006', 'Cross-Border Disclosure', 'Reasonable steps before cross-border disclosure', 'required_field', 'transfers', 'Cross-border disclosure safeguards required', 'APP 8', 'warning', 6),

-- Consent Rules
('australia_privacy', 'consent', 'AU_CON_001', 'Informed Consent', 'Consent must be voluntary, informed, specific, and current', 'required_field', 'consent_given', 'Valid consent required', 'APP 3.3', 'error', 1),
('australia_privacy', 'consent', 'AU_CON_002', 'Sensitive Info Consent', 'Express consent required for sensitive information', 'required_field', 'explicit_consent', 'Express consent required for sensitive info', 'APP 3.3(a)', 'error', 2)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (Australia-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personnel records', '7 years after termination', 7, 'Fair Work Act, Privacy Act', 'destroy', 'australia_privacy'),
('Financial Records', 'Financial and tax records', '7 years', 7, 'Taxation Administration Act', 'destroy', 'australia_privacy'),
('Customer Records', 'Customer personal information', '7 years after last contact', 7, 'Privacy Act, Limitation periods', 'anonymize', 'australia_privacy'),
('Access Request Records', 'APP access request documentation', '3 years', 3, 'Privacy Act', 'destroy', 'australia_privacy'),
('Breach Records', 'Notifiable data breach documentation', '5 years', 5, 'Privacy Act Part IIIC', 'archive', 'australia_privacy'),
('Health Records', 'Patient health information', 'Various by state', 7, 'State health records legislation', 'archive', 'australia_privacy'),
('CCTV Footage', 'Video surveillance recordings', '30 days unless incident', 0, 'Privacy Act APPs', 'destroy', 'australia_privacy'),
('Direct Marketing Records', 'Marketing opt-out preferences', 'Until relationship ends', 0, 'Privacy Act APP 7', 'destroy', 'australia_privacy')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
