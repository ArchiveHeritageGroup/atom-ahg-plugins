-- #130: Field-level structured redaction for archival description metadata.
-- Per-IO privacy profile + per-field redaction rules + reason vocabulary.
-- ENUMs avoided (project rule): VARCHAR + COMMENT. INT ids match AtoM object.id.

CREATE TABLE IF NOT EXISTS `privacy_reason` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `label_en` VARCHAR(200) NOT NULL,
  `label_af` VARCHAR(200) DEFAULT NULL,
  `requires_review` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'high-sensitivity reasons need dual review',
  `requires_legal_review` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'e.g. erasure requests need DPO sign-off',
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `privacy_reason` (`code`,`label_en`,`requires_review`,`requires_legal_review`,`sort_order`) VALUES
('personal_data','Contains personal data',1,0,10),
('special_category','Special category data (GDPR Art.9 / POPIA s.26)',1,1,20),
('biometric','Biometric or facial recognition data',1,1,30),
('minor','Data subject is or may be a minor',1,1,40),
('legal_case','Related to legal proceedings',1,1,50),
('third_party','Contains third-party personal data',0,0,60),
('erasure_request','Data subject erasure request (GDPR Art.17 / POPIA s.24)',1,1,70),
('access_request','Data subject access request pending',1,0,80),
('cultural_sensitivity','Culturally sensitive personal data',1,0,90),
('confidential','Confidential personnel or institutional data',0,0,100);

CREATE TABLE IF NOT EXISTS `information_object_privacy` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `information_object_id` INT NOT NULL,
  `privacy_reason_id` TINYINT UNSIGNED DEFAULT NULL COMMENT 'privacy_reason.id',
  `redaction_status` VARCHAR(10) NOT NULL DEFAULT 'none' COMMENT 'none, partial, full, pending',
  `applied_by` INT DEFAULT NULL COMMENT 'user.id',
  `applied_at` DATETIME DEFAULT NULL,
  `legal_basis_reference` VARCHAR(500) DEFAULT NULL COMMENT 'e.g. POPIA s.37, GDPR Art.17(3)(e)',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_iop_io` (`information_object_id`),
  KEY `idx_iop_status` (`redaction_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `information_object_privacy_field` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `privacy_id` INT UNSIGNED NOT NULL COMMENT 'information_object_privacy.id',
  `field_name` VARCHAR(100) NOT NULL,
  `redaction_type` VARCHAR(16) NOT NULL DEFAULT 'full' COMMENT 'full, partial, pseudonymised',
  `redaction_pattern` VARCHAR(100) DEFAULT NULL COMMENT 'email_partial, phone_partial, id_last4',
  `reason` VARCHAR(500) NOT NULL,
  `is_sensitive` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'medical, biometric, financial',
  `reviewed_by` INT DEFAULT NULL COMMENT 'user.id',
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_iopf_field` (`privacy_id`,`field_name`),
  KEY `idx_iopf_privacy` (`privacy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `information_object_privacy_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `information_object_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'served_redacted, served_full, field_added, field_removed, profile_set',
  `field_name` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iopl_io` (`information_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
