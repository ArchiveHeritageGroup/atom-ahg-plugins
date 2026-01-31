-- =====================================================
-- POPIA - Protection of Personal Information Act
-- South Africa
-- Regulator: Information Regulator (https://inforegulator.org.za/)
-- Effective: 1 July 2021
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'paia_enabled', true,
      'narssa_enabled', true,
      'id_types', JSON_ARRAY('sa_id', 'passport', 'drivers_license', 'asylum_permit'),
      'languages', JSON_ARRAY('en', 'af', 'zu', 'xh', 'st', 'tn', 'nso', 've', 'ts', 'ss', 'nr')
    )
WHERE `code` = 'popia';

-- =====================================================
-- Lawful Bases (POPIA Section 11)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('popia', 'consent', 'Consent', 'The data subject has consented to the processing', 'POPIA s11(1)(a)', 1, 0, 1),
('popia', 'contract', 'Contractual Necessity', 'Processing is necessary for a contract with the data subject', 'POPIA s11(1)(b)', 0, 0, 2),
('popia', 'legal_obligation', 'Legal Obligation', 'Processing is necessary to comply with a legal obligation', 'POPIA s11(1)(c)', 0, 0, 3),
('popia', 'legitimate_interest', 'Legitimate Interest', 'Processing is necessary for legitimate interests pursued by the responsible party', 'POPIA s11(1)(d)', 0, 1, 4),
('popia', 'vital_interest', 'Vital Interest', 'Processing protects a legitimate interest of the data subject', 'POPIA s11(1)(e)', 0, 0, 5),
('popia', 'public_body', 'Public Body Function', 'Processing is necessary for proper performance of a public law duty', 'POPIA s11(1)(f)', 0, 0, 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (POPIA Sections 26-33)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('popia', 'religious', 'Religious or Philosophical Beliefs', 'Religious, philosophical beliefs or membership of religious organisations', 'POPIA s26', 1, 1),
('popia', 'race_ethnic', 'Race or Ethnic Origin', 'Race or ethnic origin of the data subject', 'POPIA s26', 1, 2),
('popia', 'trade_union', 'Trade Union Membership', 'Trade union membership', 'POPIA s26', 1, 3),
('popia', 'political', 'Political Opinions', 'Political persuasion or opinions', 'POPIA s26', 1, 4),
('popia', 'health', 'Health or Sex Life', 'Health or sex life of the data subject', 'POPIA s26', 1, 5),
('popia', 'biometric', 'Biometric Information', 'Biometric information used to uniquely identify', 'POPIA s26', 1, 6),
('popia', 'criminal', 'Criminal Behaviour', 'Criminal behaviour or allegations of criminal behaviour', 'POPIA s26', 1, 7),
('popia', 'children', 'Children Data', 'Personal information of children (under 18)', 'POPIA s35', 1, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (POPIA Data Subject Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('popia', 'access', 'Access Request', 'Right to access personal information held', 'POPIA s23', 30, 1, 1),
('popia', 'rectification', 'Correction/Rectification', 'Right to correct or delete inaccurate information', 'POPIA s24', 30, 0, 2),
('popia', 'erasure', 'Deletion/Destruction', 'Right to have personal information deleted', 'POPIA s24', 30, 0, 3),
('popia', 'objection', 'Objection to Processing', 'Right to object to processing on reasonable grounds', 'POPIA s11(3)', 30, 0, 4),
('popia', 'restriction', 'Restriction of Processing', 'Right to request restriction while dispute is resolved', 'POPIA s24', 30, 0, 5),
('popia', 'withdraw_consent', 'Withdraw Consent', 'Right to withdraw previously given consent', 'POPIA s11(2)', 30, 0, 6),
('popia', 'portability', 'Data Portability', 'Right to receive data in structured format', 'POPIA s23', 30, 1, 7),
('popia', 'automated_decision', 'Automated Decision Review', 'Right not to be subject to automated decision-making', 'POPIA s71', 30, 0, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
-- DSAR Rules
('popia', 'dsar', 'POPIA_DSAR_001', 'Identity Verification Required', 'Data subject identity must be verified before processing DSAR', 'required_field', 'is_verified', 'Identity verification is mandatory under POPIA s23', 'POPIA s23(2)', 'error', 1),
('popia', 'dsar', 'POPIA_DSAR_002', '30-Day Response Deadline', 'DSAR must be responded to within 30 days', 'date_check', 'due_date', 'Response deadline exceeded (30 days from receipt)', 'POPIA s23(1)', 'error', 2),
('popia', 'dsar', 'POPIA_DSAR_003', 'Refusal Reason Required', 'If refusing request, grounds must be provided', 'required_field', 'refusal_reason', 'Refusal must include valid grounds under POPIA', 'POPIA s23(3)', 'warning', 3),

-- Breach Rules
('popia', 'breach', 'POPIA_BRE_001', '72-Hour Notification', 'Information Regulator must be notified within 72 hours', 'value_check', 'regulator_notified', 'Regulator notification overdue (72 hours)', 'POPIA s22(1)', 'error', 1),
('popia', 'breach', 'POPIA_BRE_002', 'Subject Notification', 'Data subjects must be notified if rights compromised', 'required_field', 'subjects_notified', 'Data subjects must be notified of breach', 'POPIA s22(2)', 'error', 2),
('popia', 'breach', 'POPIA_BRE_003', 'Impact Assessment', 'Breach impact on data subjects must be assessed', 'required_field', 'risk_to_rights', 'Impact assessment is required', 'POPIA s22', 'warning', 3),

-- ROPA Rules
('popia', 'ropa', 'POPIA_ROPA_001', 'Lawful Basis Required', 'Processing must have a documented lawful basis', 'required_field', 'lawful_basis', 'Lawful basis must be documented', 'POPIA s11', 'error', 1),
('popia', 'ropa', 'POPIA_ROPA_002', 'Purpose Specification', 'Purpose of processing must be specified', 'required_field', 'purpose', 'Processing purpose must be specified', 'POPIA s13', 'error', 2),
('popia', 'ropa', 'POPIA_ROPA_003', 'Retention Period', 'Retention period must be defined', 'required_field', 'retention_period', 'Retention period must be specified', 'POPIA s14', 'warning', 3),
('popia', 'ropa', 'POPIA_ROPA_004', 'Security Measures', 'Appropriate security measures must be documented', 'required_field', 'security_measures', 'Security measures must be documented', 'POPIA s19', 'error', 4),

-- Consent Rules
('popia', 'consent', 'POPIA_CON_001', 'Voluntary Consent', 'Consent must be freely given', 'required_field', 'consent_given', 'Valid consent record required', 'POPIA s11(1)(a)', 'error', 1),
('popia', 'consent', 'POPIA_CON_002', 'Consent Evidence', 'Evidence of consent must be retained', 'required_field', 'consent_proof', 'Consent evidence must be retained', 'POPIA s11', 'warning', 2),

-- Retention Rules
('popia', 'retention', 'POPIA_RET_001', 'Retention Justification', 'Retention beyond purpose must be justified', 'required_field', 'legal_basis', 'Retention basis must be documented', 'POPIA s14', 'warning', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (POPIA-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personal files, contracts, performance reviews', '7 years after termination', 7, 'BCEA, LRA, POPIA', 'destroy', 'popia'),
('Financial Records', 'Invoices, receipts, financial statements', '5 years', 5, 'Companies Act, TAA', 'destroy', 'popia'),
('Tax Records', 'Tax returns, assessments, supporting documents', '5 years', 5, 'Tax Administration Act', 'destroy', 'popia'),
('Medical Records', 'Patient health records', '10 years or age 21', 10, 'National Health Act', 'archive', 'popia'),
('CCTV Footage', 'Video surveillance recordings', '30 days unless incident', 0, 'POPIA, RICA', 'destroy', 'popia'),
('Access Control Logs', 'Building and system access records', '1 year', 1, 'POPIA', 'destroy', 'popia'),
('Marketing Consent', 'Records of consent for marketing', 'Duration of consent + 1 year', 1, 'POPIA s69, CPA', 'destroy', 'popia'),
('DSAR Records', 'Data subject access request documentation', '3 years', 3, 'POPIA', 'destroy', 'popia'),
('Breach Records', 'Data breach incident documentation', '5 years', 5, 'POPIA', 'archive', 'popia'),
('Children Records', 'Records involving persons under 18', 'Until age 21 + 3 years', 3, 'POPIA s35', 'destroy', 'popia')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
