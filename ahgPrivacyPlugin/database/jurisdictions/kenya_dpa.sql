-- =====================================================
-- Kenya DPA - Data Protection Act 2019
-- Kenya
-- Regulator: Office of the Data Protection Commissioner (ODPC)
-- Effective: 25 November 2019
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'odpc_registration', true,
      'id_types', JSON_ARRAY('national_id', 'passport', 'alien_id', 'kra_pin'),
      'languages', JSON_ARRAY('en', 'sw')
    )
WHERE `code` = 'kenya_dpa';

-- =====================================================
-- Lawful Bases (Kenya DPA Section 30)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('kenya_dpa', 'consent', 'Consent', 'Data subject has consented to the processing', 'Kenya DPA s.30(1)(a)', 1, 0, 1),
('kenya_dpa', 'contract', 'Contractual Necessity', 'Processing necessary for contract performance', 'Kenya DPA s.30(1)(b)', 0, 0, 2),
('kenya_dpa', 'legal_obligation', 'Legal Obligation', 'Processing necessary for legal compliance', 'Kenya DPA s.30(1)(c)', 0, 0, 3),
('kenya_dpa', 'vital_interest', 'Vital Interests', 'Processing necessary to protect vital interests', 'Kenya DPA s.30(1)(d)', 0, 0, 4),
('kenya_dpa', 'public_interest', 'Public Interest', 'Processing necessary for public interest task', 'Kenya DPA s.30(1)(e)', 0, 0, 5),
('kenya_dpa', 'legitimate_interest', 'Legitimate Interests', 'Processing necessary for legitimate interests', 'Kenya DPA s.30(1)(f)', 0, 1, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (Kenya DPA - Sensitive Data)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('kenya_dpa', 'racial_ethnic', 'Race or Ethnic Origin', 'Data revealing racial or ethnic origin', 'Kenya DPA s.44', 1, 1),
('kenya_dpa', 'health', 'Health Data', 'Data concerning health', 'Kenya DPA s.44', 1, 2),
('kenya_dpa', 'genetic', 'Genetic Data', 'Genetic data', 'Kenya DPA s.44', 1, 3),
('kenya_dpa', 'biometric', 'Biometric Data', 'Biometric data for identification', 'Kenya DPA s.44', 1, 4),
('kenya_dpa', 'sex_life', 'Sex Life', 'Data concerning sex life', 'Kenya DPA s.44', 1, 5),
('kenya_dpa', 'children', 'Children Data', 'Personal data of children', 'Kenya DPA s.33', 1, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (Kenya DPA Data Subject Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('kenya_dpa', 'access', 'Right of Access', 'Right to access personal data', 'Kenya DPA s.26', 30, 1, 1),
('kenya_dpa', 'rectification', 'Right to Rectification', 'Right to correct inaccurate data', 'Kenya DPA s.27', 30, 0, 2),
('kenya_dpa', 'erasure', 'Right to Erasure', 'Right to deletion of data', 'Kenya DPA s.28', 30, 0, 3),
('kenya_dpa', 'restriction', 'Right to Restriction', 'Right to restrict processing', 'Kenya DPA s.29', 30, 0, 4),
('kenya_dpa', 'portability', 'Right to Portability', 'Right to data portability', 'Kenya DPA s.35', 30, 0, 5),
('kenya_dpa', 'objection', 'Right to Object', 'Right to object to processing', 'Kenya DPA s.34', 30, 0, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
('kenya_dpa', 'dsar', 'KE_DSAR_001', '30-Day Response', 'Response within 30 days', 'date_check', 'due_date', 'Response deadline exceeded', 'Kenya DPA s.26(3)', 'error', 1),

('kenya_dpa', 'breach', 'KE_BRE_001', '72-Hour ODPC Notification', 'ODPC must be notified within 72 hours', 'value_check', 'regulator_notified', 'ODPC notification overdue', 'Kenya DPA s.43(1)', 'error', 1),
('kenya_dpa', 'breach', 'KE_BRE_002', 'Subject Notification', 'Data subjects must be notified', 'required_field', 'subjects_notified', 'Data subject notification required', 'Kenya DPA s.43(4)', 'error', 2),

('kenya_dpa', 'ropa', 'KE_ROPA_001', 'Lawful Basis Required', 'Processing must have lawful basis', 'required_field', 'lawful_basis', 'Lawful basis required', 'Kenya DPA s.30', 'error', 1),
('kenya_dpa', 'ropa', 'KE_ROPA_002', 'Security Measures', 'Security measures required', 'required_field', 'security_measures', 'Security measures required', 'Kenya DPA s.41', 'error', 2),

('kenya_dpa', 'consent', 'KE_CON_001', 'Valid Consent', 'Consent must be freely given, specific, informed', 'required_field', 'consent_given', 'Valid consent required', 'Kenya DPA s.32', 'error', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (Kenya-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee records', '7 years after termination', 7, 'Kenya DPA, Employment Act', 'destroy', 'kenya_dpa'),
('Financial Records', 'Financial records', '7 years', 7, 'Companies Act, KRA', 'destroy', 'kenya_dpa'),
('Customer Records', 'Customer data', '7 years', 7, 'Kenya DPA', 'anonymize', 'kenya_dpa'),
('Breach Records', 'Data breach documentation', '5 years', 5, 'Kenya DPA', 'archive', 'kenya_dpa')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
