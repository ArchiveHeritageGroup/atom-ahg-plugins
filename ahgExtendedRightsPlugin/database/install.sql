-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgExtendedRightsPlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: object_rights_holder
CREATE TABLE IF NOT EXISTS `object_rights_holder` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `donor_id` int NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object_id` (`object_id`),
  KEY `idx_donor_id` (`donor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: object_rights_statement
CREATE TABLE IF NOT EXISTS `object_rights_statement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `rights_statement_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_obj_rs` (`object_id`,`rights_statement_id`),
  KEY `idx_object_id` (`object_id`),
  KEY `idx_rights_statement_id` (`rights_statement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tk_label_category
CREATE TABLE IF NOT EXISTS `tk_label_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#000000',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_cat_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TK Labels (Traditional Knowledge Labels)
-- ============================================================
-- Table: tk_label_category
CREATE TABLE IF NOT EXISTS `tk_label_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_cat_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default TK Label Categories
INSERT IGNORE INTO `tk_label_category` (`id`, `code`, `color`, `sort_order`) VALUES
(1, 'attribution', '#0d6efd', 1),
(2, 'protocol', '#198754', 2),
(3, 'provenance', '#ffc107', 3);

-- Table: tk_label
CREATE TABLE IF NOT EXISTS `tk_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tk_label_category_id` bigint unsigned NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_filename` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_code` (`code`),
  UNIQUE KEY `uq_tk_uri` (`uri`),
  KEY `idx_tk_cat` (`tk_label_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tk_label
CREATE TABLE IF NOT EXISTS `tk_label` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tk_label_category_id` int unsigned DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  `uri` varchar(255) DEFAULT NULL,
  `icon_url` varchar(500) DEFAULT NULL,
  `icon_file` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_code` (`code`),
  KEY `idx_tk_cat` (`tk_label_category_id`),
  CONSTRAINT `fk_tk_label_cat` FOREIGN KEY (`tk_label_category_id`) REFERENCES `tk_label_category` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default TK Labels
INSERT IGNORE INTO `tk_label` (`id`, `tk_label_category_id`, `code`, `uri`, `icon_url`, `is_active`, `sort_order`) VALUES
(1, 1, 'TK-A', 'https://localcontexts.org/label/tk-attribution/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Attribution.png', 1, 1),
(2, 1, 'TK-CL', 'https://localcontexts.org/label/tk-clan/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Clan.png', 1, 2),
(3, 1, 'TK-F', 'https://localcontexts.org/label/tk-family/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Family.png', 1, 3),
(4, 2, 'TK-MC', 'https://localcontexts.org/label/tk-multiple-communities/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Men_General.png', 1, 10),
(5, 2, 'TK-WG', 'https://localcontexts.org/label/tk-women-general/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Women_General.png', 1, 11),
(6, 2, 'TK-SS', 'https://localcontexts.org/label/tk-seasonal/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Seasonal.png', 1, 12),
(7, 2, 'TK-CV', 'https://localcontexts.org/label/tk-community-voice/', NULL, 1, 13),
(8, 2, 'TK-CS', 'https://localcontexts.org/label/tk-community-use-only/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Community_Use_Only.png', 1, 14),
(9, 2, 'TK-NC', 'https://localcontexts.org/label/tk-non-commercial/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_NonCommercial.png', 1, 15),
(10, 3, 'TK-V', 'https://localcontexts.org/label/tk-verified/', 'https://localcontexts.org/wp-content/uploads/2023/03/TK_Verified.png', 1, 20),
(11, 3, 'TK-CO', 'https://localcontexts.org/label/tk-open-to-commercialization/', NULL, 1, 21),
(12, 3, 'TK-OC', 'https://localcontexts.org/label/tk-outreach/', NULL, 1, 22);

-- Table: tk_label_category_i18n
CREATE TABLE IF NOT EXISTS `tk_label_category_i18n` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tk_label_category_id` bigint unsigned NOT NULL,
  `culture` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_cat_i18n` (`tk_label_category_id`,`culture`),
  KEY `idx_tk_cat_i18n_parent` (`tk_label_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tk_label_category_i18n
CREATE TABLE IF NOT EXISTS `tk_label_category_i18n` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tk_label_category_id` int unsigned NOT NULL,
  `culture` varchar(10) NOT NULL DEFAULT 'en',
  `name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_cat_i18n` (`tk_label_category_id`, `culture`),
  CONSTRAINT `fk_tk_cat_i18n` FOREIGN KEY (`tk_label_category_id`) REFERENCES `tk_label_category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tk_label_category_i18n` (`tk_label_category_id`, `culture`, `name`, `description`) VALUES
(1, 'en', 'Attribution', 'Labels for proper attribution and credit'),
(2, 'en', 'Protocol', 'Labels for cultural protocols and restrictions'),
(3, 'en', 'Provenance', 'Labels for provenance and verification');

-- Table: tk_label_i18n
CREATE TABLE IF NOT EXISTS `tk_label_i18n` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tk_label_id` bigint unsigned NOT NULL,
  `culture` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `usage_guide` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_i18n` (`tk_label_id`,`culture`),
  KEY `idx_tk_i18n_parent` (`tk_label_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tk_label_i18n
CREATE TABLE IF NOT EXISTS `tk_label_i18n` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tk_label_id` int unsigned NOT NULL,
  `culture` varchar(10) NOT NULL DEFAULT 'en',
  `name` varchar(100) NOT NULL,
  `description` text,
  `community_protocol` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk_i18n` (`tk_label_id`, `culture`),
  CONSTRAINT `fk_tk_label_i18n` FOREIGN KEY (`tk_label_id`) REFERENCES `tk_label` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tk_label_i18n` (`tk_label_id`, `culture`, `name`, `description`) VALUES
(1, 'en', 'TK Attribution', 'Corrects historical mistakes in attribution.'),
(2, 'en', 'TK Clan', 'Material associated with a specific clan.'),
(3, 'en', 'TK Family', 'Material with family ownership.'),
(4, 'en', 'TK Multiple Communities', 'Shared across multiple communities.'),
(5, 'en', 'TK Women General', 'Gender restrictions apply - women.'),
(6, 'en', 'TK Seasonal', 'Seasonal or time-based restrictions.'),
(7, 'en', 'TK Community Voice', 'Community protocols apply.'),
(8, 'en', 'TK Community Use Only', 'For community use only.'),
(9, 'en', 'TK Non-Commercial', 'Not for commercial use.'),
(10, 'en', 'TK Verified', 'Verified by community.'),
(11, 'en', 'TK-CO', 'Open to commercialization with permission.'),
(12, 'en', 'TK Open to Commercialization', 'Approved for outreach activities.');


-- ============================================================
-- ahgExtendedRightsPlugin - Database Schema
-- Generated from actual database structure
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: archive
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `extended_rights`
--

DROP TABLE IF EXISTS `extended_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `extended_rights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `rights_statement_id` bigint unsigned DEFAULT NULL,
  `creative_commons_license_id` bigint unsigned DEFAULT NULL,
  `rights_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `rights_holder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rights_holder_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ext_rights_object` (`object_id`),
  KEY `idx_ext_rights_rs` (`rights_statement_id`),
  KEY `idx_ext_rights_cc` (`creative_commons_license_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extended_rights_batch_log`
--

DROP TABLE IF EXISTS `extended_rights_batch_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `extended_rights_batch_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_count` int NOT NULL DEFAULT '0',
  `object_ids` json DEFAULT NULL,
  `data` json DEFAULT NULL,
  `results` json DEFAULT NULL,
  `performed_by` int DEFAULT NULL,
  `performed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_at` (`performed_at`),
  KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extended_rights_i18n`
--

DROP TABLE IF EXISTS `extended_rights_i18n`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `extended_rights_i18n` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `extended_rights_id` bigint unsigned NOT NULL,
  `culture` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `rights_note` text COLLATE utf8mb4_unicode_ci,
  `usage_conditions` text COLLATE utf8mb4_unicode_ci,
  `copyright_notice` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ext_rights_i18n` (`extended_rights_id`,`culture`),
  KEY `idx_ext_rights_i18n_parent` (`extended_rights_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extended_rights_tk_label`
--

DROP TABLE IF EXISTS `extended_rights_tk_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `extended_rights_tk_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `extended_rights_id` bigint unsigned NOT NULL,
  `tk_label_id` bigint unsigned NOT NULL,
  `community_id` int DEFAULT NULL,
  `community_note` text COLLATE utf8mb4_unicode_ci,
  `assigned_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ext_rights_tk` (`extended_rights_id`,`tk_label_id`),
  KEY `idx_ext_rights_tk_label` (`tk_label_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `embargo`
--

DROP TABLE IF EXISTS `embargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `embargo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `embargo_type` VARCHAR(55) COMMENT 'full, metadata_only, digital_object, custom' COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `is_perpetual` tinyint(1) DEFAULT '0',
  `status` VARCHAR(44) COMMENT 'active, expired, lifted, pending' COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_by` int DEFAULT NULL,
  `lifted_by` int DEFAULT NULL,
  `lifted_at` timestamp NULL DEFAULT NULL,
  `lift_reason` text COLLATE utf8mb4_unicode_ci,
  `notify_on_expiry` tinyint(1) DEFAULT '1',
  `notify_days_before` int DEFAULT '30',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_embargo_object` (`object_id`),
  KEY `idx_embargo_status` (`object_id`,`status`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_object_active` (`object_id`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `embargo_audit`
--

DROP TABLE IF EXISTS `embargo_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `embargo_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `embargo_id` bigint unsigned NOT NULL,
  `action` VARCHAR(83) COMMENT 'created, modified, lifted, extended, exception_added, exception_removed' COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emb_audit_embargo` (`embargo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `embargo_exception`
--

DROP TABLE IF EXISTS `embargo_exception`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `embargo_exception` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `embargo_id` bigint unsigned NOT NULL,
  `exception_type` VARCHAR(45) COMMENT 'user, group, ip_range, repository' COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception_id` int DEFAULT NULL,
  `ip_range_start` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_range_end` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `granted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emb_exc_embargo` (`embargo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `embargo_i18n`
--

DROP TABLE IF EXISTS `embargo_i18n`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `embargo_i18n` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `embargo_id` bigint unsigned NOT NULL,
  `culture` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `public_message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_embargo_i18n` (`embargo_id`,`culture`),
  KEY `idx_embargo_i18n_parent` (`embargo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 16:42:35

-- Creative Commons License tables
-- NOTE: These tables are defined in ahgRightsPlugin/data/install.sql
-- Do not duplicate here - ensure ahgRightsPlugin is installed first
