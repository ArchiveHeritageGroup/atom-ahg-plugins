-- =====================================================
-- ahgRegistryPlugin - Database Schema
-- AtoM/Heratio Community Hub & Registry
-- 24 tables + seed data
-- DO NOT include INSERT INTO atom_plugin
-- =====================================================

-- ---------------------------------------------------
-- 1. registry_institution
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_institution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `institution_type` VARCHAR(113) COMMENT 'archive, library, museum, gallery, dam, heritage_site, research_centre, government, university, other' NOT NULL,
  `glam_sectors` json DEFAULT NULL,
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `banner_path` varchar(500) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `fax` varchar(100) DEFAULT NULL,
  `street_address` text,
  `city` varchar(100) DEFAULT NULL,
  `province_state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `size` VARCHAR(100) COMMENT 'small, medium, large, national' DEFAULT NULL,
  `governance` VARCHAR(255) COMMENT 'public, private, ngo, academic, government, tribal, community' DEFAULT NULL,
  `parent_body` varchar(255) DEFAULT NULL,
  `established_year` int DEFAULT NULL,
  `accreditation` varchar(255) DEFAULT NULL,
  `collection_summary` text,
  `collection_strengths` json DEFAULT NULL,
  `total_holdings` text COMMENT 'Legacy — superseded by holdings_analog + holdings_digital',
  `holdings_analog` text,
  `holdings_digital` text,
  `digitization_percentage` int DEFAULT NULL,
  `descriptive_standards` json DEFAULT NULL,
  `management_system` varchar(500) DEFAULT NULL,
  `uses_atom` tinyint(1) DEFAULT 0,
  `open_to_public` tinyint(1) DEFAULT 1,
  `institution_url` varchar(500) DEFAULT NULL,  -- main website URL (separate from AtoM URL)
  `is_verified` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `verification_notes` text,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_institution_slug` (`slug`),
  KEY `idx_institution_type` (`institution_type`),
  KEY `idx_institution_country` (`country`),
  KEY `idx_institution_active` (`is_active`),
  FULLTEXT KEY `idx_institution_search` (`name`, `description`, `collection_summary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. registry_vendor
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_vendor` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_type` json DEFAULT NULL,
  `specializations` json DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `short_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province_state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `company_registration` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vat_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `established_year` int DEFAULT NULL,
  `team_size` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'solo, 2-5, 6-20, 21-50, 50+',
  `service_regions` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `github_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gitlab_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `verification_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `client_count` int DEFAULT '0',
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `rating_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vendor_slug` (`slug`),
  KEY `idx_vendor_country` (`country`),
  KEY `idx_vendor_active` (`is_active`),
  FULLTEXT KEY `idx_vendor_search` (`name`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. registry_contact
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_contact` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(31) COMMENT 'institution, vendor' NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `mobile` varchar(100) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `roles` json DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_public` tinyint(1) DEFAULT 1,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. registry_instance
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_instance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `institution_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instance_type` varchar(53) COLLATE utf8mb4_unicode_ci DEFAULT 'production' COMMENT 'production, staging, development, demo, offline',
  `software` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'heratio',
  `software_version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hosting` varchar(46) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'self_hosted, cloud, vendor_hosted, saas',
  `hosting_vendor_id` bigint unsigned DEFAULT NULL,
  `maintained_by_vendor_id` bigint unsigned DEFAULT NULL,
  `sync_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_enabled` tinyint(1) DEFAULT '0',
  `last_sync_at` datetime DEFAULT NULL,
  `last_heartbeat_at` datetime DEFAULT NULL,
  `sync_data` json DEFAULT NULL,
  `status` varchar(51) COLLATE utf8mb4_unicode_ci DEFAULT 'online' COMMENT 'online, offline, maintenance, decommissioned',
  `is_public` tinyint(1) DEFAULT '1',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `record_count` int DEFAULT NULL,
  `digital_object_count` int DEFAULT NULL,
  `storage_gb` decimal(10,2) DEFAULT NULL,
  `os_environment` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `descriptive_standard` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multi_repository` tinyint(1) DEFAULT '0',
  `repository_count` int DEFAULT NULL,
  `deployment_architecture` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'single_site, split_edit_public, mirror, other',
  `feature_usage` json DEFAULT NULL,
  `feature_notes` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instance_institution` (`institution_id`),
  KEY `idx_instance_hosting_vendor` (`hosting_vendor_id`),
  KEY `idx_instance_maintained_vendor` (`maintained_by_vendor_id`),
  KEY `idx_instance_sync_token` (`sync_token`),
  KEY `idx_instance_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. registry_software
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_software` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `category` VARCHAR(500) COMMENT 'JSON array of: ams, ims, dam, dams, cms, glam, preservation, digitization, discovery, utility, plugin, integration, theme, other' NOT NULL,
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `screenshot_path` varchar(500) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `documentation_url` varchar(500) DEFAULT NULL,
  `install_url` varchar(500) DEFAULT NULL,
  `git_provider` VARCHAR(56) COMMENT 'github, gitlab, bitbucket, self_hosted, none' DEFAULT 'none',
  `git_url` varchar(500) DEFAULT NULL,
  `git_default_branch` varchar(100) DEFAULT NULL,
  `git_latest_tag` varchar(100) DEFAULT NULL,
  `git_latest_commit` varchar(40) DEFAULT NULL,
  `git_is_public` tinyint(1) DEFAULT 1,
  `git_api_token_encrypted` varchar(500) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `upload_path` varchar(500) DEFAULT NULL,
  `upload_filename` varchar(255) DEFAULT NULL,
  `upload_size_bytes` bigint DEFAULT NULL,
  `upload_checksum` varchar(64) DEFAULT NULL,
  `license` varchar(100) DEFAULT NULL,
  `license_url` varchar(500) DEFAULT NULL,
  `latest_version` varchar(50) DEFAULT NULL,
  `supported_platforms` json DEFAULT NULL,
  `glam_sectors` json DEFAULT NULL,
  `standards_supported` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `min_php_version` varchar(20) DEFAULT NULL,
  `min_mysql_version` varchar(20) DEFAULT NULL,
  `requirements` text,
  `pricing_model` VARCHAR(72) COMMENT 'free, open_source, freemium, subscription, one_time, contact' DEFAULT 'open_source',
  `pricing_details` text,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `verification_notes` text,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `institution_count` int DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `rating_count` int DEFAULT 0,
  `download_count` int DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_software_slug` (`slug`),
  KEY `idx_software_vendor` (`vendor_id`),
  KEY `idx_software_category` (`category`),
  KEY `idx_software_active` (`is_active`),
  FULLTEXT KEY `idx_software_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 6. registry_software_release
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_software_release` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `software_id` bigint unsigned NOT NULL,
  `version` varchar(50) NOT NULL,
  `release_type` VARCHAR(48) COMMENT 'major, minor, patch, beta, rc, alpha' DEFAULT 'patch',
  `release_notes` text,
  `git_tag` varchar(100) DEFAULT NULL,
  `git_commit` varchar(40) DEFAULT NULL,
  `git_compare_url` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size_bytes` bigint DEFAULT NULL,
  `file_checksum` varchar(64) DEFAULT NULL,
  `download_count` int DEFAULT 0,
  `is_stable` tinyint(1) DEFAULT 1,
  `is_latest` tinyint(1) DEFAULT 0,
  `released_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_release_software` (`software_id`),
  KEY `idx_release_version` (`version`),
  KEY `idx_release_latest` (`is_latest`),
  UNIQUE KEY `uk_software_version` (`software_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 7. registry_vendor_institution
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_vendor_institution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned NOT NULL,
  `institution_id` bigint unsigned NOT NULL,
  `relationship_type` VARCHAR(92) COMMENT 'developer, hosting, maintenance, consulting, digitization, training, integration' NOT NULL,
  `service_description` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vendor_inst_type` (`vendor_id`, `institution_id`, `relationship_type`),
  KEY `idx_vi_vendor` (`vendor_id`),
  KEY `idx_vi_institution` (`institution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 8. registry_institution_software
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_institution_software` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `institution_id` bigint unsigned NOT NULL,
  `software_id` bigint unsigned NOT NULL,
  `instance_id` bigint unsigned DEFAULT NULL,
  `version_in_use` varchar(50) DEFAULT NULL,
  `deployment_date` date DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inst_soft` (`institution_id`, `software_id`, `instance_id`),
  KEY `idx_is_institution` (`institution_id`),
  KEY `idx_is_software` (`software_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 9. registry_review
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_review` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(28) COMMENT 'vendor, software' NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `reviewer_institution_id` bigint unsigned DEFAULT NULL,
  `reviewer_name` varchar(255) DEFAULT NULL,
  `reviewer_email` varchar(255) DEFAULT NULL,
  `rating` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `comment` text,
  `is_visible` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_review_entity` (`entity_type`, `entity_id`),
  KEY `idx_review_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 10. registry_sync_log
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_sync_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` bigint unsigned NOT NULL,
  `event_type` VARCHAR(52) COMMENT 'register, heartbeat, sync, update, error' NOT NULL,
  `payload` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` VARCHAR(26) COMMENT 'success, error' DEFAULT 'success',
  `error_message` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_synclog_instance` (`instance_id`),
  KEY `idx_synclog_event` (`event_type`),
  KEY `idx_synclog_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 11. registry_tag
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_tag` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(41) COMMENT 'institution, vendor, software' NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tag_entity` (`entity_type`, `entity_id`),
  KEY `idx_tag_tag` (`tag`),
  UNIQUE KEY `uk_entity_tag` (`entity_type`, `entity_id`, `tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 12. registry_user_group
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_user_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `logo_path` varchar(500) DEFAULT NULL,
  `banner_path` varchar(500) DEFAULT NULL,
  `group_type` VARCHAR(59) COMMENT 'regional, topic, software, institutional, other' DEFAULT 'regional',
  `focus_areas` json DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `is_virtual` tinyint(1) DEFAULT 0,
  `meeting_frequency` VARCHAR(63) COMMENT 'weekly, biweekly, monthly, quarterly, annual, adhoc' DEFAULT NULL,
  `meeting_format` VARCHAR(38) COMMENT 'in_person, virtual, hybrid' DEFAULT NULL,
  `meeting_platform` varchar(100) DEFAULT NULL,
  `next_meeting_at` datetime DEFAULT NULL,
  `next_meeting_details` text,
  `mailing_list_url` varchar(500) DEFAULT NULL,
  `slack_url` varchar(500) DEFAULT NULL,
  `discord_url` varchar(500) DEFAULT NULL,
  `forum_url` varchar(500) DEFAULT NULL,
  `member_count` int DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `organizer_name` varchar(255) DEFAULT NULL,
  `organizer_email` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_slug` (`slug`),
  KEY `idx_group_type` (`group_type`),
  KEY `idx_group_country` (`country`),
  KEY `idx_group_active` (`is_active`),
  FULLTEXT KEY `idx_group_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 13. registry_user_group_member
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_user_group_member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `institution_id` bigint unsigned DEFAULT NULL,
  `role` VARCHAR(61) COMMENT 'organizer, co_organizer, member, speaker, sponsor' DEFAULT 'member',
  `is_active` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gm_group` (`group_id`),
  KEY `idx_gm_email` (`email`),
  UNIQUE KEY `uk_group_email` (`group_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 14. registry_discussion
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_discussion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NULL,
  `blog_post_id` bigint unsigned NULL,
  `author_email` varchar(255) NOT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_user_id` int DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `topic_type` VARCHAR(69) COMMENT 'discussion, question, announcement, event, showcase, help' DEFAULT 'discussion',
  `tags` json DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `status` VARCHAR(40) COMMENT 'active, closed, hidden, spam' DEFAULT 'active',
  `reply_count` int DEFAULT 0,
  `view_count` int DEFAULT 0,
  `last_reply_at` datetime DEFAULT NULL,
  `last_reply_by` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_discussion_group` (`group_id`),
  KEY `idx_discussion_blog_post` (`blog_post_id`),
  KEY `idx_discussion_status` (`status`),
  KEY `idx_discussion_pinned` (`is_pinned` DESC),
  KEY `idx_discussion_last_reply` (`last_reply_at` DESC),
  FULLTEXT KEY `idx_discussion_search` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 15. registry_discussion_reply
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_discussion_reply` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `discussion_id` bigint unsigned NOT NULL,
  `parent_reply_id` bigint unsigned DEFAULT NULL,
  `author_email` varchar(255) NOT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_user_id` int DEFAULT NULL,
  `content` text NOT NULL,
  `is_accepted_answer` tinyint(1) DEFAULT 0,
  `status` VARCHAR(32) COMMENT 'active, hidden, spam' DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reply_discussion` (`discussion_id`),
  KEY `idx_reply_parent` (`parent_reply_id`),
  KEY `idx_reply_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 16. registry_attachment
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_attachment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(71) COMMENT 'discussion, reply, blog_post, institution, vendor, software' NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size_bytes` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_type` VARCHAR(60) COMMENT 'image, document, log, archive, screenshot, other' DEFAULT 'other',
  `caption` varchar(500) DEFAULT NULL,
  `is_inline` tinyint(1) DEFAULT 0,
  `download_count` int DEFAULT 0,
  `uploaded_by_email` varchar(255) DEFAULT NULL,
  `uploaded_by_user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attachment_entity` (`entity_type`, `entity_id`),
  KEY `idx_attachment_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 17. registry_blog_post
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_blog_post` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `slug` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `excerpt` varchar(1000) DEFAULT NULL,
  `featured_image_path` varchar(500) DEFAULT NULL,
  `author_type` VARCHAR(50) COMMENT 'admin, vendor, institution, user_group' NOT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `category` VARCHAR(86) COMMENT 'news, announcement, event, tutorial, case_study, release, community, other' DEFAULT 'news',
  `tags` json DEFAULT NULL,
  `status` VARCHAR(54) COMMENT 'draft, pending_review, published, archived' DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `view_count` int DEFAULT 0,
  `comment_count` int DEFAULT 0,
  `comments_enabled` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_blog_slug` (`slug`),
  KEY `idx_blog_author` (`author_type`, `author_id`),
  KEY `idx_blog_status` (`status`),
  KEY `idx_blog_category` (`category`),
  KEY `idx_blog_published` (`published_at` DESC),
  FULLTEXT KEY `idx_blog_search` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 18. registry_settings
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` VARCHAR(39) COMMENT 'text, number, boolean, json' DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 19. registry_oauth_account (social login)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_oauth_account` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,              -- FK user (AtoM user, if linked)
  `provider` VARCHAR(57) COMMENT 'google, facebook, github, linkedin, microsoft' NOT NULL,
  `provider_user_id` varchar(255) NOT NULL, -- provider's unique user ID
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `access_token_encrypted` text DEFAULT NULL,
  `refresh_token_encrypted` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `profile_data` json DEFAULT NULL,        -- raw profile from provider
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_user` (`provider`, `provider_user_id`),
  KEY `idx_oauth_user` (`user_id`),
  KEY `idx_oauth_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 20. registry_instance_feature (feature/module usage tracking)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_instance_feature` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` bigint unsigned NOT NULL,
  `feature_name` varchar(100) NOT NULL,    -- e.g. "accession_records", "archival_descriptions"
  `is_in_use` tinyint(1) DEFAULT 1,
  `comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_instance_feature` (`instance_id`, `feature_name`),
  KEY `idx_if_instance` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 21. registry_software_component (plugins/modules of a software product)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_software_component` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `software_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `component_type` VARCHAR(73) COMMENT 'plugin, module, extension, theme, integration, library, other' DEFAULT 'plugin',
  `category` varchar(100) DEFAULT NULL,
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `git_url` varchar(500) DEFAULT NULL,
  `documentation_url` varchar(500) DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `sort_order` int DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_component_slug` (`software_id`, `slug`),
  KEY `idx_component_software` (`software_id`),
  KEY `idx_component_category` (`category`),
  KEY `idx_component_type` (`component_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed Data: registry_settings defaults
-- =====================================================
INSERT INTO `registry_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('registry_name', 'Heratio Registry', 'text', 'Display name for the registry'),
('moderation_enabled', '1', 'boolean', 'Require admin approval for new registrations'),
('allow_self_registration', '1', 'boolean', 'Allow institutions and vendors to self-register'),
('featured_count', '6', 'number', 'Number of featured items to display on home page'),
('heartbeat_interval_hours', '24', 'number', 'Expected interval between instance heartbeats (hours)'),
('heartbeat_offline_threshold_days', '7', 'number', 'Days without heartbeat before marking instance offline'),
('max_upload_size_mb', '100', 'number', 'Maximum file upload size in megabytes'),
('allowed_upload_extensions', 'zip,tar.gz,deb,rpm', 'text', 'Allowed file extensions for software uploads'),
('default_country', 'South Africa', 'text', 'Default country for new registrations'),
('map_default_lat', '-30.5595', 'text', 'Default map center latitude'),
('map_default_lng', '22.9375', 'text', 'Default map center longitude'),
('map_default_zoom', '5', 'number', 'Default map zoom level'),
('max_attachment_size_mb', '10', 'number', 'Maximum attachment size for discussions/blog (MB)'),
('allowed_attachment_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xlsx,csv,txt,log,zip', 'text', 'Allowed attachment file types'),
('discussion_require_approval', '0', 'boolean', 'Require admin approval for new discussions'),
('blog_require_approval', '1', 'boolean', 'Require admin approval for blog posts from non-admins'),
('oauth_google_enabled', '0', 'boolean', 'Enable Google OAuth login'),
('oauth_google_client_id', '', 'text', 'Google OAuth Client ID'),
('oauth_google_client_secret', '', 'text', 'Google OAuth Client Secret (encrypted)'),
('oauth_facebook_enabled', '0', 'boolean', 'Enable Facebook OAuth login'),
('oauth_facebook_app_id', '', 'text', 'Facebook App ID'),
('oauth_facebook_app_secret', '', 'text', 'Facebook App Secret (encrypted)'),
('oauth_github_enabled', '0', 'boolean', 'Enable GitHub OAuth login'),
('oauth_github_client_id', '', 'text', 'GitHub OAuth Client ID'),
('oauth_github_client_secret', '', 'text', 'GitHub OAuth Client Secret (encrypted)'),
('max_logo_size_mb', '5', 'number', 'Maximum logo upload size in megabytes'),
('allowed_logo_types', 'jpg,jpeg,png,gif,svg,webp', 'text', 'Allowed logo file types'),
('smtp_enabled', '0', 'boolean', 'Enable SMTP email sending'),
('smtp_host', '', 'text', 'SMTP server hostname'),
('smtp_port', '587', 'number', 'SMTP server port'),
('smtp_encryption', 'tls', 'text', 'Encryption: tls, ssl, or none'),
('smtp_username', '', 'text', 'SMTP username'),
('smtp_password', '', 'text', 'SMTP password / app password'),
('smtp_from_email', '', 'text', 'From email address for newsletters'),
('smtp_from_name', 'AtoM Registry', 'text', 'From display name for newsletters'),
('footer_description', 'The global community hub for AtoM institutions, vendors, and archival software. Connect, collaborate, and discover.', 'text', 'Footer description text'),
('footer_copyright', '© {year} The Archive and Heritage Group (Pty) Ltd. · Powered by <a href=\"https://accesstomemoryfoundation.org\" target=\"_blank\">Access to Memory (AtoM)</a>', 'text', 'Footer copyright text'),
('footer_columns', '[{\"title\":\"Directory\",\"links\":[{\"label\":\"Institutions\",\"url\":\"/registry/institutions\"},{\"label\":\"Vendors\",\"url\":\"/registry/vendors\"},{\"label\":\"Software\",\"url\":\"/registry/software\"},{\"label\":\"Map\",\"url\":\"/registry/map\"}]},{\"title\":\"Community\",\"links\":[{\"label\":\"User Groups\",\"url\":\"/registry/groups\"},{\"label\":\"Blog\",\"url\":\"/registry/blog\"},{\"label\":\"Newsletters\",\"url\":\"/registry/newsletters\"},{\"label\":\"Community Hub\",\"url\":\"/registry/community\"}]},{\"title\":\"Get Started\",\"links\":[{\"label\":\"Create Account\",\"url\":\"/registry/register\"},{\"label\":\"Register Institution\",\"url\":\"/registry/my/institution/register\"},{\"label\":\"Register as Vendor\",\"url\":\"/registry/my/vendor/register\"},{\"label\":\"Register Software\",\"url\":\"/registry/my/vendor/software/add\"}]},{\"title\":\"About\",\"links\":[{\"label\":\"AtoM Foundation\",\"url\":\"https://accesstomemoryfoundation.org\"},{\"label\":\"The AHG\",\"url\":\"https://www.theahg.co.za\"},{\"label\":\"GitHub\",\"url\":\"https://github.com/ArchiveHeritageGroup\"},{\"label\":\"API\",\"url\":\"/registry/api/directory\"}]}]', 'json', 'Footer link columns'),
('nav_show_community', '1', 'boolean', 'Show the Community link in the navigation bar'),
('nav_show_user_groups', '1', 'boolean', 'Show User Groups under the More menu'),
('nav_show_blog', '1', 'boolean', 'Show Blog under the More menu'),
('nav_show_newsletters', '1', 'boolean', 'Show Newsletters under the More menu'),
('nav_show_map', '1', 'boolean', 'Show Map under the More menu'),
('nav_show_search', '1', 'boolean', 'Show Search under the More menu')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- ---------------------------------------------------
-- 19. registry_favorite
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_favorite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `entity_type` VARCHAR(48) COMMENT 'institution, vendor, software, group' NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_entity` (`user_id`, `entity_type`, `entity_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 20. registry_newsletter
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_newsletter` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `excerpt` varchar(1000) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_user_id` int DEFAULT NULL,
  `status` VARCHAR(45) COMMENT 'draft, scheduled, sent, cancelled' DEFAULT 'draft',
  `recipient_count` int DEFAULT 0,
  `sent_count` int DEFAULT 0,
  `open_count` int DEFAULT 0,
  `click_count` int DEFAULT 0,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent` (`sent_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 21. registry_newsletter_subscriber
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_newsletter_subscriber` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `institution_id` bigint unsigned DEFAULT NULL,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `status` VARCHAR(41) COMMENT 'active, unsubscribed, bounced' DEFAULT 'active',
  `subscribed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` datetime DEFAULT NULL,
  `unsubscribe_token` varchar(64) NOT NULL,
  `confirm_token` varchar(64) DEFAULT NULL,
  `is_confirmed` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_unsubscribe_token` (`unsubscribe_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 22. registry_newsletter_send_log
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_newsletter_send_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` bigint unsigned NOT NULL,
  `subscriber_id` bigint unsigned NOT NULL,
  `status` VARCHAR(58) COMMENT 'queued, sent, failed, bounced, opened, clicked' DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `error_message` text,
  PRIMARY KEY (`id`),
  KEY `idx_newsletter` (`newsletter_id`),
  KEY `idx_subscriber` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 23. registry_user_institution (multi-institution per user)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_user_institution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `institution_id` bigint unsigned NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'manager' COMMENT 'owner, manager, editor, viewer',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_inst` (`user_id`, `institution_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_inst` (`institution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- 24. registry_dropdown (DB-driven dropdown values)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_dropdown` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dropdown_group` varchar(100) NOT NULL COMMENT 'e.g., institution_type, hosting_type, relationship_type',
  `value` varchar(100) NOT NULL,
  `label` varchar(255) NOT NULL,
  `badge_color` varchar(30) DEFAULT NULL COMMENT 'Bootstrap color class for badges',
  `sort_order` int NOT NULL DEFAULT 100,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_group_value` (`dropdown_group`, `value`),
  KEY `idx_group_active` (`dropdown_group`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: contact_role dropdown group (used by institution contact form)
INSERT IGNORE INTO `registry_dropdown` (`dropdown_group`, `value`, `label`, `sort_order`, `is_active`) VALUES
('contact_role','management','Management / Director',10,1),
('contact_role','atom_admin','AtoM Administrator',20,1),
('contact_role','office_admin','Office Administrator (Billing)',30,1),
('contact_role','it_support','IT / Technical Support',40,1),
('contact_role','archivist','Archivist',50,1),
('contact_role','librarian','Librarian',60,1),
('contact_role','curator','Curator',70,1),
('contact_role','cataloguer','Cataloguer / Metadata Specialist',80,1),
('contact_role','preservation','Digital Preservation Specialist',90,1),
('contact_role','conservator','Conservator',100,1),
('contact_role','collections_manager','Collections Manager',110,1),
('contact_role','reference','Reference / Research Services',120,1),
('contact_role','registrar','Registrar',130,1),
('contact_role','education','Education / Outreach',140,1),
('contact_role','digitization','Digitization Technician',150,1),
('contact_role','volunteer','Volunteer',160,1),
('contact_role','other','Other',999,1);

-- =====================================================
-- Notifications (in-app + email out)
-- =====================================================
CREATE TABLE IF NOT EXISTS `registry_notification` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'recipient user.id',
  `type` VARCHAR(64) NOT NULL COMMENT 'user_registered, institution_claimed, vendor_registered, software_added, review_submitted, etc.',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `link` VARCHAR(500) NULL COMMENT 'destination URL when notification is clicked',
  `related_type` VARCHAR(64) NULL COMMENT 'user, institution, vendor, software, review, ...',
  `related_id` BIGINT UNSIGNED NULL,
  `actor_user_id` INT UNSIGNED NULL COMMENT 'user.id who triggered the event (null for anonymous)',
  `actor_name` VARCHAR(255) NULL COMMENT 'display name of triggering actor (snapshot)',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_dismissed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'hidden from top bar (still appears in dropdown until read)',
  `created_at` DATETIME NOT NULL,
  `read_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`, `created_at`),
  KEY `idx_user_bar` (`user_id`, `is_dismissed`, `is_read`, `created_at`),
  KEY `idx_type_related` (`type`, `related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_entity_urls.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =============================================================================
-- Registry — Repeatable URLs for Institution & Vendor
-- Adds a single registry_entity_url table so institutions and vendors can
-- attach multiple typed URLs (archives site, AtoM instance, digital repository,
-- social profiles, source control, etc.) instead of a fixed handful of columns.
-- =============================================================================

CREATE TABLE IF NOT EXISTS registry_entity_url (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL COMMENT 'institution, vendor',
    entity_id BIGINT UNSIGNED NOT NULL,
    link_type VARCHAR(30) NOT NULL DEFAULT 'website' COMMENT 'website, atom_instance, repository, catalogue, blog, social, github, gitlab, linkedin, facebook, twitter, youtube, other',
    url VARCHAR(500) NOT NULL,
    label VARCHAR(150) DEFAULT NULL COMMENT 'optional custom label shown instead of link_type',
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_link_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill: migrate existing single-column URLs into the new repeatable table
-- so no data is lost when forms switch to the repeatable widget.
INSERT IGNORE INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'institution', id, 'website', website, 10
FROM registry_institution
WHERE website IS NOT NULL AND website <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'institution' AND u.entity_id = registry_institution.id
        AND u.link_type = 'website' AND u.url = registry_institution.website
  );

INSERT IGNORE INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'website', website, 10
FROM registry_vendor
WHERE website IS NOT NULL AND website <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'website' AND u.url = registry_vendor.website
  );

INSERT IGNORE INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'github', github_url, 20
FROM registry_vendor
WHERE github_url IS NOT NULL AND github_url <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'github' AND u.url = registry_vendor.github_url
  );

INSERT IGNORE INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'gitlab', gitlab_url, 30
FROM registry_vendor
WHERE gitlab_url IS NOT NULL AND gitlab_url <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'gitlab' AND u.url = registry_vendor.gitlab_url
  );

INSERT IGNORE INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'linkedin', linkedin_url, 40
FROM registry_vendor
WHERE linkedin_url IS NOT NULL AND linkedin_url <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'linkedin' AND u.url = registry_vendor.linkedin_url
  );

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_erd.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Registry ERD Documentation Migration
-- Date: 2026-03-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `registry_erd` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(255) NOT NULL COMMENT 'e.g. ahgPreservationPlugin',
  `vendor_id` bigint unsigned DEFAULT NULL COMMENT 'FK to registry_vendor.id',
  `display_name` varchar(255) NOT NULL COMMENT 'e.g. Digital Preservation',
  `slug` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'general' COMMENT 'core, sector, compliance, browse, ai, ingest, rights, research, collection, exhibition, integration, reporting',
  `description` text COMMENT 'Short description of this ERD group',
  `tables_json` json DEFAULT NULL COMMENT 'Array of table names to auto-render schema from information_schema',
  `diagram` longtext COMMENT 'ASCII ERD diagram (rendered in <pre> block)',
  `diagram_image` varchar(500) DEFAULT NULL COMMENT 'Uploaded ERD diagram image/document path',
  `notes` text COMMENT 'Additional notes or markdown content',
  `icon` varchar(100) DEFAULT 'fas fa-database' COMMENT 'Font Awesome icon class',
  `color` varchar(50) DEFAULT 'primary' COMMENT 'Bootstrap color class',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 100,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_erd_slug` (`slug`),
  UNIQUE KEY `uq_erd_plugin` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Seed: Standards (already built)
-- =====================================================
INSERT IGNORE INTO `registry_erd` (`plugin_name`, `display_name`, `slug`, `category`, `description`, `tables_json`, `icon`, `color`, `sort_order`) VALUES
('ahgRegistryPlugin', 'Standards & Conformance', 'standards-conformance', 'core',
 'Standards directory, Heratio extensions, vendor conformance declarations, and setup guides.',
 '["registry_standard","registry_standard_extension","registry_software_standard","registry_setup_guide"]',
 'fas fa-balance-scale', 'danger', 10),

('ahgAuditTrailPlugin', 'Audit Trail', 'audit-trail', 'compliance',
 'Audit logging for all entity changes with field-level detail tracking.',
 '["audit_log","audit_log_detail"]',
 'fas fa-history', 'secondary', 20),

('ahgSecurityClearancePlugin', 'Security Classification', 'security-classification', 'compliance',
 'NARSSA-aligned security classification, user clearance levels, and access control.',
 '["security_classification","security_classification_record","security_user_clearance"]',
 'fas fa-shield-alt', 'warning', 30),

('ahgPrivacyPlugin', 'Privacy & Compliance', 'privacy-compliance', 'compliance',
 'POPIA/GDPR/CCPA compliance: breach management, consent tracking, SAR requests, data retention.',
 '["privacy_breach","privacy_breach_record","privacy_consent","privacy_sar_request","privacy_data_retention"]',
 'fas fa-user-shield', 'info', 40),

('ahgPreservationPlugin', 'Digital Preservation', 'digital-preservation', 'core',
 'PREMIS events, checksums, fixity verification, format registry, PRONOM sync, replication.',
 '["preservation_event","preservation_checksum","preservation_format","preservation_replication","preservation_package"]',
 'fas fa-archive', 'success', 50),

('ahgConditionPlugin', 'Condition Assessment', 'condition-assessment', 'collection',
 'Spectrum 5.1-aligned condition assessment with treatment proposals and photo documentation.',
 '["condition_assessment","condition_assessment_detail","condition_treatment_proposal","condition_photo"]',
 'fas fa-clipboard-check', 'primary', 60),

('ahgLoanPlugin', 'Loan Management', 'loan-management', 'collection',
 'Incoming/outgoing loan tracking with item-level condition checks and insurance.',
 '["loan","loan_item","loan_condition"]',
 'fas fa-exchange-alt', 'info', 70),

('ahgHeritageAccountingPlugin', 'Heritage Accounting', 'heritage-accounting', 'compliance',
 'GRAP 103 / IPSAS 45 heritage asset accounting with valuation and movement tracking.',
 '["heritage_asset","heritage_valuation","heritage_movement"]',
 'fas fa-calculator', 'success', 80),

('ahgIiifPlugin', 'IIIF Integration', 'iiif-integration', 'core',
 'IIIF manifests, canvases, annotations, annotation bodies, and OCR text storage.',
 '["iiif_manifest","iiif_canvas","iiif_annotation","iiif_annotation_body","iiif_ocr_text"]',
 'fas fa-images', 'primary', 90),

('ahgResearchPlugin', 'Research Portal', 'research-portal', 'research',
 'Researcher registration, reading room booking, access requests, and usage logging.',
 '["research_request","research_request_item","research_booking","research_access_log"]',
 'fas fa-microscope', 'warning', 100),

('ahgDoiPlugin', 'DOI Integration', 'doi-integration', 'integration',
 'DataCite DOI minting, queue processing, verification, and sync.',
 '["doi_record","doi_queue"]',
 'fas fa-fingerprint', 'dark', 110),

('ahgExtendedRightsPlugin', 'Extended Rights', 'extended-rights', 'rights',
 'RightsStatements.org, embargo management, TK Labels, and batch rights operations.',
 '["extended_rights","embargo_record"]',
 'fas fa-gavel', 'danger', 120),

('ahgProvenancePlugin', 'Provenance Tracking', 'provenance-tracking', 'collection',
 'Chain of custody and provenance event tracking for archival records.',
 '["provenance_event"]',
 'fas fa-route', 'secondary', 130),

('ahgDonorAgreementPlugin', 'Donor Agreements', 'donor-agreements', 'collection',
 'Donor/institution agreement management with SA regulatory compliance.',
 '["donor_agreement"]',
 'fas fa-file-contract', 'primary', 140),

('ahgExhibitionPlugin', 'Exhibition Management', 'exhibition-management', 'exhibition',
 'Exhibition planning, object loans, venue management for GLAM/DAM institutions.',
 '["exhibition","exhibition_item","exhibition_venue"]',
 'fas fa-palette', 'info', 150),

('ahgCustomFieldsPlugin', 'Custom Fields (EAV)', 'custom-fields', 'core',
 'Admin-configurable custom metadata fields using Entity-Attribute-Value pattern.',
 '["custom_field_definition","custom_field_value"]',
 'fas fa-th-list', 'success', 160),

('ahgAIPlugin', 'AI & NER', 'ai-ner', 'ai',
 'Named Entity Recognition, translation, summarization, spellcheck, face detection, LLM suggestions.',
 '["ai_entity","ai_entity_link","ai_translation","ai_suggestion"]',
 'fas fa-brain', 'purple', 170),

('ahgIngestPlugin', 'Data Ingest', 'data-ingest', 'ingest',
 'OAIS-aligned 6-step batch ingestion pipeline with AI processing.',
 '["ingest_session","ingest_file","ingest_mapping","ingest_row","ingest_validation","ingest_job"]',
 'fas fa-file-import', 'warning', 180),

('ahgFeedbackPlugin', 'User Feedback', 'user-feedback', 'research',
 'User feedback and suggestions management.',
 '["feedback"]',
 'fas fa-comment-dots', 'info', 190),

('ahgWorkflowPlugin', 'Workflow Engine', 'workflow-engine', 'reporting',
 'Configurable approval workflow with steps, assignments, and history.',
 '["workflow_definition","workflow_step","workflow_instance","workflow_history"]',
 'fas fa-project-diagram', 'primary', 200),

('ahgReportBuilderPlugin', 'Report Builder', 'report-builder', 'reporting',
 'Enterprise report builder with templates, sections, SQL queries, scheduling.',
 '["report_template","report_section","report_schedule","report_output"]',
 'fas fa-chart-bar', 'success', 210),

('ahgLibraryPlugin', 'Library Cataloging', 'library-cataloging', 'sector',
 'Library cataloging with MARC-inspired fields, ISBN lookup, and cover images.',
 '["library_item"]',
 'fas fa-book', 'primary', 220),

('ahgMuseumPlugin', 'Museum Cataloging', 'museum-cataloging', 'sector',
 'Museum cataloging with CCO, CIDOC-CRM, Spectrum 5.1, Getty vocabulary linking.',
 '["museum_object","museum_exhibition"]',
 'fas fa-landmark', 'warning', 230),

('ahgGalleryPlugin', 'Gallery Management', 'gallery-management', 'sector',
 'Gallery/exhibition management, artist tracking, loans, and provenance.',
 '["gallery_artwork","gallery_exhibition","gallery_artist"]',
 'fas fa-paint-brush', 'danger', 240),

('ahgDAMPlugin', 'Digital Asset Management', 'dam', 'sector',
 'Digital Asset Management with IPTC metadata, watermarks, and asset workflows.',
 '["dam_asset","dam_collection","dam_watermark"]',
 'fas fa-photo-video', 'info', 250),

('ahgContactPlugin', 'Extended Contacts', 'extended-contacts', 'collection',
 'Extended contact information for actors (phone, email, address, social media).',
 '["contact_information"]',
 'fas fa-address-book', 'secondary', 260),

('ahgICIPPlugin', 'Indigenous Cultural IP', 'icip', 'rights',
 'Indigenous Cultural & Intellectual Property management and TK Labels.',
 '["icip_record","icip_community"]',
 'fas fa-feather-alt', 'success', 270),

('ahgCDPAPlugin', 'CDPA (Zimbabwe)', 'cdpa-zimbabwe', 'compliance',
 'Cyber & Data Protection Act [Chapter 12:07] — POTRAZ compliance.',
 '["cdpa_license","cdpa_request"]',
 'fas fa-flag', 'danger', 280),

('ahgNAZPlugin', 'NAZ (Zimbabwe)', 'naz-zimbabwe', 'compliance',
 'National Archives Act [Chapter 25:06] — 25-year rule, closure, permits.',
 '["naz_closure","naz_transfer","naz_permit"]',
 'fas fa-landmark', 'warning', 290),

('ahgRicExplorerPlugin', 'RiC / Fuseki', 'ric-fuseki', 'integration',
 'Records in Context (RiC-O) triplestore integration with Apache Jena Fuseki.',
 '["ric_sync_config","ric_sync_log"]',
 'fas fa-project-diagram', 'dark', 300),

('ahgSettingsPlugin', 'AHG Settings', 'ahg-settings', 'core',
 'Centralized AHG plugin settings management (section-based admin UI).',
 '["ahg_settings"]',
 'fas fa-cog', 'secondary', 310)

ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_feedback_v1.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- ahgRegistryPlugin - Migration: Feedback v1
-- Glenn & Richard feedback (2026-03-30)
-- New instance fields + password reset tokens
-- =====================================================

-- ---------------------------------------------------
-- 1. Instance: multi-repository, deployment architecture
-- ---------------------------------------------------
ALTER TABLE `registry_instance`
  ADD COLUMN `multi_repository` tinyint(1) DEFAULT 0 AFTER `descriptive_standard`,
  ADD COLUMN `repository_count` int DEFAULT NULL AFTER `multi_repository`,
  ADD COLUMN `deployment_architecture` VARCHAR(50) DEFAULT NULL COMMENT 'single_site, split_edit_public, mirror, other' AFTER `repository_count`;

-- ---------------------------------------------------
-- 2. Vendor: add lat/lng for map display
-- ---------------------------------------------------
ALTER TABLE `registry_vendor`
  ADD COLUMN `latitude` DECIMAL(10,7) DEFAULT NULL AFTER `country`,
  ADD COLUMN `longitude` DECIMAL(10,7) DEFAULT NULL AFTER `latitude`;

-- ---------------------------------------------------
-- 3. Password reset tokens
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_password_reset` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token`),
  KEY `idx_reset_email` (`email`),
  KEY `idx_reset_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_notes.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Registry Notes (universal comments/notes)
-- Date: 2026-03-07
-- =====================================================

CREATE TABLE IF NOT EXISTS `registry_note` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL COMMENT 'standard, vendor, erd, software, institution, group',
  `entity_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'FK to registry_user.id',
  `user_name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_note_entity` (`entity_type`, `entity_id`),
  KEY `idx_note_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_notifications.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- Registry notifications: in-app notifications for admins and users
-- Recipients: admins (all users in administrator group) + targeted single users

CREATE TABLE IF NOT EXISTS `registry_notification` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'recipient user.id',
  `type` VARCHAR(64) NOT NULL COMMENT 'user_registered, institution_claimed, vendor_registered, software_added, review_submitted, etc.',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `link` VARCHAR(500) NULL COMMENT 'destination URL when notification is clicked',
  `related_type` VARCHAR(64) NULL COMMENT 'user, institution, vendor, software, review, ...',
  `related_id` BIGINT UNSIGNED NULL,
  `actor_user_id` INT UNSIGNED NULL COMMENT 'user.id who triggered the event (null for anonymous)',
  `actor_name` VARCHAR(255) NULL COMMENT 'display name of triggering actor (snapshot)',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_dismissed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'hidden from top bar (still appears in dropdown until read)',
  `created_at` DATETIME NOT NULL,
  `read_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`, `created_at`),
  KEY `idx_user_bar` (`user_id`, `is_dismissed`, `is_read`, `created_at`),
  KEY `idx_type_related` (`type`, `related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_software_verification.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- Add verification audit columns to registry_software (parity with registry_vendor / registry_institution)
-- Required by SoftwareService::verify() and adminSoftwareVerify action.

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registry_software' AND COLUMN_NAME = 'verified_at');
SET @sql := IF(@col = 0, 'ALTER TABLE registry_software ADD COLUMN verified_at DATETIME NULL AFTER is_verified', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registry_software' AND COLUMN_NAME = 'verified_by');
SET @sql := IF(@col = 0, 'ALTER TABLE registry_software ADD COLUMN verified_by INT NULL AFTER verified_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registry_software' AND COLUMN_NAME = 'verification_notes');
SET @sql := IF(@col = 0, 'ALTER TABLE registry_software ADD COLUMN verification_notes TEXT NULL AFTER verified_by', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- Merged in from database/migrate_standards.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =====================================================
-- Registry Standards & Setup Guides Migration
-- Date: 2026-03-07
-- =====================================================

-- ---------------------------------------------------
-- 1. registry_standard — Reference to external standards
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_standard` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `acronym` varchar(50) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'descriptive' COMMENT 'descriptive, preservation, rights, accounting, compliance, metadata, interchange, sector',
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `issuing_body` varchar(255) DEFAULT NULL,
  `current_version` varchar(50) DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `sector_applicability` json DEFAULT NULL COMMENT '["archive","library","museum","gallery","dam"]',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_standard_slug` (`slug`),
  KEY `idx_standard_category` (`category`),
  KEY `idx_standard_active` (`is_active`),
  FULLTEXT KEY `ft_standard_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. registry_standard_extension — WHERE Heratio deviates/extends
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_standard_extension` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `standard_id` bigint unsigned NOT NULL,
  `extension_type` varchar(30) NOT NULL DEFAULT 'addition' COMMENT 'addition, deviation, implementation_note, api_binding',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `rationale` text COMMENT 'Why this extension exists',
  `plugin_name` varchar(100) DEFAULT NULL COMMENT 'Which plugin implements this',
  `api_endpoint` varchar(255) DEFAULT NULL COMMENT 'API route if applicable',
  `db_tables` varchar(500) DEFAULT NULL COMMENT 'Comma-separated table names affected',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int DEFAULT 100,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ext_standard` (`standard_id`),
  KEY `idx_ext_type` (`extension_type`),
  KEY `idx_ext_plugin` (`plugin_name`),
  CONSTRAINT `fk_ext_standard` FOREIGN KEY (`standard_id`) REFERENCES `registry_standard` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. registry_software_standard — Vendor conformance declarations
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_software_standard` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `software_id` bigint unsigned NOT NULL,
  `standard_id` bigint unsigned NOT NULL,
  `conformance_level` varchar(20) NOT NULL DEFAULT 'partial' COMMENT 'full, partial, extended, planned',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_software_standard` (`software_id`, `standard_id`),
  KEY `idx_ss_software` (`software_id`),
  KEY `idx_ss_standard` (`standard_id`),
  CONSTRAINT `fk_ss_software` FOREIGN KEY (`software_id`) REFERENCES `registry_software` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_standard` FOREIGN KEY (`standard_id`) REFERENCES `registry_standard` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. registry_setup_guide — Deployment/config guides under Software
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_setup_guide` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `software_id` bigint unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'deployment' COMMENT 'security, deployment, configuration, optimization, troubleshooting, integration',
  `content` text NOT NULL COMMENT 'Markdown content',
  `short_description` varchar(500) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_user_id` int DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int DEFAULT 0,
  `sort_order` int DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_guide_slug` (`software_id`, `slug`),
  KEY `idx_guide_software` (`software_id`),
  KEY `idx_guide_category` (`category`),
  KEY `idx_guide_active` (`is_active`),
  FULLTEXT KEY `ft_guide_search` (`title`, `content`),
  CONSTRAINT `fk_guide_software` FOREIGN KEY (`software_id`) REFERENCES `registry_software` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Seed Data: Common GLAM/DAM Standards (links only)
-- ---------------------------------------------------
INSERT IGNORE INTO `registry_standard` (`name`, `acronym`, `slug`, `category`, `short_description`, `website_url`, `issuing_body`, `current_version`, `publication_year`, `sector_applicability`, `is_featured`, `sort_order`) VALUES
-- Descriptive Standards
('General International Standard Archival Description', 'ISAD(G)', 'isad-g', 'descriptive', 'Standard for describing archival materials at all levels.', 'https://www.ica.org/en/isadg-general-international-standard-archival-description-second-edition', 'International Council on Archives (ICA)', '2nd Edition', 2000, '["archive"]', 1, 10),
('International Standard Archival Authority Record', 'ISAAR(CPF)', 'isaar-cpf', 'descriptive', 'Standard for creating authority records for corporate bodies, persons, and families.', 'https://www.ica.org/en/isaar-cpf-international-standard-archival-authority-record-corporate-bodies-persons-and-families-2nd', 'International Council on Archives (ICA)', '2nd Edition', 2004, '["archive"]', 1, 20),
('Describing Archives: A Content Standard', 'DACS', 'dacs', 'descriptive', 'US standard for describing archives, personal papers, and manuscripts.', 'https://saa-ts-dacs.github.io/', 'Society of American Archivists (SAA)', '2nd Edition', 2013, '["archive"]', 0, 30),
('Rules for Archival Description', 'RAD', 'rad', 'descriptive', 'Canadian standard for archival description.', 'https://archivescanada.ca/resources/rad/', 'Canadian Committee on Archival Description', '2nd Edition', 2008, '["archive"]', 0, 40),
('Dublin Core Metadata Element Set', 'DC', 'dublin-core', 'metadata', 'General-purpose metadata standard for cross-domain resource description.', 'https://www.dublincore.org/specifications/dublin-core/dcmi-terms/', 'Dublin Core Metadata Initiative (DCMI)', 'ISO 15836:2009', 2009, '["archive","library","museum","gallery","dam"]', 1, 50),
('Metadata Object Description Schema', 'MODS', 'mods', 'metadata', 'XML schema for bibliographic metadata, subset of MARC.', 'https://www.loc.gov/standards/mods/', 'Library of Congress', '3.8', 2021, '["library","archive"]', 0, 60),
('Encoded Archival Description', 'EAD', 'ead', 'interchange', 'XML standard for encoding finding aids.', 'https://www.loc.gov/ead/', 'Library of Congress / SAA', 'EAD3 1.1.1', 2019, '["archive"]', 0, 70),
('International Standard for Describing Functions', 'ISDF', 'isdf', 'descriptive', 'Standard for describing functions of corporate bodies.', 'https://www.ica.org/en/isdf-international-standard-describing-functions', 'International Council on Archives (ICA)', '1st Edition', 2007, '["archive"]', 0, 80),
('International Standard for Describing Institutions with Archival Holdings', 'ISDIAH', 'isdiah', 'descriptive', 'Standard for describing institutions that hold archival materials.', 'https://www.ica.org/en/isdiah-international-standard-describing-institutions-archival-holdings', 'International Council on Archives (ICA)', '1st Edition', 2008, '["archive"]', 0, 90),
('Records in Contexts', 'RiC', 'ric', 'descriptive', 'Next-generation archival description standard based on linked data and ontologies.', 'https://www.ica.org/standards/RiC/RiC-O_v0-2.html', 'International Council on Archives (ICA)', '0.2', 2021, '["archive"]', 1, 100),

-- Preservation Standards
('PREMIS Data Dictionary for Preservation Metadata', 'PREMIS', 'premis', 'preservation', 'Standard for metadata supporting the preservation of digital objects.', 'https://www.loc.gov/standards/premis/', 'Library of Congress', '3.0', 2015, '["archive","library","museum","dam"]', 1, 110),
('Open Archival Information System', 'OAIS', 'oais', 'preservation', 'Reference model for long-term preservation of digital information.', 'https://www.iso.org/standard/57284.html', 'Consultative Committee for Space Data Systems (CCSDS)', 'ISO 14721:2012', 2012, '["archive","library","museum","dam"]', 1, 120),
('PRONOM Technical Registry', 'PRONOM', 'pronom', 'preservation', 'File format registry for identification and preservation planning.', 'https://www.nationalarchives.gov.uk/PRONOM/', 'The National Archives (UK)', 'Ongoing', 2002, '["archive","library","dam"]', 0, 130),

-- Rights Standards
('RightsStatements.org', NULL, 'rightsstatements', 'rights', 'Standardized rights statements for cultural heritage objects.', 'https://rightsstatements.org/', 'RightsStatements.org Consortium', '1.0', 2016, '["archive","library","museum","gallery","dam"]', 0, 140),
('Creative Commons', 'CC', 'creative-commons', 'rights', 'Standardized licenses for sharing creative works.', 'https://creativecommons.org/licenses/', 'Creative Commons', '4.0', 2013, '["archive","library","museum","gallery","dam"]', 0, 150),
('Traditional Knowledge Labels', 'TK Labels', 'tk-labels', 'rights', 'Labels for indigenous cultural heritage rights.', 'https://localcontexts.org/labels/traditional-knowledge-labels/', 'Local Contexts', '2.0', 2022, '["archive","library","museum","gallery"]', 0, 155),

-- Museum/Gallery Standards
('Cataloguing Cultural Objects', 'CCO', 'cco', 'descriptive', 'Content standard for cultural heritage object description.', 'https://vra.org/cco/', 'Visual Resources Association (VRA)', '1.0', 2006, '["museum","gallery"]', 0, 160),
('Spectrum Collections Management Standard', 'Spectrum', 'spectrum', 'sector', 'Collections management procedures standard for museums.', 'https://collectionstrust.org.uk/spectrum/', 'Collections Trust (UK)', '5.1', 2017, '["museum","gallery"]', 1, 170),
('CIDOC Conceptual Reference Model', 'CIDOC-CRM', 'cidoc-crm', 'metadata', 'Ontology for cultural heritage information integration.', 'https://www.cidoc-crm.org/', 'ICOM/CIDOC', 'ISO 21127:2023', 2023, '["museum","gallery","archive"]', 0, 175),

-- DAM Standards
('IPTC Photo Metadata Standard', 'IPTC', 'iptc', 'metadata', 'Standard for photo and media metadata.', 'https://iptc.org/standards/photo-metadata/', 'International Press Telecommunications Council', '2024.1', 2024, '["dam","library"]', 0, 180),
('International Image Interoperability Framework', 'IIIF', 'iiif', 'interchange', 'APIs for interoperable image and AV delivery.', 'https://iiif.io/', 'IIIF Consortium', '3.0', 2020, '["archive","library","museum","gallery","dam"]', 1, 190),

-- Accounting Standards
('Generally Recognised Accounting Practice for Heritage Assets', 'GRAP 103', 'grap-103', 'accounting', 'South African standard for heritage asset accounting in public sector.', 'https://www.asb.co.za/', 'Accounting Standards Board (SA)', '2014', 2014, '["archive","library","museum","gallery"]', 0, 200),
('International Public Sector Accounting Standard — Heritage', 'IPSAS 45', 'ipsas-45', 'accounting', 'International standard for heritage asset accounting in public sector.', 'https://www.ipsasb.org/', 'International Public Sector Accounting Standards Board', '2023', 2023, '["archive","library","museum","gallery"]', 0, 210),

-- Compliance Standards
('Protection of Personal Information Act', 'POPIA', 'popia', 'compliance', 'South African data protection legislation.', 'https://popia.co.za/', 'Information Regulator (SA)', '2013', 2013, '["archive","library","museum","gallery","dam"]', 0, 220),
('General Data Protection Regulation', 'GDPR', 'gdpr', 'compliance', 'EU data protection regulation.', 'https://gdpr-info.eu/', 'European Union', '2016/679', 2016, '["archive","library","museum","gallery","dam"]', 0, 230),
('National Archives Act (Zimbabwe)', 'NAZ Act', 'naz-act', 'compliance', 'Zimbabwe National Archives Act [Chapter 25:06] — 25-year closure rule.', 'http://www.parlzim.gov.zw/', 'Parliament of Zimbabwe', 'Chapter 25:06', 1986, '["archive"]', 0, 240),
('Cyber and Data Protection Act (Zimbabwe)', 'CDPA', 'cdpa', 'compliance', 'Zimbabwe Cyber and Data Protection Act [Chapter 12:07].', 'https://www.potraz.gov.zw/', 'POTRAZ / Parliament of Zimbabwe', 'Chapter 12:07', 2021, '["archive","library","museum","gallery","dam"]', 0, 250)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ---------------------------------------------------
-- 6. Seed Data: Heratio Standard Extensions
-- ---------------------------------------------------
INSERT IGNORE INTO `registry_standard_extension` (`standard_id`, `extension_type`, `title`, `description`, `rationale`, `plugin_name`, `db_tables`, `sort_order`) VALUES
-- ISAAR(CPF) extensions
((SELECT id FROM registry_standard WHERE slug = 'isaar-cpf'), 'addition', 'Structured Contact Records', 'Adds structured contact information (phone, email, address, role) to authority records via a dedicated contacts table, beyond ISAAR''s free-text address fields.', 'ISAAR(CPF) only provides free-text address area 5.2.1. Institutional users require structured, queryable contacts per authority record.', 'ahgContactPlugin', 'contact_information', 10),
((SELECT id FROM registry_standard WHERE slug = 'isaar-cpf'), 'addition', 'Actor Autocomplete & Browse', 'High-performance Laravel Query Builder browse with autocomplete search for authority records, replacing Symfony/Propel browse.', 'Base AtoM''s actor browse is limited. GLAM institutions with 50k+ authority records need fast, filterable browse.', 'ahgActorManagePlugin', NULL, 20),

-- ISAD(G) extensions
((SELECT id FROM registry_standard WHERE slug = 'isad-g'), 'addition', 'Security Classification', 'Adds security clearance levels (Unclassified through Top Secret) and embargo dates to archival descriptions, with ACL enforcement per user clearance level.', 'NARSSA and government archives require classification-based access control not covered by ISAD(G).', 'ahgSecurityClearancePlugin', 'security_clearance,security_clearance_i18n', 10),
((SELECT id FROM registry_standard WHERE slug = 'isad-g'), 'addition', 'Custom Metadata Fields (EAV)', 'Admin-configurable custom fields per entity type without code changes. Supports text, textarea, date, number, boolean, dropdown, url field types.', 'Institutions need institution-specific metadata fields beyond ISAD(G)''s fixed element set.', 'ahgCustomFieldsPlugin', 'custom_field_definition,custom_field_value', 20),
((SELECT id FROM registry_standard WHERE slug = 'isad-g'), 'addition', 'GLAM Sector Display Modes', 'Automatic detection and sector-specific display of archival descriptions (Archive, Library, Museum, Gallery, DAM) with faceted browse.', 'AtoM only serves archives. Heratio extends to all GLAM/DAM sectors with appropriate display conventions.', 'ahgDisplayPlugin', 'display_object_config,display_facet_cache', 30),

-- PREMIS extensions
((SELECT id FROM registry_standard WHERE slug = 'premis'), 'implementation_note', 'PREMIS Events & Fixity via CLI', 'PREMIS preservation events and fixity checking implemented as CLI commands with scheduling support. Integrates with PRONOM via Siegfried for format identification.', 'Full PREMIS implementation requires automated fixity verification and format identification at scale.', 'ahgPreservationPlugin', 'preservation_event,preservation_fixity', 10),

-- OAIS extensions
((SELECT id FROM registry_standard WHERE slug = 'oais'), 'implementation_note', 'OAIS Package Generation in Ingest', 'The 6-step ingest wizard generates SIP, AIP, and DIP packages per OAIS reference model, with JSON manifests and checksums.', 'OAIS compliance requires structured information packages during ingest.', 'ahgIngestPlugin', 'ingest_session,ingest_job', 10),

-- Spectrum extensions
((SELECT id FROM registry_standard WHERE slug = 'spectrum'), 'addition', 'Spectrum Procedures Integration', 'Maps Spectrum 5.1 procedures to Heratio workflows: Object Entry, Acquisition, Loans In/Out, Condition Assessment, Deaccession.', 'Museum clients require Spectrum procedure compliance for accreditation.', 'ahgSpectrumPlugin', NULL, 10),
((SELECT id FROM registry_standard WHERE slug = 'spectrum'), 'addition', 'Condition Assessment Module', 'Structured condition recording with photo evidence, damage types, conservation recommendations, and Spectrum 5.1 compliance fields.', 'Spectrum Condition Check procedure requires structured assessment records.', 'ahgConditionPlugin', 'condition_assessment,condition_photo', 20),

-- IIIF extensions
((SELECT id FROM registry_standard WHERE slug = 'iiif'), 'implementation_note', 'IIIF v2 & v3 Manifests with Annotations', 'Generates IIIF Presentation API v2 and v3 manifests for digital objects, with annotation support and Cantaloupe integration for Image API tiles.', 'GLAM institutions require interoperable image viewers and annotation capabilities.', 'ahgIiifPlugin', 'iiif_annotation', 10),

-- RiC extensions
((SELECT id FROM registry_standard WHERE slug = 'ric'), 'implementation_note', 'RiC-O Triplestore Sync', 'Syncs AtoM archival descriptions and authority records to an Apache Jena Fuseki triplestore as RiC-O linked data, with SPARQL query support.', 'RiC adoption requires linked data representation for archival entities.', 'ahgRicExplorerPlugin', NULL, 10),

-- Dublin Core
((SELECT id FROM registry_standard WHERE slug = 'dublin-core'), 'addition', 'GLAM Sector Metadata Enrichment', 'Extends Dublin Core records with sector-specific fields: Library (ISBN, call number), Museum (CCO fields), Gallery (exhibition history), DAM (IPTC, watermarks).', 'Dublin Core is intentionally minimal. Sector-specific use requires additional metadata.', 'ahgDisplayPlugin', NULL, 10),

-- POPIA
((SELECT id FROM registry_standard WHERE slug = 'popia'), 'implementation_note', 'Multi-Jurisdiction Privacy Compliance', 'Implements POPIA (SA), GDPR (EU), CCPA (US), PIPEDA (Canada), NDPA (Nigeria), DPA (Kenya), UK GDPR — with PII scanning and consent management.', 'International GLAM institutions operate across jurisdictions requiring parallel compliance.', 'ahgPrivacyPlugin', NULL, 10),

-- GRAP 103
((SELECT id FROM registry_standard WHERE slug = 'grap-103'), 'implementation_note', 'Heritage Asset Accounting with IPSAS Alignment', 'Implements GRAP 103 heritage asset valuation and reporting, aligned with IPSAS 45 for international applicability.', 'South African public sector archives require GRAP 103 compliance for heritage asset accounting.', 'ahgHeritageAccountingPlugin', NULL, 10)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ---------------------------------------------------
-- 7. Nav settings for Standards
-- ---------------------------------------------------
INSERT IGNORE INTO `registry_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('nav_show_standards', '1', 'boolean', 'Show Standards link in the navigation bar')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- ---------------------------------------------------------------------------
-- Merged in from database/seed_openric_software.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- =============================================================================
-- OpenRiC software stack seed for registry_software
-- Source: https://openric.org and the openric/* GitHub organisation
-- Idempotent: keyed by slug; safe to re-run.
-- =============================================================================

-- Remove duplicate of the RiC standard. RiC-O is a standard, not software —
-- it lives in registry_standard (slug 'ric').
DELETE FROM registry_software WHERE slug = 'ric';

-- -----------------------------------------------------------------------------
-- 1. OpenRiC Reference API (openric/service) — Laravel server implementing
--    the OpenRiC contract.
-- -----------------------------------------------------------------------------
UPDATE registry_software SET
  name = 'OpenRiC Reference API',
  short_description = 'Reference Laravel implementation of the OpenRiC HTTP contract — 46 endpoints, full RiC-O 1.1 8-entity CRUD, OAI-PMH v2.0, auto-generated OpenAPI 3.0.',
  description = 'OpenRiC Reference API is the canonical implementation of the OpenRiC HTTP contract on top of RiC-O 1.1. It exposes Records, Agents, Places, Rules, Activities, Instantiations, Repositories and Functions through a uniform CRUD surface, plus OAI-PMH v2.0 for harvesting and content negotiation for JSON-LD/Turtle/HTML. Any OpenRiC-conformant client (viewer, capture, third-party) can drive this server, and any other server that implements the contract can replace it without client changes. Hosted reference deployment at ric.theahg.co.za.',
  category = JSON_ARRAY('integration','discovery'),
  website = 'https://ric.theahg.co.za',
  documentation_url = 'https://openric.org',
  git_provider = 'github',
  git_url = 'https://github.com/openric/service',
  git_default_branch = 'main',
  git_is_public = 1,
  license = 'AGPL-3.0',
  license_url = 'https://www.gnu.org/licenses/agpl-3.0.html',
  pricing_model = 'open_source',
  glam_sectors = JSON_ARRAY('archive','library','museum','gallery','dam'),
  is_verified = 1,
  is_active = 1,
  updated_at = NOW()
WHERE slug = 'openric-api';

-- -----------------------------------------------------------------------------
-- 2. OpenRiC Viewer (openric/viewer, npm @openric/viewer)
-- -----------------------------------------------------------------------------
UPDATE registry_software SET
  name = 'OpenRiC Viewer',
  short_description = 'Standalone 2D/3D graph viewer for OpenRiC-conformant servers. Implementation-neutral — drives any server that implements the OpenRiC Viewing API.',
  description = 'A pure-browser application that renders archival graphs visually in 2D and 3D. The viewer is published on npm as @openric/viewer and can be embedded in any host page. It speaks only the OpenRiC HTTP contract, so it works against the reference API or any other conformant server.',
  category = JSON_ARRAY('discovery','utility'),
  website = 'https://viewer.openric.org',
  documentation_url = 'https://openric.org',
  git_provider = 'github',
  git_url = 'https://github.com/openric/viewer',
  git_default_branch = 'main',
  git_is_public = 1,
  license = 'AGPL-3.0',
  license_url = 'https://www.gnu.org/licenses/agpl-3.0.html',
  latest_version = '0.3.0',
  pricing_model = 'open_source',
  glam_sectors = JSON_ARRAY('archive','library','museum','gallery','dam'),
  is_verified = 1,
  is_active = 1,
  updated_at = NOW()
WHERE slug = 'openric-viewer';

-- -----------------------------------------------------------------------------
-- 3. OpenRiC Capture (openric/capture)
-- -----------------------------------------------------------------------------
UPDATE registry_software SET
  name = 'OpenRiC Capture',
  short_description = 'Pure-browser data-entry client for OpenRiC servers. Create and edit Records, Agents, Places, Rules, Activities, Instantiations and relations against any conformant server.',
  description = 'OpenRiC Capture is a browser-only data-entry client. It uses the OpenRiC write surface (POST/PATCH/DELETE) to create and edit archival entities — records, agents, places, rules, activities, instantiations, and the relations between them. Like the viewer, it is server-agnostic: point it at any conformant OpenRiC server and it works.',
  category = JSON_ARRAY('utility','cms'),
  website = 'https://capture.openric.org',
  documentation_url = 'https://openric.org',
  git_provider = 'github',
  git_url = 'https://github.com/openric/capture',
  git_default_branch = 'main',
  git_is_public = 1,
  license = 'AGPL-3.0',
  license_url = 'https://www.gnu.org/licenses/agpl-3.0.html',
  pricing_model = 'open_source',
  glam_sectors = JSON_ARRAY('archive','library','museum','gallery','dam'),
  is_verified = 1,
  is_active = 1,
  updated_at = NOW()
WHERE slug = 'openric-capture';

-- -----------------------------------------------------------------------------
-- 4. OpenRiC Validator — Python CLI, lives in openric/spec/validator/
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO registry_software
  (name, slug, vendor_id, category, short_description, description,
   website, documentation_url,
   git_provider, git_url, git_default_branch, git_is_public,
   license, license_url, latest_version,
   pricing_model, glam_sectors, is_verified, is_active, created_at, updated_at)
VALUES (
  'OpenRiC Validator', 'openric-validator', 1,
  JSON_ARRAY('utility'),
  'Python CLI conformance validator for the OpenRiC specification — JSON Schemas, SHACL shapes, profile checks.',
  'OpenRiC Validator (openric-validate) is the official Python CLI that validates artefacts against the OpenRiC specification. It runs the 19 JSON Schemas, the SHACL shapes for each named profile (Core Discovery through Export-Only), and the 27-case fixture pack. Used in CI to keep server implementations and content packages on-spec.',
  'https://openric.org', 'https://openric.org',
  'github', 'https://github.com/openric/spec', 'main', 1,
  'AGPL-3.0', 'https://www.gnu.org/licenses/agpl-3.0.html', '0.1.0',
  'open_source', JSON_ARRAY('archive','library','museum','gallery','dam'),
  1, 1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  vendor_id = VALUES(vendor_id),
  category = VALUES(category),
  short_description = VALUES(short_description),
  description = VALUES(description),
  website = VALUES(website),
  documentation_url = VALUES(documentation_url),
  git_provider = VALUES(git_provider),
  git_url = VALUES(git_url),
  git_default_branch = VALUES(git_default_branch),
  git_is_public = VALUES(git_is_public),
  license = VALUES(license),
  license_url = VALUES(license_url),
  latest_version = VALUES(latest_version),
  pricing_model = VALUES(pricing_model),
  glam_sectors = VALUES(glam_sectors),
  is_verified = VALUES(is_verified),
  is_active = VALUES(is_active),
  updated_at = NOW();

-- -----------------------------------------------------------------------------
-- 5. OpenRiC Conformance Suite — bash + jq probe in openric/spec/conformance/
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO registry_software
  (name, slug, vendor_id, category, short_description, description,
   website, documentation_url,
   git_provider, git_url, git_default_branch, git_is_public,
   license, license_url,
   pricing_model, glam_sectors, is_verified, is_active, created_at, updated_at)
VALUES (
  'OpenRiC Conformance Suite', 'openric-conformance', 1,
  JSON_ARRAY('utility'),
  'Black-box conformance probe for OpenRiC servers — point it at any server, get a pass/fail report across every documented endpoint.',
  'A bash + jq script that exercises every required endpoint of an OpenRiC server and reports pass/fail per profile. Runs in CI for the reference implementation and is the same script third parties use to certify their own servers as conformant.',
  'https://openric.org/conformance', 'https://openric.org/conformance',
  'github', 'https://github.com/openric/spec', 'main', 1,
  'AGPL-3.0', 'https://www.gnu.org/licenses/agpl-3.0.html',
  'open_source', JSON_ARRAY('archive','library','museum','gallery','dam'),
  1, 1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  vendor_id = VALUES(vendor_id),
  category = VALUES(category),
  short_description = VALUES(short_description),
  description = VALUES(description),
  website = VALUES(website),
  documentation_url = VALUES(documentation_url),
  git_provider = VALUES(git_provider),
  git_url = VALUES(git_url),
  git_default_branch = VALUES(git_default_branch),
  git_is_public = VALUES(git_is_public),
  license = VALUES(license),
  license_url = VALUES(license_url),
  pricing_model = VALUES(pricing_model),
  glam_sectors = VALUES(glam_sectors),
  is_verified = VALUES(is_verified),
  is_active = VALUES(is_active),
  updated_at = NOW();
