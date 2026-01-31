-- =====================================================
-- LGPD - Lei Geral de Proteção de Dados
-- Brazil
-- Regulator: ANPD - Autoridade Nacional de Proteção de Dados
-- Effective: 18 September 2020
-- =====================================================

-- Mark jurisdiction as installed
UPDATE `privacy_jurisdiction_registry`
SET `is_installed` = 1, `installed_at` = NOW(), `is_active` = 1,
    `config_data` = JSON_OBJECT(
      'anpd_registration', true,
      'dpo_required', true,
      'id_types', JSON_ARRAY('cpf', 'rg', 'passport', 'cnh'),
      'languages', JSON_ARRAY('pt')
    )
WHERE `code` = 'lgpd';

-- =====================================================
-- Lawful Bases (LGPD Article 7)
-- =====================================================
INSERT INTO `privacy_lawful_basis` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_consent`, `requires_lia`, `sort_order`) VALUES
('lgpd', 'consent', 'Consentimento', 'Consent of the data subject', 'LGPD Art 7(I)', 1, 0, 1),
('lgpd', 'legal_obligation', 'Obrigacao Legal', 'Compliance with legal or regulatory obligation', 'LGPD Art 7(II)', 0, 0, 2),
('lgpd', 'public_policy', 'Politicas Publicas', 'Execution of public policies by public administration', 'LGPD Art 7(III)', 0, 0, 3),
('lgpd', 'research', 'Estudos e Pesquisa', 'Research studies ensuring anonymization where possible', 'LGPD Art 7(IV)', 0, 0, 4),
('lgpd', 'contract', 'Execucao de Contrato', 'Execution of contract or preliminary procedures', 'LGPD Art 7(V)', 0, 0, 5),
('lgpd', 'legal_proceedings', 'Processo Judicial', 'Exercise of rights in judicial, administrative or arbitral proceedings', 'LGPD Art 7(VI)', 0, 0, 6),
('lgpd', 'life_protection', 'Protecao da Vida', 'Protection of life or physical safety', 'LGPD Art 7(VII)', 0, 0, 7),
('lgpd', 'health_protection', 'Tutela da Saude', 'Health protection in procedures by health professionals', 'LGPD Art 7(VIII)', 0, 0, 8),
('lgpd', 'legitimate_interest', 'Interesse Legitimo', 'Legitimate interests of controller or third party', 'LGPD Art 7(IX)', 0, 1, 9),
('lgpd', 'credit_protection', 'Protecao de Credito', 'Credit protection in accordance with legislation', 'LGPD Art 7(X)', 0, 0, 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Special Categories (LGPD Article 11 - Sensitive Data)
-- =====================================================
INSERT INTO `privacy_special_category` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `requires_explicit_consent`, `sort_order`) VALUES
('lgpd', 'racial_ethnic', 'Origem Racial/Etnica', 'Data revealing racial or ethnic origin', 'LGPD Art 5(II)', 1, 1),
('lgpd', 'religious', 'Convicao Religiosa', 'Religious conviction', 'LGPD Art 5(II)', 1, 2),
('lgpd', 'political', 'Opiniao Politica', 'Political opinion', 'LGPD Art 5(II)', 1, 3),
('lgpd', 'trade_union', 'Filiacao Sindical', 'Union affiliation', 'LGPD Art 5(II)', 1, 4),
('lgpd', 'religious_philosophical', 'Filiacao Religiosa/Filosofica', 'Religious or philosophical affiliation', 'LGPD Art 5(II)', 1, 5),
('lgpd', 'health', 'Dados de Saude', 'Health-related data', 'LGPD Art 5(II)', 1, 6),
('lgpd', 'sex_life', 'Vida Sexual', 'Data concerning sex life', 'LGPD Art 5(II)', 1, 7),
('lgpd', 'genetic', 'Dados Geneticos', 'Genetic data', 'LGPD Art 5(II)', 1, 8),
('lgpd', 'biometric', 'Dados Biometricos', 'Biometric data', 'LGPD Art 5(II)', 1, 9)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Request Types (LGPD Data Subject Rights - Article 18)
-- =====================================================
INSERT INTO `privacy_request_type` (`jurisdiction_code`, `code`, `name`, `description`, `legal_reference`, `response_days`, `fee_allowed`, `sort_order`) VALUES
('lgpd', 'confirmation', 'Confirmacao', 'Confirmation of existence of processing', 'LGPD Art 18(I)', 15, 0, 1),
('lgpd', 'access', 'Acesso', 'Access to personal data', 'LGPD Art 18(II)', 15, 0, 2),
('lgpd', 'correction', 'Correcao', 'Correction of incomplete, inaccurate data', 'LGPD Art 18(III)', 15, 0, 3),
('lgpd', 'anonymization', 'Anonimizacao', 'Anonymization, blocking or deletion of unnecessary data', 'LGPD Art 18(IV)', 15, 0, 4),
('lgpd', 'portability', 'Portabilidade', 'Portability of data to another service provider', 'LGPD Art 18(V)', 15, 0, 5),
('lgpd', 'deletion', 'Eliminacao', 'Deletion of data processed with consent', 'LGPD Art 18(VI)', 15, 0, 6),
('lgpd', 'sharing_info', 'Compartilhamento', 'Information about sharing with public and private entities', 'LGPD Art 18(VII)', 15, 0, 7),
('lgpd', 'consent_denial_info', 'Negativa de Consentimento', 'Information about possibility of denying consent', 'LGPD Art 18(VIII)', 15, 0, 8),
('lgpd', 'revoke_consent', 'Revogacao', 'Revocation of consent', 'LGPD Art 18(IX)', 15, 0, 9),
('lgpd', 'automated_review', 'Revisao Automatizada', 'Review of automated decisions', 'LGPD Art 20', 15, 0, 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Compliance Rules
-- =====================================================
INSERT INTO `privacy_compliance_rule` (`jurisdiction_code`, `category`, `code`, `name`, `description`, `check_type`, `field_name`, `error_message`, `legal_reference`, `severity`, `sort_order`) VALUES
('lgpd', 'dsar', 'LGPD_DSAR_001', '15-Day Response', 'Response within 15 days (simplified format)', 'date_check', 'due_date', 'Response deadline exceeded (15 days)', 'LGPD Art 19', 'error', 1),
('lgpd', 'dsar', 'LGPD_DSAR_002', 'Free Access', 'Simple format information must be free and immediate', 'value_check', 'fee_required', 'Simple format must be free', 'LGPD Art 19(1)', 'warning', 2),

('lgpd', 'breach', 'LGPD_BRE_001', 'ANPD Notification', 'ANPD must be notified of security incidents', 'value_check', 'regulator_notified', 'ANPD notification required', 'LGPD Art 48', 'error', 1),
('lgpd', 'breach', 'LGPD_BRE_002', 'Subject Notification', 'Data subjects must be notified', 'required_field', 'subjects_notified', 'Data subject notification required', 'LGPD Art 48(1)', 'error', 2),
('lgpd', 'breach', 'LGPD_BRE_003', 'Reasonable Timeframe', 'Notification within reasonable timeframe', 'required_field', 'notification_date', 'Timely notification required', 'LGPD Art 48', 'error', 3),

('lgpd', 'ropa', 'LGPD_ROPA_001', 'Lawful Basis Required', 'Processing must have documented lawful basis', 'required_field', 'lawful_basis', 'Lawful basis required', 'LGPD Art 7', 'error', 1),
('lgpd', 'ropa', 'LGPD_ROPA_002', 'Purpose Specification', 'Purpose must be legitimate, specific, explicit', 'required_field', 'purpose', 'Purpose must be specified', 'LGPD Art 6(I)', 'error', 2),
('lgpd', 'ropa', 'LGPD_ROPA_003', 'Security Measures', 'Technical and administrative security measures required', 'required_field', 'security_measures', 'Security measures required', 'LGPD Art 46', 'error', 3),
('lgpd', 'ropa', 'LGPD_ROPA_004', 'Data Protection Report', 'Impact report for high-risk processing', 'required_field', 'dpia_required', 'Impact report may be required', 'LGPD Art 38', 'warning', 4),

('lgpd', 'consent', 'LGPD_CON_001', 'Specific Consent', 'Consent must be provided in specific format', 'required_field', 'consent_given', 'Valid consent required', 'LGPD Art 8', 'error', 1),
('lgpd', 'consent', 'LGPD_CON_002', 'Highlighted Clauses', 'Consent clauses must be highlighted', 'required_field', 'consent_highlighted', 'Consent must be highlighted', 'LGPD Art 8(1)', 'warning', 2)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- Default Retention Schedules (Brazil-specific)
-- =====================================================
INSERT INTO `privacy_retention_schedule` (`record_type`, `description`, `retention_period`, `retention_years`, `legal_basis`, `disposal_action`, `jurisdiction`) VALUES
('Employment Records', 'Employee records', '5 years after termination', 5, 'LGPD, CLT', 'destroy', 'lgpd'),
('Financial Records', 'Financial and tax records', '5 years', 5, 'CTN, LGPD', 'destroy', 'lgpd'),
('Customer Records', 'Consumer transaction data', '5 years', 5, 'CDC, LGPD', 'anonymize', 'lgpd'),
('DSAR Records', 'Data subject request documentation', '3 years', 3, 'LGPD', 'destroy', 'lgpd'),
('Breach Records', 'Security incident documentation', '5 years', 5, 'LGPD Art 48', 'archive', 'lgpd'),
('Consent Records', 'Proof of consent', 'Duration of processing + 5 years', 5, 'LGPD Art 8', 'destroy', 'lgpd')
ON DUPLICATE KEY UPDATE `retention_period` = VALUES(`retention_period`);
