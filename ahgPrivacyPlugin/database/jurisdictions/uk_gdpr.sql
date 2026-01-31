-- =====================================================
-- UK GDPR - UK General Data Protection Regulation
-- United Kingdom (post-Brexit)
-- Regulator: Information Commissioner's Office (ICO)
-- Effective: 1 January 2021
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'dpo_required', true,
      'ico_registration', true,
      'uk_adequacy', true,
      'id_types', JSON_ARRAY('uk_passport', 'uk_drivers_license', 'national_insurance', 'brp'),
      'languages', JSON_ARRAY('en', 'cy')
    )
WHERE `code` = 'uk_gdpr';

-- =====================================================
-- Lawful Bases (UK GDPR Article 6 - same as EU GDPR)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('uk_gdpr', 'consent', 'Consent', 'The data subject has given consent for one or more specific purposes', 'UK GDPR Art 6(1)(a)', 1, 0, 1),
('uk_gdpr', 'contract', 'Contract', 'Processing is necessary for performance of a contract', 'UK GDPR Art 6(1)(b)', 0, 0, 2),
('uk_gdpr', 'legal_obligation', 'Legal Obligation', 'Processing is necessary for compliance with a legal obligation', 'UK GDPR Art 6(1)(c)', 0, 0, 3),
('uk_gdpr', 'vital_interest', 'Vital Interests', 'Processing is necessary to protect vital interests', 'UK GDPR Art 6(1)(d)', 0, 0, 4),
('uk_gdpr', 'public_task', 'Public Task', 'Processing is necessary for a task in the public interest or official authority', 'UK GDPR Art 6(1)(e)', 0, 0, 5),
('uk_gdpr', 'legitimate_interest', 'Legitimate Interests', 'Processing is necessary for legitimate interests', 'UK GDPR Art 6(1)(f)', 0, 1, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (UK GDPR Article 9)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('uk_gdpr', 'racial_ethnic', 'Racial or Ethnic Origin', 'Data revealing racial or ethnic origin', 'UK GDPR Art 9(1)', 1, 1),
('uk_gdpr', 'political', 'Political Opinions', 'Data revealing political opinions', 'UK GDPR Art 9(1)', 1, 2),
('uk_gdpr', 'religious', 'Religious or Philosophical Beliefs', 'Data revealing religious or philosophical beliefs', 'UK GDPR Art 9(1)', 1, 3),
('uk_gdpr', 'trade_union', 'Trade Union Membership', 'Data revealing trade union membership', 'UK GDPR Art 9(1)', 1, 4),
('uk_gdpr', 'genetic', 'Genetic Data', 'Genetic data for uniquely identifying a person', 'UK GDPR Art 9(1)', 1, 5),
('uk_gdpr', 'biometric', 'Biometric Data', 'Biometric data for unique identification', 'UK GDPR Art 9(1)', 1, 6),
('uk_gdpr', 'health', 'Health Data', 'Data concerning health', 'UK GDPR Art 9(1)', 1, 7),
('uk_gdpr', 'sex_life', 'Sex Life or Sexual Orientation', 'Data concerning sex life or sexual orientation', 'UK GDPR Art 9(1)', 1, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (UK GDPR Data Subject Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('uk_gdpr', 'access', 'Subject Access Request (SAR)', 'Right to obtain confirmation and access to personal data', 'UK GDPR Art 15', 30, 0, 1),
('uk_gdpr', 'rectification', 'Right to Rectification', 'Right to have inaccurate personal data corrected', 'UK GDPR Art 16', 30, 0, 2),
('uk_gdpr', 'erasure', 'Right to Erasure', 'Right to have personal data erased', 'UK GDPR Art 17', 30, 0, 3),
('uk_gdpr', 'restriction', 'Right to Restriction', 'Right to restrict processing', 'UK GDPR Art 18', 30, 0, 4),
('uk_gdpr', 'portability', 'Right to Data Portability', 'Right to receive data in portable format', 'UK GDPR Art 20', 30, 0, 5),
('uk_gdpr', 'objection', 'Right to Object', 'Right to object to processing', 'UK GDPR Art 21', 30, 0, 6),
('uk_gdpr', 'automated_decision', 'Automated Decision-Making Rights', 'Rights related to automated processing including profiling', 'UK GDPR Art 22', 30, 0, 7),
('uk_gdpr', 'withdraw_consent', 'Withdraw Consent', 'Right to withdraw consent', 'UK GDPR Art 7(3)', 30, 0, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules (UK-specific additions)
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
-- DSAR Rules
('uk_gdpr', 'dsar', 'UK_DSAR_001', 'One Month Response', 'Response within one calendar month', 'date_check', 'due_date', 'Response deadline exceeded', 'UK GDPR Art 12(3)', 'error', 1),
('uk_gdpr', 'dsar', 'UK_DSAR_002', 'ID Verification', 'Reasonable steps to verify identity', 'required_field', 'is_verified', 'Identity must be verified', 'UK GDPR Art 12(6)', 'warning', 2),

-- Breach Rules (ICO notification)
('uk_gdpr', 'breach', 'UK_BRE_001', 'ICO 72-Hour Notification', 'ICO must be notified within 72 hours where feasible', 'value_check', 'regulator_notified', 'ICO notification overdue', 'UK GDPR Art 33(1)', 'error', 1),
('uk_gdpr', 'breach', 'UK_BRE_002', 'Subject Communication', 'Communicate to data subjects without undue delay if high risk', 'required_field', 'subjects_notified', 'Data subjects must be notified', 'UK GDPR Art 34(1)', 'error', 2),

-- ROPA Rules
('uk_gdpr', 'ropa', 'UK_ROPA_001', 'Lawful Basis', 'Processing must have valid lawful basis', 'required_field', 'lawful_basis', 'Lawful basis required', 'UK GDPR Art 6', 'error', 1),
('uk_gdpr', 'ropa', 'UK_ROPA_002', 'Purpose Specification', 'Purpose must be specified', 'required_field', 'purpose', 'Purpose must be documented', 'UK GDPR Art 5(1)(b)', 'error', 2),
('uk_gdpr', 'ropa', 'UK_ROPA_003', 'International Transfers', 'UK adequacy or safeguards for transfers', 'required_field', 'transfers', 'Transfer mechanism must be documented', 'UK GDPR Art 46', 'warning', 3),

-- ICO Registration
('uk_gdpr', 'consent', 'UK_CON_001', 'Consent Standards', 'Consent must meet UK GDPR standards', 'required_field', 'consent_given', 'Valid consent required', 'UK GDPR Art 7', 'error', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (UK-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee records', '6 years after termination', 6, 'UK GDPR, Employment law', 'destroy', 'uk_gdpr'),
('Financial Records', 'Financial and tax records', '6 years', 6, 'Companies Act, HMRC', 'destroy', 'uk_gdpr'),
('Customer Records', 'Customer data', '6 years after last transaction', 6, 'UK GDPR, Limitation Act', 'anonymize', 'uk_gdpr'),
('DSAR Records', 'Subject access request records', '3 years', 3, 'UK GDPR', 'destroy', 'uk_gdpr'),
('Breach Records', 'Data breach documentation', '5 years', 5, 'UK GDPR Art 33(5)', 'archive', 'uk_gdpr'),
('CCTV Footage', 'Surveillance recordings', '30 days', 0, 'ICO CCTV Code', 'destroy', 'uk_gdpr'),
('Website Cookies', 'Cookie consent records', '12 months', 1, 'PECR', 'destroy', 'uk_gdpr')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
