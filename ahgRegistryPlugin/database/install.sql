-- =====================================================
-- ahgRegistryPlugin - Database Schema
-- AtoM/Heratio Community Hub & Registry
-- 18 tables + seed data
-- DO NOT include INSERT INTO atom_plugin
-- =====================================================

-- ---------------------------------------------------
-- 1. registry_institution
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_institution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `institution_type` enum('archive','library','museum','gallery','dam','heritage_site','research_centre','government','university','other') NOT NULL,
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
  `size` enum('small','medium','large','national') DEFAULT NULL,
  `governance` enum('public','private','ngo','academic','government','tribal','community') DEFAULT NULL,
  `parent_body` varchar(255) DEFAULT NULL,
  `established_year` int DEFAULT NULL,
  `accreditation` varchar(255) DEFAULT NULL,
  `collection_summary` text,
  `collection_strengths` json DEFAULT NULL,
  `total_holdings` varchar(100) DEFAULT NULL,
  `digitization_percentage` int DEFAULT NULL,
  `descriptive_standards` json DEFAULT NULL,
  `management_system` varchar(100) DEFAULT NULL,
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
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `vendor_type` enum('developer','integrator','consultant','service_provider','hosting','digitization','training','other') NOT NULL,
  `specializations` json DEFAULT NULL,
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `banner_path` varchar(500) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `street_address` text,
  `city` varchar(100) DEFAULT NULL,
  `province_state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `company_registration` varchar(100) DEFAULT NULL,
  `vat_number` varchar(50) DEFAULT NULL,
  `established_year` int DEFAULT NULL,
  `team_size` enum('solo','2-5','6-20','21-50','50+') DEFAULT NULL,
  `service_regions` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `gitlab_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `verification_notes` text,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `client_count` int DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `rating_count` int DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vendor_slug` (`slug`),
  KEY `idx_vendor_type` (`vendor_type`),
  KEY `idx_vendor_country` (`country`),
  KEY `idx_vendor_active` (`is_active`),
  FULLTEXT KEY `idx_vendor_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. registry_contact
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_contact` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` enum('institution','vendor') NOT NULL,
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
  `institution_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `instance_type` enum('production','staging','development','demo','offline') DEFAULT 'production',
  `software` varchar(100) DEFAULT 'heratio',
  `software_version` varchar(50) DEFAULT NULL,
  `hosting` enum('self_hosted','cloud','vendor_hosted','saas') DEFAULT NULL,
  `hosting_vendor_id` bigint unsigned DEFAULT NULL,
  `maintained_by_vendor_id` bigint unsigned DEFAULT NULL,
  `sync_token` varchar(64) DEFAULT NULL,
  `sync_enabled` tinyint(1) DEFAULT 0,
  `last_sync_at` datetime DEFAULT NULL,
  `last_heartbeat_at` datetime DEFAULT NULL,
  `sync_data` json DEFAULT NULL,
  `status` enum('online','offline','maintenance','decommissioned') DEFAULT 'online',
  `is_public` tinyint(1) DEFAULT 1,
  `description` text,
  `record_count` int DEFAULT NULL,
  `digital_object_count` int DEFAULT NULL,
  `storage_gb` decimal(10,2) DEFAULT NULL,
  `os_environment` varchar(100) DEFAULT NULL,    -- e.g. "Ubuntu 20.04.6 LTS"
  `languages` json DEFAULT NULL,                 -- ['en','fr'] interface languages
  `descriptive_standard` varchar(100) DEFAULT NULL, -- RAD, ISAD(G), DACS, etc.
  `feature_usage` json DEFAULT NULL,             -- {"accessions":true,"authority_records":true,...}
  `feature_notes` json DEFAULT NULL,             -- {"physical_storage":"receives location data from RS-SQL db"}
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
  `category` enum('ams','ims','dam','dams','cms','glam','preservation','digitization','discovery','utility','plugin','integration','theme','other') NOT NULL,
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `screenshot_path` varchar(500) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `documentation_url` varchar(500) DEFAULT NULL,
  `install_url` varchar(500) DEFAULT NULL,
  `git_provider` enum('github','gitlab','bitbucket','self_hosted','none') DEFAULT 'none',
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
  `pricing_model` enum('free','open_source','freemium','subscription','one_time','contact') DEFAULT 'open_source',
  `pricing_details` text,
  `is_verified` tinyint(1) DEFAULT 0,
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
  `release_type` enum('major','minor','patch','beta','rc','alpha') DEFAULT 'patch',
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
  `relationship_type` enum('developer','hosting','maintenance','consulting','digitization','training','integration') NOT NULL,
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
  `entity_type` enum('vendor','software') NOT NULL,
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
  `event_type` enum('register','heartbeat','sync','update','error') NOT NULL,
  `payload` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','error') DEFAULT 'success',
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
  `entity_type` enum('institution','vendor','software') NOT NULL,
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
  `group_type` enum('regional','topic','software','institutional','other') DEFAULT 'regional',
  `focus_areas` json DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `is_virtual` tinyint(1) DEFAULT 0,
  `meeting_frequency` enum('weekly','biweekly','monthly','quarterly','annual','adhoc') DEFAULT NULL,
  `meeting_format` enum('in_person','virtual','hybrid') DEFAULT NULL,
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
  `role` enum('organizer','co_organizer','member','speaker','sponsor') DEFAULT 'member',
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
  `group_id` bigint unsigned NOT NULL,
  `author_email` varchar(255) NOT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_user_id` int DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `topic_type` enum('discussion','question','announcement','event','showcase','help') DEFAULT 'discussion',
  `tags` json DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `status` enum('active','closed','hidden','spam') DEFAULT 'active',
  `reply_count` int DEFAULT 0,
  `view_count` int DEFAULT 0,
  `last_reply_at` datetime DEFAULT NULL,
  `last_reply_by` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_discussion_group` (`group_id`),
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
  `status` enum('active','hidden','spam') DEFAULT 'active',
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
  `entity_type` enum('discussion','reply','blog_post','institution','vendor','software') NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size_bytes` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_type` enum('image','document','log','archive','screenshot','other') DEFAULT 'other',
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
  `author_type` enum('admin','vendor','institution','user_group') NOT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `category` enum('news','announcement','event','tutorial','case_study','release','community','other') DEFAULT 'news',
  `tags` json DEFAULT NULL,
  `status` enum('draft','pending_review','published','archived') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `view_count` int DEFAULT 0,
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
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
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
  `provider` enum('google','facebook','github','linkedin','microsoft') NOT NULL,
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
  `component_type` enum('plugin','module','extension','theme','integration','library','other') DEFAULT 'plugin',
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
('footer_columns', '[{\"title\":\"Directory\",\"links\":[{\"label\":\"Institutions\",\"url\":\"/registry/institutions\"},{\"label\":\"Vendors\",\"url\":\"/registry/vendors\"},{\"label\":\"Software\",\"url\":\"/registry/software\"},{\"label\":\"Map\",\"url\":\"/registry/map\"}]},{\"title\":\"Community\",\"links\":[{\"label\":\"User Groups\",\"url\":\"/registry/groups\"},{\"label\":\"Blog\",\"url\":\"/registry/blog\"},{\"label\":\"Newsletters\",\"url\":\"/registry/newsletters\"},{\"label\":\"Community Hub\",\"url\":\"/registry/community\"}]},{\"title\":\"Get Started\",\"links\":[{\"label\":\"Create Account\",\"url\":\"/registry/register\"},{\"label\":\"Register Institution\",\"url\":\"/registry/my/institution/register\"},{\"label\":\"Register as Vendor\",\"url\":\"/registry/my/vendor/register\"},{\"label\":\"Register Software\",\"url\":\"/registry/my/vendor/software/add\"}]},{\"title\":\"About\",\"links\":[{\"label\":\"AtoM Foundation\",\"url\":\"https://accesstomemoryfoundation.org\"},{\"label\":\"The AHG\",\"url\":\"https://www.theahg.co.za\"},{\"label\":\"GitHub\",\"url\":\"https://github.com/ArchiveHeritageGroup\"},{\"label\":\"API\",\"url\":\"/registry/api/directory\"}]}]', 'json', 'Footer link columns')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- ---------------------------------------------------
-- 19. registry_favorite
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_favorite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `entity_type` enum('institution','vendor','software','group') NOT NULL,
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
  `status` enum('draft','scheduled','sent','cancelled') DEFAULT 'draft',
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
  `status` enum('active','unsubscribed','bounced') DEFAULT 'active',
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
  `status` enum('queued','sent','failed','bounced','opened','clicked') DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `error_message` text,
  PRIMARY KEY (`id`),
  KEY `idx_newsletter` (`newsletter_id`),
  KEY `idx_subscriber` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
