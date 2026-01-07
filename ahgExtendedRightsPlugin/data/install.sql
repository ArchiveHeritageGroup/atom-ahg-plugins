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
  `embargo_type` enum('full','metadata_only','digital_object','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `is_perpetual` tinyint(1) DEFAULT '0',
  `status` enum('active','expired','lifted','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
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
  `action` enum('created','modified','lifted','extended','exception_added','exception_removed') COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `exception_type` enum('user','group','ip_range','repository') COLLATE utf8mb4_unicode_ci NOT NULL,
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

-- Creative Commons Licenses
CREATE TABLE IF NOT EXISTS `creative_commons_license` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `uri` varchar(255) NOT NULL,
    `icon_url` varchar(255) DEFAULT NULL,
    `code` varchar(30) NOT NULL,
    `version` varchar(10) DEFAULT '4.0',
    `allows_adaptation` tinyint(1) DEFAULT 1,
    `allows_commercial` tinyint(1) DEFAULT 1,
    `requires_attribution` tinyint(1) DEFAULT 1,
    `requires_sharealike` tinyint(1) DEFAULT 0,
    `icon_filename` varchar(100) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uri` (`uri`),
    UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Creative Commons licenses
INSERT INTO `creative_commons_license` (`uri`, `icon_url`, `code`, `version`, `allows_adaptation`, `allows_commercial`, `requires_attribution`, `requires_sharealike`, `icon_filename`, `is_active`, `sort_order`) VALUES
('https://creativecommons.org/publicdomain/zero/1.0/', 'https://licensebuttons.net/l/zero/1.0/88x31.png', 'CC0-1.0', '1.0', 1, 1, 0, 0, 'cc-zero.png', 1, 1),
('https://creativecommons.org/licenses/by/4.0/', 'https://licensebuttons.net/l/by/4.0/88x31.png', 'CC-BY-4.0', '4.0', 1, 1, 1, 0, 'cc-by.png', 1, 2),
('https://creativecommons.org/licenses/by-sa/4.0/', 'https://licensebuttons.net/l/by-sa/4.0/88x31.png', 'CC-BY-SA-4.0', '4.0', 1, 1, 1, 1, 'cc-by-sa.png', 1, 3),
('https://creativecommons.org/licenses/by-nc/4.0/', 'https://licensebuttons.net/l/by-nc/4.0/88x31.png', 'CC-BY-NC-4.0', '4.0', 1, 0, 1, 0, 'cc-by-nc.png', 1, 4),
('https://creativecommons.org/licenses/by-nc-sa/4.0/', 'https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png', 'CC-BY-NC-SA-4.0', '4.0', 1, 0, 1, 1, 'cc-by-nc-sa.png', 1, 5),
('https://creativecommons.org/licenses/by-nd/4.0/', 'https://licensebuttons.net/l/by-nd/4.0/88x31.png', 'CC-BY-ND-4.0', '4.0', 0, 1, 1, 0, 'cc-by-nd.png', 1, 6),
('https://creativecommons.org/licenses/by-nc-nd/4.0/', 'https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png', 'CC-BY-NC-ND-4.0', '4.0', 0, 0, 1, 0, 'cc-by-nc-nd.png', 1, 7),
('https://creativecommons.org/publicdomain/mark/1.0/', NULL, 'PDM-1.0', '1.0', 1, 1, 0, 0, 'publicdomain.png', 1, 8)
ON DUPLICATE KEY UPDATE `is_active` = VALUES(`is_active`);
