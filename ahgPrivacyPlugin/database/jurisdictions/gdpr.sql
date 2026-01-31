-- =====================================================
-- GDPR - General Data Protection Regulation
-- European Union
-- Regulator: European Data Protection Board
-- Effective: 25 May 2018
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'dpo_required', true,
      'cross_border_transfers', true,
      'sccs_required', true,
      'id_types', JSON_ARRAY('national_id', 'passport', 'eu_residence_permit'),
      'languages', JSON_ARRAY('en', 'de', 'fr', 'es', 'it', 'nl', 'pl', 'pt')
    )
WHERE `code` = 'gdpr';

-- =====================================================
-- Lawful Bases (GDPR Article 6)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('gdpr', 'consent', 'Consent', 'The data subject has given consent for one or more specific purposes', 'GDPR Art 6(1)(a)', 1, 0, 1),
('gdpr', 'contract', 'Contract', 'Processing is necessary for performance of a contract', 'GDPR Art 6(1)(b)', 0, 0, 2),
('gdpr', 'legal_obligation', 'Legal Obligation', 'Processing is necessary for compliance with a legal obligation', 'GDPR Art 6(1)(c)', 0, 0, 3),
('gdpr', 'vital_interest', 'Vital Interests', 'Processing is necessary to protect vital interests of data subject or another person', 'GDPR Art 6(1)(d)', 0, 0, 4),
('gdpr', 'public_task', 'Public Task', 'Processing is necessary for performance of a task in the public interest', 'GDPR Art 6(1)(e)', 0, 0, 5),
('gdpr', 'legitimate_interest', 'Legitimate Interests', 'Processing is necessary for legitimate interests except where overridden by data subject rights', 'GDPR Art 6(1)(f)', 0, 1, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (GDPR Article 9)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('gdpr', 'racial_ethnic', 'Racial or Ethnic Origin', 'Data revealing racial or ethnic origin', 'GDPR Art 9(1)', 1, 1),
('gdpr', 'political', 'Political Opinions', 'Data revealing political opinions', 'GDPR Art 9(1)', 1, 2),
('gdpr', 'religious', 'Religious or Philosophical Beliefs', 'Data revealing religious or philosophical beliefs', 'GDPR Art 9(1)', 1, 3),
('gdpr', 'trade_union', 'Trade Union Membership', 'Data revealing trade union membership', 'GDPR Art 9(1)', 1, 4),
('gdpr', 'genetic', 'Genetic Data', 'Genetic data uniquely identifying a natural person', 'GDPR Art 9(1)', 1, 5),
('gdpr', 'biometric', 'Biometric Data', 'Biometric data for uniquely identifying a natural person', 'GDPR Art 9(1)', 1, 6),
('gdpr', 'health', 'Health Data', 'Data concerning health', 'GDPR Art 9(1)', 1, 7),
('gdpr', 'sex_life', 'Sex Life or Sexual Orientation', 'Data concerning sex life or sexual orientation', 'GDPR Art 9(1)', 1, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (GDPR Data Subject Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('gdpr', 'access', 'Right of Access', 'Right to obtain confirmation and access to personal data', 'GDPR Art 15', 30, 0, 1),
('gdpr', 'rectification', 'Right to Rectification', 'Right to have inaccurate personal data corrected', 'GDPR Art 16', 30, 0, 2),
('gdpr', 'erasure', 'Right to Erasure', 'Right to have personal data erased (right to be forgotten)', 'GDPR Art 17', 30, 0, 3),
('gdpr', 'restriction', 'Right to Restriction', 'Right to restrict processing in certain circumstances', 'GDPR Art 18', 30, 0, 4),
('gdpr', 'portability', 'Right to Data Portability', 'Right to receive data in structured, machine-readable format', 'GDPR Art 20', 30, 0, 5),
('gdpr', 'objection', 'Right to Object', 'Right to object to processing including profiling', 'GDPR Art 21', 30, 0, 6),
('gdpr', 'automated_decision', 'Automated Decision-Making', 'Right not to be subject to automated decision-making including profiling', 'GDPR Art 22', 30, 0, 7),
('gdpr', 'withdraw_consent', 'Withdraw Consent', 'Right to withdraw consent at any time', 'GDPR Art 7(3)', 30, 0, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
-- DSAR Rules
('gdpr', 'dsar', 'GDPR_DSAR_001', 'One Month Response', 'Response must be provided within one month', 'date_check', 'due_date', 'Response deadline exceeded (one month)', 'GDPR Art 12(3)', 'error', 1),
('gdpr', 'dsar', 'GDPR_DSAR_002', 'Extension Communication', 'If extended, data subject must be informed within one month', 'required_field', 'status', 'Extension must be communicated', 'GDPR Art 12(3)', 'warning', 2),
('gdpr', 'dsar', 'GDPR_DSAR_003', 'Free of Charge', 'First copy must be provided free of charge', 'value_check', 'fee_required', 'First copy should be free under GDPR', 'GDPR Art 15(3)', 'info', 3),

-- Breach Rules
('gdpr', 'breach', 'GDPR_BRE_001', '72-Hour Supervisory Authority', 'Breach must be notified to supervisory authority within 72 hours', 'value_check', 'regulator_notified', 'Supervisory authority notification overdue', 'GDPR Art 33(1)', 'error', 1),
('gdpr', 'breach', 'GDPR_BRE_002', 'High Risk Communication', 'Data subjects must be notified if high risk to rights', 'required_field', 'subjects_notified', 'Data subjects must be notified of high-risk breach', 'GDPR Art 34(1)', 'error', 2),
('gdpr', 'breach', 'GDPR_BRE_003', 'Documentation', 'All breaches must be documented regardless of notification', 'required_field', 'description', 'Breach must be documented', 'GDPR Art 33(5)', 'error', 3),

-- ROPA Rules
('gdpr', 'ropa', 'GDPR_ROPA_001', 'Lawful Basis Documentation', 'Lawful basis must be documented for all processing', 'required_field', 'lawful_basis', 'Lawful basis must be recorded', 'GDPR Art 5(1)(a)', 'error', 1),
('gdpr', 'ropa', 'GDPR_ROPA_002', 'Purpose Limitation', 'Purposes must be specified, explicit and legitimate', 'required_field', 'purpose', 'Purpose must be specified', 'GDPR Art 5(1)(b)', 'error', 2),
('gdpr', 'ropa', 'GDPR_ROPA_003', 'Data Minimisation', 'Data must be adequate, relevant and limited to necessity', 'required_field', 'data_categories', 'Data categories must be documented', 'GDPR Art 5(1)(c)', 'warning', 3),
('gdpr', 'ropa', 'GDPR_ROPA_004', 'Storage Limitation', 'Retention periods must be defined', 'required_field', 'retention_period', 'Retention period must be specified', 'GDPR Art 5(1)(e)', 'error', 4),
('gdpr', 'ropa', 'GDPR_ROPA_005', 'Security Measures', 'Appropriate technical and organizational measures required', 'required_field', 'security_measures', 'Security measures must be documented', 'GDPR Art 5(1)(f)', 'error', 5),
('gdpr', 'ropa', 'GDPR_ROPA_006', 'Transfer Safeguards', 'International transfers must have appropriate safeguards', 'required_field', 'transfers', 'Transfer safeguards must be documented', 'GDPR Art 46', 'warning', 6),
('gdpr', 'ropa', 'GDPR_ROPA_007', 'DPIA Requirement', 'DPIA required for high-risk processing', 'required_field', 'dpia_required', 'DPIA assessment must be recorded', 'GDPR Art 35', 'warning', 7),

-- Consent Rules
('gdpr', 'consent', 'GDPR_CON_001', 'Freely Given', 'Consent must be freely given, specific, informed and unambiguous', 'required_field', 'consent_given', 'Valid consent required', 'GDPR Art 4(11)', 'error', 1),
('gdpr', 'consent', 'GDPR_CON_002', 'Demonstrable', 'Controller must be able to demonstrate consent was given', 'required_field', 'consent_proof', 'Consent evidence must be retained', 'GDPR Art 7(1)', 'error', 2),
('gdpr', 'consent', 'GDPR_CON_003', 'Withdrawable', 'It must be as easy to withdraw as to give consent', 'required_field', 'withdrawal_date', 'Withdrawal mechanism required', 'GDPR Art 7(3)', 'info', 3),

-- Retention Rules
('gdpr', 'retention', 'GDPR_RET_001', 'Storage Limitation', 'Data must not be kept longer than necessary', 'required_field', 'retention_years', 'Retention period must be justified', 'GDPR Art 5(1)(e)', 'warning', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (GDPR-aligned)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personal files, contracts', '6 years after termination', 6, 'GDPR Art 5(1)(e), Employment law', 'destroy', 'gdpr'),
('Financial Records', 'Invoices, receipts, financial statements', '7 years', 7, 'Tax regulations', 'destroy', 'gdpr'),
('Customer Records', 'Customer contact and transaction data', '6 years after last transaction', 6, 'GDPR, Contract law', 'anonymize', 'gdpr'),
('Marketing Consent', 'Records of consent for marketing', 'Duration of consent + 6 months', 0, 'GDPR Art 7', 'destroy', 'gdpr'),
('DSAR Records', 'Data subject request documentation', '3 years', 3, 'GDPR Art 12', 'destroy', 'gdpr'),
('Breach Records', 'Data breach documentation', '5 years', 5, 'GDPR Art 33(5)', 'archive', 'gdpr'),
('Consent Records', 'Proof of consent', 'Duration of processing + 3 years', 3, 'GDPR Art 7(1)', 'destroy', 'gdpr'),
('CCTV Footage', 'Video surveillance recordings', '30 days unless incident', 0, 'GDPR Art 5(1)(e)', 'destroy', 'gdpr'),
('Website Analytics', 'Anonymous web statistics', '26 months', 2, 'GDPR, ePrivacy', 'anonymize', 'gdpr')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
