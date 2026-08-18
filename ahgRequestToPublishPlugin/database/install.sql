-- ============================================================
-- ahgRequestToPublishPlugin - Database Schema
-- ============================================================
-- Version: 1.0.0
-- Author: The Archive and Heritage Group
-- ============================================================

-- Request to Publish table (links to object table)
CREATE TABLE IF NOT EXISTS `request_to_publish` (
  `id` INT NOT NULL,
  `parent_id` VARCHAR(50) DEFAULT NULL,
  `rtp_type_id` INT DEFAULT NULL,
  `lft` INT NOT NULL DEFAULT 0,
  `rgt` INT NOT NULL DEFAULT 1,
  `source_culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`),
  INDEX `idx_rtp_type` (`rtp_type_id`),
  INDEX `idx_parent` (`parent_id`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Request to Publish i18n table (translatable fields)
CREATE TABLE IF NOT EXISTS `request_to_publish_i18n` (
  `id` INT NOT NULL,
  `culture` VARCHAR(14) NOT NULL DEFAULT 'en',
  `unique_identifier` VARCHAR(1024) DEFAULT NULL,
  `rtp_name` VARCHAR(50) DEFAULT NULL,
  `rtp_surname` VARCHAR(50) DEFAULT NULL,
  `rtp_phone` VARCHAR(50) DEFAULT NULL,
  `rtp_email` VARCHAR(50) DEFAULT NULL,
  `rtp_institution` VARCHAR(200) DEFAULT NULL,
  `rtp_motivation` TEXT,
  `rtp_planned_use` TEXT,
  `rtp_need_image_by` DATETIME DEFAULT NULL,
  `rtp_admin_notes` TEXT,
  `object_id` VARCHAR(50) DEFAULT NULL,
  `status_id` INT NOT NULL DEFAULT 220,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `culture`),
  INDEX `idx_status` (`status_id`),
  INDEX `idx_object` (`object_id`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Workflow layer, merged in from database/workflow.sql on 2026-08-17.
--
-- It lived in a separate file that install-plugin-schema.php never ran, so a
-- clean install created request_to_publish(_i18n) and nothing else. The inbox
-- then returned HTTP 500 with "Table 'atom.rtp_workflow' doesn't exist" the
-- first time a curator opened it. Every instance we own had the table because
-- someone applied the file by hand, which is why it was never noticed.
--
-- A plugin's schema is install.sql. There is no second file.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `rtp_workflow` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`     INT NOT NULL COMMENT 'request_to_publish.id (logical FK)',
    `receipt_token`  CHAR(32) NOT NULL COMMENT 'Anonymous tracking token',
    `is_anonymous`   TINYINT(1) NOT NULL DEFAULT 0,
    `triage_status`  VARCHAR(20) NOT NULL DEFAULT 'new' COMMENT 'new, triaged, in_review, decided',
    `priority`       VARCHAR(10) NOT NULL DEFAULT 'normal' COMMENT 'low, normal, high',
    `assigned_to`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'Curator user id',
    `assigned_name`  VARCHAR(255) DEFAULT NULL,
    `internal_notes` TEXT DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_request` (`request_id`),
    UNIQUE KEY `uniq_token` (`receipt_token`),
    KEY `idx_triage` (`triage_status`),
    KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rtp_review` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`    INT NOT NULL COMMENT 'request_to_publish.id (logical FK)',
    `reviewer_id`   BIGINT UNSIGNED DEFAULT NULL,
    `reviewer_name` VARCHAR(255) DEFAULT NULL,
    `verdict`       VARCHAR(20) NOT NULL DEFAULT 'abstain' COMMENT 'recommend_approve, recommend_reject, needs_changes, abstain',
    `comments`      TEXT DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
