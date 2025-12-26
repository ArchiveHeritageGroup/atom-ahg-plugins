-- =============================================================================
-- arSecurityClearancePlugin Install SQL
-- All security, embargo, extended rights, and watermark tables
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;

-- Security Classification
CREATE TABLE IF NOT EXISTS `security_classification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_i18n` varchar(100) DEFAULT NULL,
  `description` text,
  `color` varchar(20) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `requires_clearance` tinyint(1) DEFAULT '1',
  `requires_justification` tinyint(1) DEFAULT '0',
  `requires_approval` tinyint(1) DEFAULT '0',
  `requires_2fa` tinyint(1) DEFAULT '0',
  `max_session_hours` int DEFAULT NULL,
  `watermark_required` tinyint(1) DEFAULT '0',
  `watermark_image` varchar(255) DEFAULT NULL,
  `download_allowed` tinyint(1) DEFAULT '1',
  `print_allowed` tinyint(1) DEFAULT '1',
  `copy_allowed` tinyint(1) DEFAULT '1',
  `active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  UNIQUE KEY `uq_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Security Clearance
CREATE TABLE IF NOT EXISTS `user_security_clearance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `classification_id` int unsigned NOT NULL,
  `granted_by` int unsigned DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usc_user` (`user_id`),
  KEY `idx_classification` (`classification_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Security Clearance Log
CREATE TABLE IF NOT EXISTS `user_security_clearance_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `classification_id` int unsigned DEFAULT NULL,
  `action` enum('granted','revoked','updated','expired') NOT NULL,
  `changed_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object Security Classification
CREATE TABLE IF NOT EXISTS `object_security_classification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `classification_id` int unsigned NOT NULL,
  `classified_by` int DEFAULT NULL,
  `classified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `review_date` date DEFAULT NULL,
  `declassify_date` date DEFAULT NULL,
  `declassify_to_id` int unsigned DEFAULT NULL,
  `reason` text,
  `handling_instructions` text,
  `inherit_to_children` tinyint(1) DEFAULT '1',
  `justification` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_osc_object` (`object_id`),
  KEY `idx_classification` (`classification_id`),
  KEY `idx_classified_by` (`classified_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Compartment
CREATE TABLE IF NOT EXISTS `security_compartment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `min_clearance_id` int unsigned DEFAULT NULL,
  `requires_need_to_know` tinyint(1) DEFAULT '1',
  `requires_briefing` tinyint(1) DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Access Request
CREATE TABLE IF NOT EXISTS `security_access_request` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `object_id` int unsigned DEFAULT NULL,
  `classification_id` int unsigned DEFAULT NULL,
  `compartment_id` int unsigned DEFAULT NULL,
  `request_type` enum('view','download','print','clearance_upgrade','compartment_access','renewal') NOT NULL,
  `justification` text NOT NULL,
  `duration_hours` int DEFAULT NULL,
  `priority` enum('normal','urgent','immediate') DEFAULT 'normal',
  `status` enum('pending','approved','denied','expired','cancelled') DEFAULT 'pending',
  `reviewed_by` int unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text,
  `access_granted_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Audit Log
CREATE TABLE IF NOT EXISTS `security_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int DEFAULT NULL,
  `object_type` varchar(50) DEFAULT 'information_object',
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `action_category` varchar(50) DEFAULT 'access',
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Access Log
CREATE TABLE IF NOT EXISTS `security_access_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `object_id` int NOT NULL,
  `classification_id` int unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `access_granted` tinyint(1) NOT NULL,
  `denial_reason` varchar(255) DEFAULT NULL,
  `justification` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Clearance History
CREATE TABLE IF NOT EXISTS `security_clearance_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `previous_classification_id` int unsigned DEFAULT NULL,
  `new_classification_id` int unsigned DEFAULT NULL,
  `action` enum('granted','upgraded','downgraded','revoked','renewed','expired','2fa_enabled','2fa_disabled') NOT NULL,
  `changed_by` int unsigned NOT NULL,
  `reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security 2FA Session
CREATE TABLE IF NOT EXISTS `security_2fa_session` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `verified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_fingerprint` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Access Condition Link
CREATE TABLE IF NOT EXISTS `security_access_condition_link` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `classification_id` int unsigned DEFAULT NULL,
  `access_conditions` text,
  `reproduction_conditions` text,
  `narssa_ref` varchar(100) DEFAULT NULL,
  `retention_period` varchar(50) DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Compliance Log
CREATE TABLE IF NOT EXISTS `security_compliance_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(100) NOT NULL,
  `object_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Declassification Schedule
CREATE TABLE IF NOT EXISTS `security_declassification_schedule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int unsigned NOT NULL,
  `scheduled_date` date NOT NULL,
  `from_classification_id` int unsigned NOT NULL,
  `to_classification_id` int unsigned DEFAULT NULL,
  `trigger_type` enum('date','event','retention') NOT NULL DEFAULT 'date',
  `trigger_event` varchar(255) DEFAULT NULL,
  `processed` tinyint(1) DEFAULT '0',
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scheduled` (`scheduled_date`,`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Retention Schedule
CREATE TABLE IF NOT EXISTS `security_retention_schedule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `narssa_ref` varchar(100) NOT NULL,
  `record_type` varchar(255) NOT NULL,
  `retention_period` varchar(100) NOT NULL,
  `disposal_action` varchar(100) NOT NULL,
  `legal_reference` text,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_narssa` (`narssa_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Watermark Type
CREATE TABLE IF NOT EXISTS `watermark_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_file` varchar(255) NOT NULL,
  `position` varchar(50) DEFAULT 'repeat',
  `opacity` decimal(3,2) DEFAULT '0.30',
  `active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custom Watermark
CREATE TABLE IF NOT EXISTS `custom_watermark` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `position` varchar(50) DEFAULT 'center',
  `opacity` decimal(3,2) DEFAULT '0.40',
  `created_by` int unsigned DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Watermark Setting
CREATE TABLE IF NOT EXISTS `watermark_setting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object Watermark Setting
CREATE TABLE IF NOT EXISTS `object_watermark_setting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int unsigned NOT NULL,
  `watermark_enabled` tinyint(1) DEFAULT '1',
  `watermark_type_id` int unsigned DEFAULT NULL,
  `custom_watermark_id` int unsigned DEFAULT NULL,
  `position` varchar(50) DEFAULT 'center',
  `opacity` decimal(3,2) DEFAULT '0.40',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_id` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Watermark Log
CREATE TABLE IF NOT EXISTS `security_watermark_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `object_id` int unsigned NOT NULL,
  `digital_object_id` int unsigned DEFAULT NULL,
  `watermark_type` enum('visible','invisible','both') NOT NULL DEFAULT 'visible',
  `watermark_text` varchar(500) NOT NULL,
  `watermark_code` varchar(100) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`created_at`),
  KEY `idx_code` (`watermark_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Embargo
CREATE TABLE IF NOT EXISTS `embargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `embargo_type` enum('full','metadata_only','digital_object','custom') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text,
  `is_perpetual` tinyint(1) DEFAULT '0',
  `status` enum('active','expired','lifted','pending') DEFAULT 'pending',
  `created_by` int DEFAULT NULL,
  `lifted_by` int DEFAULT NULL,
  `lifted_at` timestamp NULL DEFAULT NULL,
  `lift_reason` text,
  `notify_on_expiry` tinyint(1) DEFAULT '1',
  `notify_days_before` int DEFAULT '30',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_embargo_object` (`object_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Embargo Audit
CREATE TABLE IF NOT EXISTS `embargo_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `embargo_id` bigint unsigned NOT NULL,
  `action` enum('created','modified','lifted','extended','exception_added','exception_removed') NOT NULL,
  `user_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emb_audit_embargo` (`embargo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Embargo Exception
CREATE TABLE IF NOT EXISTS `embargo_exception` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `embargo_id` bigint unsigned NOT NULL,
  `exception_type` enum('user','group','ip_range','repository') NOT NULL,
  `exception_id` int DEFAULT NULL,
  `ip_range_start` varchar(45) DEFAULT NULL,
  `ip_range_end` varchar(45) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `notes` text,
  `granted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emb_exc_embargo` (`embargo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Embargo i18n
CREATE TABLE IF NOT EXISTS `embargo_i18n` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `embargo_id` bigint unsigned NOT NULL,
  `culture` varchar(10) NOT NULL DEFAULT 'en',
  `reason` varchar(255) DEFAULT NULL,
  `notes` text,
  `public_message` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_embargo_i18n` (`embargo_id`,`culture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extended Rights
CREATE TABLE IF NOT EXISTS `extended_rights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `rights_statement_id` bigint unsigned DEFAULT NULL,
  `creative_commons_license_id` bigint unsigned DEFAULT NULL,
  `rights_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `rights_holder` varchar(255) DEFAULT NULL,
  `rights_holder_uri` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ext_rights_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extended Rights Batch Log
CREATE TABLE IF NOT EXISTS `extended_rights_batch_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL,
  `object_count` int NOT NULL DEFAULT '0',
  `object_ids` json DEFAULT NULL,
  `data` json DEFAULT NULL,
  `results` json DEFAULT NULL,
  `performed_by` int DEFAULT NULL,
  `performed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extended Rights i18n
CREATE TABLE IF NOT EXISTS `extended_rights_i18n` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `extended_rights_id` bigint unsigned NOT NULL,
  `culture` varchar(10) NOT NULL DEFAULT 'en',
  `rights_note` text,
  `usage_conditions` text,
  `copyright_notice` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ext_rights_i18n` (`extended_rights_id`,`culture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extended Rights TK Label
CREATE TABLE IF NOT EXISTS `extended_rights_tk_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `extended_rights_id` bigint unsigned NOT NULL,
  `tk_label_id` bigint unsigned NOT NULL,
  `community_id` int DEFAULT NULL,
  `community_note` text,
  `assigned_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ext_rights_tk` (`extended_rights_id`,`tk_label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object Rights Holder (stub for donor integration)
CREATE TABLE IF NOT EXISTS `object_rights_holder` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `donor_id` int NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_donor` (`donor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object Rights Statement
CREATE TABLE IF NOT EXISTS `object_rights_statement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `rights_statement_id` bigint unsigned DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Data
INSERT IGNORE INTO security_classification (code, name, level, color, icon, description, requires_clearance) VALUES
('PUBLIC', 'Public', 0, '#28a745', 'fa-globe', 'Publicly accessible information', 0),
('INTERNAL', 'Internal', 1, '#17a2b8', 'fa-building', 'Internal use only', 0),
('CONFIDENTIAL', 'Confidential', 2, '#ffc107', 'fa-lock', 'Confidential information', 1),
('SECRET', 'Secret', 3, '#fd7e14', 'fa-user-secret', 'Secret information requiring clearance', 1),
('TOP_SECRET', 'Top Secret', 4, '#dc3545', 'fa-shield-alt', 'Top secret - highest clearance required', 1);

INSERT IGNORE INTO watermark_type (code, name, image_file, position, opacity, sort_order) VALUES
('none', 'None', '', 'center', 0.00, 0),
('confidential', 'Confidential', 'confidential.png', 'repeat', 0.30, 1),
('draft', 'Draft', 'draft.png', 'center', 0.50, 2),
('sample', 'Sample', 'sample.png', 'repeat', 0.30, 3);

SET FOREIGN_KEY_CHECKS=1;

-- Donor Stubs (for compatibility until arDonorPlugin is installed)
CREATE TABLE IF NOT EXISTS `donor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `donor_agreement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `donor_id` int DEFAULT NULL,
  `agreement_type_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `donor_agreement_restriction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `donor_agreement_id` int DEFAULT NULL,
  `restriction_type` varchar(50) DEFAULT NULL,
  `applies_to_all` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `auto_release` tinyint(1) DEFAULT 0,
  `release_date` date DEFAULT NULL,
  `security_clearance_level` int DEFAULT NULL,
  `reason` text,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_agreement` (`donor_agreement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
