-- ============================================================================
-- ahgAiConditionPlugin Database Tables
-- Version: 1.0.0
-- Last Updated: 2026-02-21
-- DO NOT include INSERT INTO atom_plugin - plugins are enabled manually
-- ============================================================================

-- AI Condition Assessments (links to condition_report from ahgConditionPlugin)
CREATE TABLE IF NOT EXISTS `ahg_ai_condition_assessment` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `information_object_id` INT DEFAULT NULL,
    `condition_report_id` INT DEFAULT NULL COMMENT 'FK to condition_report if linked',
    `digital_object_id` INT DEFAULT NULL,
    `image_path` VARCHAR(1024) DEFAULT NULL COMMENT 'Path to analyzed image',
    `overlay_path` VARCHAR(1024) DEFAULT NULL COMMENT 'Path to annotated overlay image',
    `overall_score` DECIMAL(5,2) DEFAULT NULL COMMENT '0-100 condition score',
    `condition_grade` VARCHAR(50) DEFAULT NULL COMMENT 'Dropdown: condition_grade',
    `damage_count` INT DEFAULT 0,
    `recommendations` TEXT DEFAULT NULL,
    `model_version` VARCHAR(50) DEFAULT NULL,
    `processing_time_ms` INT DEFAULT NULL,
    `confidence_threshold` DECIMAL(3,2) DEFAULT 0.25,
    `source` VARCHAR(50) DEFAULT 'manual' COMMENT 'manual, bulk, auto, api',
    `is_confirmed` TINYINT(1) DEFAULT 0 COMMENT 'Human reviewed and confirmed',
    `confirmed_by` INT DEFAULT NULL,
    `confirmed_at` DATETIME DEFAULT NULL,
    `api_client_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'If submitted via SaaS API',
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_aic_object` (`information_object_id`),
    INDEX `idx_aic_report` (`condition_report_id`),
    INDEX `idx_aic_grade` (`condition_grade`),
    INDEX `idx_aic_score` (`overall_score`),
    INDEX `idx_aic_source` (`source`),
    INDEX `idx_aic_confirmed` (`is_confirmed`),
    INDEX `idx_aic_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual damage detections per assessment
CREATE TABLE IF NOT EXISTS `ahg_ai_condition_damage` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assessment_id` BIGINT UNSIGNED NOT NULL,
    `damage_type` VARCHAR(50) NOT NULL COMMENT 'Dropdown: damage_type',
    `severity` VARCHAR(50) DEFAULT NULL COMMENT 'Dropdown: damage_severity',
    `confidence` DECIMAL(4,3) NOT NULL COMMENT '0.000-1.000',
    `bbox_x` INT DEFAULT NULL COMMENT 'Bounding box top-left X (pixels)',
    `bbox_y` INT DEFAULT NULL COMMENT 'Bounding box top-left Y (pixels)',
    `bbox_w` INT DEFAULT NULL COMMENT 'Bounding box width (pixels)',
    `bbox_h` INT DEFAULT NULL COMMENT 'Bounding box height (pixels)',
    `area_percent` DECIMAL(5,2) DEFAULT NULL COMMENT 'Damage area as % of total image',
    `location_zone` VARCHAR(50) DEFAULT NULL COMMENT 'Dropdown: condition location_zone',
    `description` TEXT DEFAULT NULL,
    `score_deduction` DECIMAL(5,2) DEFAULT NULL COMMENT 'Points deducted from score',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_acd_assessment` (`assessment_id`),
    INDEX `idx_acd_type` (`damage_type`),
    INDEX `idx_acd_severity` (`severity`),
    CONSTRAINT `fk_acd_assessment` FOREIGN KEY (`assessment_id`)
        REFERENCES `ahg_ai_condition_assessment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Condition score history for trend tracking
CREATE TABLE IF NOT EXISTS `ahg_ai_condition_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `information_object_id` INT NOT NULL,
    `assessment_id` BIGINT UNSIGNED NOT NULL,
    `score` DECIMAL(5,2) NOT NULL,
    `condition_grade` VARCHAR(50) NOT NULL,
    `damage_count` INT DEFAULT 0,
    `assessed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ach_object` (`information_object_id`),
    INDEX `idx_ach_date` (`assessed_at`),
    CONSTRAINT `fk_ach_assessment` FOREIGN KEY (`assessment_id`)
        REFERENCES `ahg_ai_condition_assessment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SaaS API clients
CREATE TABLE IF NOT EXISTS `ahg_ai_service_client` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `organization` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `api_key` VARCHAR(64) NOT NULL UNIQUE,
    `tier` VARCHAR(50) DEFAULT 'free' COMMENT 'Dropdown: ai_service_tier',
    `monthly_limit` INT DEFAULT 50,
    `can_contribute_training` TINYINT(1) DEFAULT 0 COMMENT 'Client has opted in to contribute training data',
    `training_approved` TINYINT(1) DEFAULT 0 COMMENT 'Admin approved client data for training',
    `training_approved_at` DATETIME DEFAULT NULL,
    `training_approved_by` INT DEFAULT NULL,
    `training_approval_doc` VARCHAR(1024) DEFAULT NULL COMMENT 'Path to uploaded consent document',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_asc_key` (`api_key`),
    INDEX `idx_asc_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SaaS usage metering
CREATE TABLE IF NOT EXISTS `ahg_ai_service_usage` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id` BIGINT UNSIGNED NOT NULL,
    `year_month` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
    `scans_used` INT DEFAULT 0,
    `last_scan_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_client_month` (`client_id`, `year_month`),
    CONSTRAINT `fk_asu_client` FOREIGN KEY (`client_id`)
        REFERENCES `ahg_ai_service_client` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Training data contributions (from condition photos, annotation studio, SaaS clients)
CREATE TABLE IF NOT EXISTS `ahg_ai_training_contribution` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `source` VARCHAR(50) NOT NULL COMMENT 'condition_photos, annotation_studio, saas_client, manual',
    `object_id` INT DEFAULT NULL COMMENT 'FK to information_object if linked',
    `contributor` VARCHAR(255) DEFAULT NULL COMMENT 'User ID or client name',
    `client_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'FK to ahg_ai_service_client if SaaS',
    `image_filename` VARCHAR(255) NOT NULL,
    `annotation_filename` VARCHAR(255) NOT NULL,
    `damage_types` JSON DEFAULT NULL COMMENT 'Array of damage types in this contribution',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_atc_source` (`source`),
    INDEX `idx_atc_status` (`status`),
    INDEX `idx_atc_object` (`object_id`),
    INDEX `idx_atc_client` (`client_id`),
    INDEX `idx_atc_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Seed Data: Dropdown Manager taxonomies (section: ai)
-- ============================================================================

-- AI assessment source
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
    ('ai_assessment_source', 'AI Assessment Source', 'ai', 'manual', 'Manual Upload', '#6c757d', 10, 1),
    ('ai_assessment_source', 'AI Assessment Source', 'ai', 'bulk', 'Bulk Scan', '#0d6efd', 20, 0),
    ('ai_assessment_source', 'AI Assessment Source', 'ai', 'auto', 'Auto (On Upload)', '#198754', 30, 0),
    ('ai_assessment_source', 'AI Assessment Source', 'ai', 'api', 'External API', '#6f42c1', 40, 0),
    ('ai_assessment_source', 'AI Assessment Source', 'ai', 'manual_entry', 'Manual Entry', '#495057', 50, 0);

-- AI service tier
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
    ('ai_service_tier', 'AI Service Tier', 'ai', 'free', 'Free (50/month)', '#6c757d', 10, 1),
    ('ai_service_tier', 'AI Service Tier', 'ai', 'standard', 'Standard (500/month)', '#0d6efd', 20, 0),
    ('ai_service_tier', 'AI Service Tier', 'ai', 'pro', 'Professional (5000/month)', '#198754', 30, 0),
    ('ai_service_tier', 'AI Service Tier', 'ai', 'enterprise', 'Enterprise (Unlimited)', '#dc3545', 40, 0);

-- AI confidence level
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
    ('ai_confidence_level', 'AI Confidence Level', 'ai', 'low', 'Low (<50%)', '#dc3545', 10, 0),
    ('ai_confidence_level', 'AI Confidence Level', 'ai', 'medium', 'Medium (50-75%)', '#ffc107', 20, 0),
    ('ai_confidence_level', 'AI Confidence Level', 'ai', 'high', 'High (75-90%)', '#198754', 30, 1),
    ('ai_confidence_level', 'AI Confidence Level', 'ai', 'very_high', 'Very High (>90%)', '#0d6efd', 40, 0);

-- Seed internal API client
INSERT IGNORE INTO `ahg_ai_service_client` (`id`, `name`, `organization`, `email`, `api_key`, `tier`, `monthly_limit`, `is_active`) VALUES
    (1, 'AtoM Internal', 'The Archive and Heritage Group', 'johan@theahg.co.za', 'ahg_ai_condition_internal_2026', 'enterprise', 999999, 1);

-- ============================================================================
-- Settings seed data
-- ============================================================================

INSERT IGNORE INTO `ahg_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
    ('ai_condition_service_url', 'http://localhost:8100', 'ai_condition'),
    ('ai_condition_api_key', 'ahg_ai_condition_internal_2026', 'ai_condition'),
    ('ai_condition_auto_scan', '0', 'ai_condition'),
    ('ai_condition_min_confidence', '0.25', 'ai_condition'),
    ('ai_condition_overlay_enabled', '1', 'ai_condition'),
    ('ai_condition_notify_grade', 'poor', 'ai_condition');
