-- =====================================================
-- NDPA - Nigeria Data Protection Act
-- Nigeria
-- Regulator: Nigeria Data Protection Commission (NDPC)
-- Effective: 14 June 2023
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'ndpc_registration', true,
      'dpia_threshold', 'high_risk',
      'id_types', JSON_ARRAY('nin', 'bvn', 'passport', 'voters_card', 'drivers_license'),
      'languages', JSON_ARRAY('en')
    )
WHERE `code` = 'ndpa';

-- =====================================================
-- Lawful Bases (NDPA Section 25)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('ndpa', 'consent', 'Consent', 'Data subject has given consent for one or more specific purposes', 'NDPA s.25(1)(a)', 1, 0, 1),
('ndpa', 'contract', 'Contractual Necessity', 'Processing necessary for performance of a contract', 'NDPA s.25(1)(b)', 0, 0, 2),
('ndpa', 'legal_obligation', 'Legal Obligation', 'Processing necessary for compliance with legal obligation', 'NDPA s.25(1)(c)', 0, 0, 3),
('ndpa', 'vital_interest', 'Vital Interests', 'Processing necessary to protect vital interests', 'NDPA s.25(1)(d)', 0, 0, 4),
('ndpa', 'public_interest', 'Public Interest', 'Processing necessary for task in public interest', 'NDPA s.25(1)(e)', 0, 0, 5),
('ndpa', 'legitimate_interest', 'Legitimate Interests', 'Processing necessary for legitimate interests of controller', 'NDPA s.25(1)(f)', 0, 1, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (NDPA Section 30 - Sensitive Data)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('ndpa', 'racial_ethnic', 'Racial or Ethnic Origin', 'Data revealing racial or ethnic origin', 'NDPA s.30', 1, 1),
('ndpa', 'religious', 'Religious Beliefs', 'Data revealing religious beliefs', 'NDPA s.30', 1, 2),
('ndpa', 'political', 'Political Opinions', 'Data revealing political opinions', 'NDPA s.30', 1, 3),
('ndpa', 'health', 'Health Data', 'Data concerning health status', 'NDPA s.30', 1, 4),
('ndpa', 'genetic', 'Genetic Data', 'Genetic data', 'NDPA s.30', 1, 5),
('ndpa', 'biometric', 'Biometric Data', 'Biometric data for unique identification', 'NDPA s.30', 1, 6),
('ndpa', 'sex_life', 'Sex Life or Orientation', 'Data concerning sex life or sexual orientation', 'NDPA s.30', 1, 7),
('ndpa', 'trade_union', 'Trade Union Membership', 'Trade union membership', 'NDPA s.30', 1, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (NDPA Data Subject Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('ndpa', 'access', 'Right of Access', 'Right to obtain confirmation and access personal data', 'NDPA s.34', 30, 0, 1),
('ndpa', 'rectification', 'Right to Rectification', 'Right to have inaccurate data corrected', 'NDPA s.35', 30, 0, 2),
('ndpa', 'erasure', 'Right to Erasure', 'Right to have personal data erased', 'NDPA s.36', 30, 0, 3),
('ndpa', 'restriction', 'Right to Restriction', 'Right to restrict processing', 'NDPA s.37', 30, 0, 4),
('ndpa', 'portability', 'Right to Portability', 'Right to receive data in structured format', 'NDPA s.38', 30, 0, 5),
('ndpa', 'objection', 'Right to Object', 'Right to object to processing', 'NDPA s.39', 30, 0, 6),
('ndpa', 'withdraw_consent', 'Withdraw Consent', 'Right to withdraw consent', 'NDPA s.25(2)', 30, 0, 7)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
('ndpa', 'dsar', 'NDPA_DSAR_001', '30-Day Response', 'Response within 30 days of request', 'date_check', 'due_date', 'Response deadline exceeded', 'NDPA s.34(3)', 'error', 1),
('ndpa', 'dsar', 'NDPA_DSAR_002', 'Free First Copy', 'First copy provided free of charge', 'value_check', 'fee_required', 'First copy must be free', 'NDPA s.34(5)', 'warning', 2),

('ndpa', 'breach', 'NDPA_BRE_001', '72-Hour NDPC Notification', 'NDPC must be notified within 72 hours', 'value_check', 'regulator_notified', 'NDPC notification overdue', 'NDPA s.40(1)', 'error', 1),
('ndpa', 'breach', 'NDPA_BRE_002', 'Subject Notification', 'Data subjects notified without undue delay', 'required_field', 'subjects_notified', 'Data subject notification required', 'NDPA s.40(2)', 'error', 2),
('ndpa', 'breach', 'NDPA_BRE_003', 'Breach Documentation', 'All breaches must be documented', 'required_field', 'description', 'Breach documentation required', 'NDPA s.40(4)', 'error', 3),

('ndpa', 'ropa', 'NDPA_ROPA_001', 'Lawful Basis Required', 'Processing must have documented lawful basis', 'required_field', 'lawful_basis', 'Lawful basis required', 'NDPA s.25', 'error', 1),
('ndpa', 'ropa', 'NDPA_ROPA_002', 'Purpose Specification', 'Purposes must be specified and documented', 'required_field', 'purpose', 'Purpose must be specified', 'NDPA s.24', 'error', 2),
('ndpa', 'ropa', 'NDPA_ROPA_003', 'Security Measures', 'Appropriate security safeguards required', 'required_field', 'security_measures', 'Security measures required', 'NDPA s.29', 'error', 3),
('ndpa', 'ropa', 'NDPA_ROPA_004', 'DPIA Requirement', 'DPIA required for high-risk processing', 'required_field', 'dpia_required', 'DPIA assessment required', 'NDPA s.31', 'warning', 4),

('ndpa', 'consent', 'NDPA_CON_001', 'Informed Consent', 'Consent must be freely given, specific, informed', 'required_field', 'consent_given', 'Valid consent required', 'NDPA s.25(1)(a)', 'error', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (Nigeria-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personal files', '6 years after termination', 6, 'NDPA, Labour Act', 'destroy', 'ndpa'),
('Financial Records', 'Financial and tax records', '6 years', 6, 'CAMA, FIRS regulations', 'destroy', 'ndpa'),
('Customer Records', 'Customer transaction data', '6 years', 6, 'NDPA, Limitation laws', 'anonymize', 'ndpa'),
('DSAR Records', 'Data subject request documentation', '3 years', 3, 'NDPA', 'destroy', 'ndpa'),
('Breach Records', 'Data breach documentation', '5 years', 5, 'NDPA s.40(4)', 'archive', 'ndpa'),
('KYC Records', 'Know Your Customer documentation', '5 years after relationship', 5, 'CBN regulations, NDPA', 'destroy', 'ndpa')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
