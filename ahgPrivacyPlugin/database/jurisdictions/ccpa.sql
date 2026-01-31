-- =====================================================
-- CCPA/CPRA - California Consumer Privacy Act / California Privacy Rights Act
-- United States (California)
-- Regulator: California Privacy Protection Agency
-- Effective: 1 January 2020 (CCPA), 1 January 2023 (CPRA amendments)
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'cpra_enabled', true,
      'revenue_threshold', 25000000,
      'consumer_threshold', 100000,
      'data_sales_threshold', 0.5,
      'id_types', JSON_ARRAY('ssn', 'drivers_license', 'state_id', 'passport'),
      'languages', JSON_ARRAY('en', 'es')
    )
WHERE `code` = 'ccpa';

-- =====================================================
-- Lawful Bases (CCPA/CPRA Processing Bases)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('ccpa', 'consent', 'Consumer Consent', 'Consumer has provided affirmative consent', 'CCPA 1798.100', 1, 0, 1),
('ccpa', 'opt_out_sale', 'Opt-Out of Sale', 'Consumer has not opted out of sale/sharing', 'CCPA 1798.120', 0, 0, 2),
('ccpa', 'service_provider', 'Service Provider', 'Processing by service provider under contract', 'CCPA 1798.140(ag)', 0, 0, 3),
('ccpa', 'contractor', 'Contractor', 'Processing by contractor under written contract', 'CCPA 1798.140(j)', 0, 0, 4),
('ccpa', 'business_purpose', 'Business Purpose', 'Processing reasonably necessary for business purposes', 'CCPA 1798.140(e)', 0, 1, 5),
('ccpa', 'legal_compliance', 'Legal Compliance', 'Required for legal compliance or to exercise legal rights', 'CCPA 1798.145', 0, 0, 6),
('ccpa', 'security', 'Security Operations', 'Necessary to protect security, integrity, or safety', 'CCPA 1798.145(a)(4)', 0, 0, 7)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (CCPA Sensitive Personal Information)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('ccpa', 'government_id', 'Government ID', 'Social Security, drivers license, state ID, passport numbers', 'CCPA 1798.140(ae)(1)', 1, 1),
('ccpa', 'financial_account', 'Financial Account Info', 'Financial account, debit/credit card with access code', 'CCPA 1798.140(ae)(2)', 1, 2),
('ccpa', 'precise_geolocation', 'Precise Geolocation', 'Precise geolocation data', 'CCPA 1798.140(ae)(3)', 1, 3),
('ccpa', 'racial_ethnic', 'Racial or Ethnic Origin', 'Racial or ethnic origin', 'CCPA 1798.140(ae)(4)', 1, 4),
('ccpa', 'religious', 'Religious Beliefs', 'Religious or philosophical beliefs', 'CCPA 1798.140(ae)(5)', 1, 5),
('ccpa', 'union_membership', 'Union Membership', 'Union membership', 'CCPA 1798.140(ae)(6)', 1, 6),
('ccpa', 'mail_email_text', 'Private Communications', 'Contents of mail, email, and text messages (unless business recipient)', 'CCPA 1798.140(ae)(7)', 1, 7),
('ccpa', 'genetic', 'Genetic Data', 'Genetic data', 'CCPA 1798.140(ae)(8)', 1, 8),
('ccpa', 'biometric', 'Biometric Information', 'Biometric information for identification', 'CCPA 1798.140(ae)(9)', 1, 9),
('ccpa', 'health', 'Health Information', 'Health information', 'CCPA 1798.140(ae)(10)', 1, 10),
('ccpa', 'sex_life', 'Sex Life or Orientation', 'Sex life or sexual orientation', 'CCPA 1798.140(ae)(11)', 1, 11)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (CCPA Consumer Rights)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('ccpa', 'know', 'Right to Know', 'Right to know what personal information is collected, used, shared, or sold', 'CCPA 1798.100', 45, 0, 1),
('ccpa', 'access', 'Right to Access', 'Right to access personal information collected', 'CCPA 1798.110', 45, 0, 2),
('ccpa', 'delete', 'Right to Delete', 'Right to request deletion of personal information', 'CCPA 1798.105', 45, 0, 3),
('ccpa', 'correct', 'Right to Correct', 'Right to correct inaccurate personal information', 'CCPA 1798.106', 45, 0, 4),
('ccpa', 'opt_out_sale', 'Opt-Out of Sale/Sharing', 'Right to opt out of sale or sharing of personal information', 'CCPA 1798.120', 15, 0, 5),
('ccpa', 'limit_sensitive', 'Limit Sensitive PI Use', 'Right to limit use and disclosure of sensitive personal information', 'CCPA 1798.121', 15, 0, 6),
('ccpa', 'portability', 'Data Portability', 'Right to receive personal information in portable format', 'CCPA 1798.130', 45, 0, 7),
('ccpa', 'non_discrimination', 'Non-Discrimination', 'Right not to be discriminated against for exercising rights', 'CCPA 1798.125', 45, 0, 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
-- DSAR Rules
('ccpa', 'dsar', 'CCPA_DSAR_001', '45-Day Response', 'Respond to verifiable consumer request within 45 days', 'date_check', 'due_date', 'Response deadline exceeded (45 days)', 'CCPA 1798.130(a)(2)', 'error', 1),
('ccpa', 'dsar', 'CCPA_DSAR_002', 'Free of Charge', 'Requests must be processed free of charge (first time)', 'value_check', 'fee_required', 'First request must be free', 'CCPA 1798.145(g)(1)', 'error', 2),
('ccpa', 'dsar', 'CCPA_DSAR_003', 'Identity Verification', 'Must verify consumer identity before processing', 'required_field', 'is_verified', 'Consumer identity must be verified', 'CCPA 1798.130(a)(3)', 'error', 3),
('ccpa', 'dsar', 'CCPA_DSAR_004', 'Two Request Methods', 'Must provide at least two methods for submitting requests', 'required_field', 'submission_method', 'Multiple submission methods required', 'CCPA 1798.130(a)(1)', 'warning', 4),

-- Breach Rules (California Civil Code 1798.82)
('ccpa', 'breach', 'CCPA_BRE_001', 'Expedient Notification', 'Notify affected consumers in the most expedient time possible', 'value_check', 'subjects_notified', 'Consumer notification required without unreasonable delay', 'Cal. Civ. Code 1798.82', 'error', 1),
('ccpa', 'breach', 'CCPA_BRE_002', 'AG Notification', 'Notify Attorney General if breach affects 500+ Californians', 'value_check', 'regulator_notified', 'AG notification required for 500+ affected', 'Cal. Civ. Code 1798.82(f)', 'error', 2),

-- ROPA/Disclosure Rules
('ccpa', 'ropa', 'CCPA_ROPA_001', 'Privacy Policy', 'Must maintain comprehensive privacy policy', 'required_field', 'privacy_policy', 'Privacy policy required', 'CCPA 1798.130(a)(5)', 'error', 1),
('ccpa', 'ropa', 'CCPA_ROPA_002', 'Collection Categories', 'Must disclose categories of PI collected', 'required_field', 'data_categories', 'Collection categories must be disclosed', 'CCPA 1798.100(a)', 'error', 2),
('ccpa', 'ropa', 'CCPA_ROPA_003', 'Purpose Disclosure', 'Must disclose purposes for which PI is collected', 'required_field', 'purpose', 'Purposes must be disclosed', 'CCPA 1798.100(a)', 'error', 3),
('ccpa', 'ropa', 'CCPA_ROPA_004', 'Sale Disclosure', 'Must disclose if selling or sharing PI', 'required_field', 'data_selling', 'Sale/sharing status must be disclosed', 'CCPA 1798.115', 'error', 4),
('ccpa', 'ropa', 'CCPA_ROPA_005', 'Retention Disclosure', 'Must disclose retention periods by category', 'required_field', 'retention_period', 'Retention periods must be disclosed', 'CCPA 1798.100(a)(3)', 'warning', 5),

-- Consent Rules
('ccpa', 'consent', 'CCPA_CON_001', 'Opt-In for Minors', 'Affirmative opt-in required for consumers under 16', 'required_field', 'minor_consent', 'Minor opt-in consent required', 'CCPA 1798.120(c)', 'error', 1),
('ccpa', 'consent', 'CCPA_CON_002', 'Do Not Sell Link', 'Must provide clear Do Not Sell My Personal Information link', 'required_field', 'dns_link', 'DNS/DNShare link required', 'CCPA 1798.135', 'error', 2),
('ccpa', 'consent', 'CCPA_CON_003', 'Sensitive PI Consent', 'Limit Use link required for sensitive PI', 'required_field', 'limit_sensitive_link', 'Limit Use link required for sensitive PI', 'CCPA 1798.135(a)(3)', 'error', 3)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (California-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee personnel files', '4 years after termination', 4, 'Cal. Labor Code, CCPA', 'destroy', 'ccpa'),
('Financial Records', 'Financial and tax records', '7 years', 7, 'IRS, California Tax', 'destroy', 'ccpa'),
('Customer Records', 'Consumer transaction data', '5 years', 5, 'CCPA, UCC', 'anonymize', 'ccpa'),
('Consumer Request Records', 'CCPA request documentation', '24 months', 2, 'CCPA 1798.185(a)(15)', 'destroy', 'ccpa'),
('Opt-Out Records', 'Do Not Sell opt-out records', '24 months', 2, 'CCPA', 'destroy', 'ccpa'),
('Breach Records', 'Security breach documentation', '5 years', 5, 'Cal. Civ. Code 1798.82', 'archive', 'ccpa'),
('Privacy Training', 'Employee privacy training records', '24 months', 2, 'CCPA 1798.185(a)(14)', 'destroy', 'ccpa')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
