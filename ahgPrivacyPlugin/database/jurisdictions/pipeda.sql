-- =====================================================
-- PIPEDA - Personal Information Protection and Electronic Documents Act
-- Canada
-- Regulator: Office of the Privacy Commissioner of Canada
-- Effective: 1 January 2001
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'casl_enabled', true,
      'provincial_acts', JSON_ARRAY('PIPA_AB', 'PIPA_BC', 'LPRPDE_QC'),
      'id_types', JSON_ARRAY('sin', 'passport', 'drivers_license', 'health_card'),
      'languages', JSON_ARRAY('en', 'fr')
    )
WHERE `code` = 'pipeda';

-- =====================================================
-- Lawful Bases (PIPEDA Fair Information Principles)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('pipeda', 'consent', 'Consent', 'Knowledge and consent of the individual', 'PIPEDA Principle 3', 1, 0, 1),
('pipeda', 'implied_consent', 'Implied Consent', 'Consent is implied where purpose is obvious and individual provides information', 'PIPEDA s.6.1', 0, 0, 2),
('pipeda', 'opt_out_consent', 'Opt-Out Consent', 'Consent with option to withdraw for non-sensitive data', 'PIPEDA s.6.1', 0, 0, 3),
('pipeda', 'legitimate_business', 'Legitimate Business Activity', 'Processing for legitimate business activities where consent inappropriate', 'PIPEDA s.7', 0, 1, 4),
('pipeda', 'legal_requirement', 'Legal Requirement', 'Required by law or for legal proceedings', 'PIPEDA s.7', 0, 0, 5),
('pipeda', 'emergency', 'Emergency', 'Clearly in the interest of the individual and consent cannot be obtained', 'PIPEDA s.7(1)(a)', 0, 0, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (PIPEDA Sensitive Information)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('pipeda', 'health', 'Health Information', 'Medical and health-related personal information', 'PIPEDA Principle 4.3.4', 1, 1),
('pipeda', 'financial', 'Financial Information', 'Financial and credit information', 'PIPEDA Principle 4.3.4', 1, 2),
('pipeda', 'ethnic_origin', 'Ethnic Origin', 'Information about ethnic or racial origin', 'PIPEDA Principle 4.3.4', 1, 3),
('pipeda', 'religious', 'Religious Beliefs', 'Religious or philosophical beliefs', 'PIPEDA Principle 4.3.4', 1, 4),
('pipeda', 'political', 'Political Opinions', 'Political opinions or affiliations', 'PIPEDA Principle 4.3.4', 1, 5),
('pipeda', 'sexual', 'Sexual Orientation', 'Information about sexual orientation or practices', 'PIPEDA Principle 4.3.4', 1, 6),
('pipeda', 'criminal', 'Criminal History', 'Criminal record or history', 'PIPEDA Principle 4.3.4', 1, 7),
('pipeda', 'biometric', 'Biometric Data', 'Biometric information for identification', 'PIPEDA Principle 4.3.4', 1, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (PIPEDA Individual Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('pipeda', 'access', 'Access Request', 'Right to access personal information held', 'PIPEDA s.8', 30, 1, 1),
('pipeda', 'correction', 'Correction Request', 'Right to have inaccurate information corrected', 'PIPEDA Principle 4.9.5', 30, 0, 2),
('pipeda', 'withdraw_consent', 'Withdraw Consent', 'Right to withdraw consent at any time', 'PIPEDA Principle 4.3.8', 30, 0, 3),
('pipeda', 'challenge', 'Challenge Compliance', 'Right to challenge compliance with PIPEDA', 'PIPEDA Principle 4.10', 30, 0, 4),
('pipeda', 'complaint', 'Privacy Complaint', 'Right to complain to Privacy Commissioner', 'PIPEDA s.28', 30, 0, 5)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
-- DSAR Rules
('pipeda', 'dsar', 'PIPEDA_DSAR_001', '30-Day Response', 'Access requests must be responded to within 30 days', 'date_check', 'due_date', 'Response deadline exceeded (30 days)', 'PIPEDA s.8(3)', 'error', 1),
('pipeda', 'dsar', 'PIPEDA_DSAR_002', 'Minimal Cost', 'Fee must be minimal and communicated in advance', 'value_check', 'fee_required', 'Fee must be minimal and disclosed', 'PIPEDA Principle 4.9.4', 'warning', 2),
('pipeda', 'dsar', 'PIPEDA_DSAR_003', 'Understandable Format', 'Information must be provided in understandable form', 'required_field', 'response_format', 'Response must be understandable', 'PIPEDA Principle 4.9.3', 'info', 3),

-- Breach Rules
('pipeda', 'breach', 'PIPEDA_BRE_001', 'Report to Commissioner', 'Breach creating real risk of significant harm must be reported', 'value_check', 'regulator_notified', 'OPC notification required for RROSH breaches', 'PIPEDA s.10.1(1)', 'error', 1),
('pipeda', 'breach', 'PIPEDA_BRE_002', 'Notify Individuals', 'Affected individuals must be notified of RROSH breach', 'required_field', 'subjects_notified', 'Individuals must be notified', 'PIPEDA s.10.1(3)', 'error', 2),
('pipeda', 'breach', 'PIPEDA_BRE_003', 'Breach Records', 'Records of all breaches must be maintained for 24 months', 'required_field', 'description', 'Breach must be documented', 'PIPEDA s.10.3', 'error', 3),

-- ROPA Rules
('pipeda', 'ropa', 'PIPEDA_ROPA_001', 'Purpose Identification', 'Purposes must be identified before or at collection', 'required_field', 'purpose', 'Purpose must be identified', 'PIPEDA Principle 4.2', 'error', 1),
('pipeda', 'ropa', 'PIPEDA_ROPA_002', 'Limiting Collection', 'Collection limited to what is necessary', 'required_field', 'data_categories', 'Data collection must be limited', 'PIPEDA Principle 4.4', 'warning', 2),
('pipeda', 'ropa', 'PIPEDA_ROPA_003', 'Retention Limits', 'Personal information retained only as long as necessary', 'required_field', 'retention_period', 'Retention period must be defined', 'PIPEDA Principle 4.5', 'error', 3),
('pipeda', 'ropa', 'PIPEDA_ROPA_004', 'Safeguards', 'Appropriate security safeguards must be in place', 'required_field', 'security_measures', 'Security safeguards required', 'PIPEDA Principle 4.7', 'error', 4),

-- Consent Rules
('pipeda', 'consent', 'PIPEDA_CON_001', 'Meaningful Consent', 'Consent must be meaningful and informed', 'required_field', 'consent_given', 'Valid consent required', 'PIPEDA Principle 4.3', 'error', 1),
('pipeda', 'consent', 'PIPEDA_CON_002', 'Withdrawable', 'Individual can withdraw consent subject to legal restrictions', 'required_field', 'withdrawal_date', 'Withdrawal mechanism required', 'PIPEDA Principle 4.3.8', 'info', 2)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (PIPEDA-aligned)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personal files and records', '7 years after termination', 7, 'PIPEDA, Employment Standards', 'destroy', 'pipeda'),
('Financial Records', 'Financial and tax records', '7 years', 7, 'Income Tax Act, PIPEDA', 'destroy', 'pipeda'),
('Customer Records', 'Customer transaction data', '6 years after last transaction', 6, 'PIPEDA, Limitation periods', 'anonymize', 'pipeda'),
('Marketing Consent', 'CASL consent records', 'Duration + 6 months', 0, 'CASL, PIPEDA', 'destroy', 'pipeda'),
('Access Request Records', 'PIPEDA access request documentation', '2 years', 2, 'PIPEDA', 'destroy', 'pipeda'),
('Breach Records', 'Privacy breach documentation', '24 months minimum', 2, 'PIPEDA s.10.3', 'archive', 'pipeda'),
('Health Records', 'Patient health information', '10 years minimum', 10, 'Provincial health laws', 'archive', 'pipeda')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
