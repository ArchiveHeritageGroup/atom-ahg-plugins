-- =====================================================
-- PDPA - Personal Data Protection Act
-- Singapore
-- Regulator: Personal Data Protection Commission (PDPC)
-- Effective: 2 July 2014
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'dnc_registry', true,
      'notifiable_breach', true,
      'id_types', JSON_ARRAY('nric', 'fin', 'passport', 'work_permit'),
      'languages', JSON_ARRAY('en', 'zh', 'ms', 'ta')
    )
WHERE `code` = 'pdpa_sg';

-- =====================================================
-- Lawful Bases (PDPA Consent Requirements)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('pdpa_sg', 'consent', 'Consent', 'Individual has given consent for collection, use or disclosure', 'PDPA s.13', 1, 0, 1),
('pdpa_sg', 'deemed_consent', 'Deemed Consent', 'Consent is deemed from conduct or circumstances', 'PDPA s.15', 0, 0, 2),
('pdpa_sg', 'deemed_consent_notification', 'Deemed Consent by Notification', 'Consent deemed where notified and opportunity to opt out', 'PDPA s.15A', 0, 0, 3),
('pdpa_sg', 'legitimate_interest', 'Legitimate Interests', 'Processing for legitimate interests exception', 'PDPA s.3A', 0, 1, 4),
('pdpa_sg', 'business_improvement', 'Business Improvement', 'For business improvement purposes', 'PDPA s.17A', 0, 0, 5),
('pdpa_sg', 'vital_interest', 'Vital Interests', 'Necessary for vital interests of individual', 'PDPA First Sch', 0, 0, 6),
('pdpa_sg', 'publicly_available', 'Publicly Available', 'Data is publicly available', 'PDPA First Sch', 0, 0, 7),
('pdpa_sg', 'legal_requirement', 'Legal Requirement', 'Required under law or court order', 'PDPA First Sch', 0, 0, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (PDPA - NRIC and Sensitive Data)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('pdpa_sg', 'nric', 'NRIC Numbers', 'National Registration Identity Card numbers', 'PDPA Advisory Guidelines', 1, 1),
('pdpa_sg', 'fin', 'FIN Numbers', 'Foreign Identification Numbers', 'PDPA Advisory Guidelines', 1, 2),
('pdpa_sg', 'passport', 'Passport Numbers', 'Passport numbers', 'PDPA Advisory Guidelines', 1, 3),
('pdpa_sg', 'financial', 'Financial Data', 'Financial account information', 'PDPA', 1, 4),
('pdpa_sg', 'health', 'Health Data', 'Medical and health information', 'PDPA', 1, 5),
('pdpa_sg', 'biometric', 'Biometric Data', 'Biometric data for identification', 'PDPA', 1, 6),
('pdpa_sg', 'children', 'Minor Data', 'Personal data of minors', 'PDPA', 1, 7)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (PDPA Individual Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('pdpa_sg', 'access', 'Access Request', 'Right to access personal data', 'PDPA s.21', 30, 1, 1),
('pdpa_sg', 'correction', 'Correction Request', 'Right to correct errors or omissions', 'PDPA s.22', 30, 0, 2),
('pdpa_sg', 'withdraw_consent', 'Withdraw Consent', 'Right to withdraw consent', 'PDPA s.16', 30, 0, 3),
('pdpa_sg', 'portability', 'Data Portability', 'Right to data portability', 'PDPA s.26F', 30, 1, 4),
('pdpa_sg', 'dnc', 'Do Not Call', 'Registration with Do Not Call Registry', 'PDPA Part IX', 30, 0, 5)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
('pdpa_sg', 'dsar', 'SG_DSAR_001', '30-Day Response', 'Response within 30 days or as soon as reasonably possible', 'date_check', 'due_date', 'Response deadline exceeded', 'PDPA s.21(3)', 'error', 1),
('pdpa_sg', 'dsar', 'SG_DSAR_002', 'Reasonable Fee', 'Fee must be reasonable and disclosed', 'value_check', 'fee_required', 'Fee must be reasonable', 'PDPA s.28', 'warning', 2),

('pdpa_sg', 'breach', 'SG_BRE_001', '3-Day PDPC Notification', 'Notifiable breach must be reported to PDPC within 3 days', 'value_check', 'regulator_notified', 'PDPC notification overdue (3 calendar days)', 'PDPA s.26D(2)', 'error', 1),
('pdpa_sg', 'breach', 'SG_BRE_002', 'Individual Notification', 'Affected individuals must be notified', 'required_field', 'subjects_notified', 'Individual notification required', 'PDPA s.26D(3)', 'error', 2),
('pdpa_sg', 'breach', 'SG_BRE_003', 'Breach Assessment', 'Assessment must be completed within 30 days', 'required_field', 'assessment_date', 'Breach assessment required', 'PDPA s.26C', 'error', 3),
('pdpa_sg', 'breach', 'SG_BRE_004', 'Breach Documentation', 'Breach must be documented', 'required_field', 'description', 'Breach documentation required', 'PDPA', 'error', 4),

('pdpa_sg', 'ropa', 'SG_ROPA_001', 'Purpose Limitation', 'Purpose must be what reasonable person would consider appropriate', 'required_field', 'purpose', 'Appropriate purpose required', 'PDPA s.18', 'error', 1),
('pdpa_sg', 'ropa', 'SG_ROPA_002', 'Notification Required', 'Individuals must be notified of purposes', 'required_field', 'notification', 'Purpose notification required', 'PDPA s.20', 'error', 2),
('pdpa_sg', 'ropa', 'SG_ROPA_003', 'Retention Limitation', 'Data must not be retained longer than necessary', 'required_field', 'retention_period', 'Retention period required', 'PDPA s.25', 'error', 3),
('pdpa_sg', 'ropa', 'SG_ROPA_004', 'Protection Obligation', 'Reasonable security arrangements required', 'required_field', 'security_measures', 'Security measures required', 'PDPA s.24', 'error', 4),
('pdpa_sg', 'ropa', 'SG_ROPA_005', 'Transfer Limitation', 'Overseas transfers must maintain comparable protection', 'required_field', 'transfers', 'Transfer safeguards required', 'PDPA s.26', 'warning', 5),

('pdpa_sg', 'consent', 'SG_CON_001', 'Valid Consent', 'Consent must be validly obtained', 'required_field', 'consent_given', 'Valid consent required', 'PDPA s.14', 'error', 1),
('pdpa_sg', 'consent', 'SG_CON_002', 'DNC Compliance', 'Check DNC Registry before marketing messages', 'required_field', 'dnc_checked', 'DNC check required', 'PDPA s.43', 'error', 2)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (Singapore-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee records', '2 years after termination', 2, 'PDPA, Employment Act', 'destroy', 'pdpa_sg'),
('Financial Records', 'Business financial records', '5 years', 5, 'Companies Act, IRAS', 'destroy', 'pdpa_sg'),
('Customer Records', 'Customer personal data', '5 years after relationship', 5, 'PDPA', 'anonymize', 'pdpa_sg'),
('Access Request Records', 'PDPA access request documentation', '2 years', 2, 'PDPA', 'destroy', 'pdpa_sg'),
('Breach Records', 'Data breach documentation', '5 years', 5, 'PDPA', 'archive', 'pdpa_sg'),
('Consent Records', 'Consent evidence', 'Duration of processing', 0, 'PDPA s.14', 'destroy', 'pdpa_sg'),
('DNC Records', 'Do Not Call Registry checks', '3 years', 3, 'PDPA Part IX', 'destroy', 'pdpa_sg')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
