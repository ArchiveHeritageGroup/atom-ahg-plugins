-- ============================================================
-- ahgSecurityClearancePlugin - Database Schema
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
-- Table structure for table `object_security_classification`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `handling_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `inherit_to_children` tinyint(1) DEFAULT '1',
  `justification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_osc_object` (`object_id`),
  KEY `idx_osc_classification_review_declassify` (`classification_id`,`review_date`,`declassify_date`),
  KEY `idx_osc_assigned_by` (`assigned_by`),
  KEY `fk_osc_classified_by` (`classified_by`),
  CONSTRAINT `fk_osc_classification` FOREIGN KEY (`classification_id`) REFERENCES `security_classification` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_osc_classified_by` FOREIGN KEY (`classified_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_osc_object` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_2fa_session`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  UNIQUE KEY `unique_session` (`session_id`),
  KEY `idx_user_session` (`user_id`,`session_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_access_condition_link`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  KEY `idx_object` (`object_id`),
  KEY `idx_classification` (`classification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_access_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `security_access_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `object_id` int NOT NULL,
  `classification_id` int unsigned NOT NULL,
  `compartment_id` int unsigned DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_granted` tinyint(1) NOT NULL,
  `denial_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `prev_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sal_object` (`object_id`),
  KEY `idx_sal_user` (`user_id`),
  KEY `idx_sal_classification` (`classification_id`),
  KEY `idx_sal_entry_hash` (`entry_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_audit_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_category` (`action_category`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_classification`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `security_classification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_justification` tinyint(1) NOT NULL DEFAULT '0',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `requires_2fa` tinyint(1) NOT NULL DEFAULT '0',
  `max_session_hours` int DEFAULT NULL,
  `watermark_required` tinyint(1) NOT NULL DEFAULT '0',
  `watermark_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `download_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `print_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `copy_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_security_classification_level` (`level`),
  UNIQUE KEY `idx_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_clearance_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `security_clearance_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `previous_classification_id` int unsigned DEFAULT NULL,
  `new_classification_id` int unsigned DEFAULT NULL,
  `action` VARCHAR(95) COMMENT 'granted, upgraded, downgraded, revoked, renewed, expired, 2fa_enabled, 2fa_disabled' NOT NULL,
  `changed_by` int unsigned NOT NULL,
  `reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`created_at`),
  KEY `previous_classification_id` (`previous_classification_id`),
  KEY `new_classification_id` (`new_classification_id`),
  CONSTRAINT `security_clearance_history_ibfk_1` FOREIGN KEY (`previous_classification_id`) REFERENCES `security_classification` (`id`) ON DELETE SET NULL,
  CONSTRAINT `security_clearance_history_ibfk_2` FOREIGN KEY (`new_classification_id`) REFERENCES `security_classification` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_compartment`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `security_compartment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `min_clearance_id` int unsigned NOT NULL,
  `requires_need_to_know` tinyint(1) DEFAULT '1',
  `requires_briefing` tinyint(1) DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_active` (`active`),
  KEY `min_clearance_id` (`min_clearance_id`),
  CONSTRAINT `security_compartment_ibfk_1` FOREIGN KEY (`min_clearance_id`) REFERENCES `security_classification` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_compliance_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  KEY `idx_action` (`action`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_declassification_schedule`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `security_declassification_schedule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int unsigned NOT NULL,
  `scheduled_date` date NOT NULL,
  `from_classification_id` int unsigned NOT NULL,
  `to_classification_id` int unsigned DEFAULT NULL,
  `trigger_type` VARCHAR(34) COMMENT 'date, event, retention' NOT NULL DEFAULT 'date',
  `trigger_event` varchar(255) DEFAULT NULL,
  `processed` tinyint(1) DEFAULT '0',
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scheduled` (`scheduled_date`,`processed`),
  KEY `idx_object` (`object_id`),
  KEY `from_classification_id` (`from_classification_id`),
  KEY `to_classification_id` (`to_classification_id`),
  CONSTRAINT `security_declassification_schedule_ibfk_1` FOREIGN KEY (`from_classification_id`) REFERENCES `security_classification` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `security_declassification_schedule_ibfk_2` FOREIGN KEY (`to_classification_id`) REFERENCES `security_classification` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_retention_schedule`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_watermark_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `security_watermark_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `object_id` int unsigned NOT NULL,
  `digital_object_id` int unsigned DEFAULT NULL,
  `watermark_type` VARCHAR(36) COMMENT 'visible, invisible, both' NOT NULL DEFAULT 'visible',
  `watermark_text` varchar(500) NOT NULL,
  `watermark_code` varchar(100) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`created_at`),
  KEY `idx_object` (`object_id`),
  KEY `idx_code` (`watermark_code`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_security_clearance`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `user_security_clearance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `classification_id` int unsigned NOT NULL,
  `granted_by` int unsigned DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usc_user` (`user_id`),
  KEY `idx_usc_classification_id` (`classification_id`),
  KEY `idx_usc_expires_at` (`expires_at`),
  KEY `idx_usc_granted_by` (`granted_by`),
  CONSTRAINT `fk_usc_classification` FOREIGN KEY (`classification_id`) REFERENCES `security_classification` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_security_clearance_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `user_security_clearance_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `classification_id` int unsigned DEFAULT NULL,
  `action` VARCHAR(46) COMMENT 'granted, revoked, updated, expired' NOT NULL,
  `changed_by` int unsigned DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_changed_by` (`changed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 17:00:32

-- Seed Data
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
-- Dumping data for table `security_classification`
--

LOCK TABLES `security_classification` WRITE;
/*!40000 ALTER TABLE `security_classification` DISABLE KEYS */;
INSERT IGNORE INTO `security_classification` VALUES (1,'PUBLIC',0,'Public','Publicly accessible material',NULL,NULL,0,0,0,NULL,0,NULL,1,1,1,1,'2025-12-02 08:13:59','2025-12-04 13:58:31');
INSERT IGNORE INTO `security_classification` VALUES (2,'INTERNAL',1,'Internal','Internal institutional use',NULL,NULL,0,0,0,NULL,0,NULL,1,1,1,1,'2025-12-02 08:13:59','2025-12-04 13:58:31');
INSERT IGNORE INTO `security_classification` VALUES (3,'RESTRICTED',2,'Restricted','Restricted material, limited staff',NULL,NULL,1,0,0,NULL,0,NULL,1,1,0,1,'2025-12-02 08:13:59','2025-12-04 13:58:31');
INSERT IGNORE INTO `security_classification` VALUES (4,'CONFIDENTIAL',3,'Confidential','Confidential archival material',NULL,NULL,1,1,0,NULL,1,'confidential.png',0,0,0,1,'2025-12-02 08:13:59','2025-12-04 16:18:54');
INSERT IGNORE INTO `security_classification` VALUES (5,'SECRET',4,'Secret','Highly sensitive material',NULL,NULL,1,1,1,NULL,1,'secret_copyright.png',0,0,0,1,'2025-12-02 08:13:59','2025-12-04 16:18:54');
INSERT IGNORE INTO `security_classification` VALUES (7,'TOP_SECRET',5,'Top Secret',NULL,'#6f42c1','fa-user-secret',0,0,0,NULL,1,'top_secret_copyright.png',1,1,1,1,'2025-12-04 14:14:15','2025-12-04 16:18:54');
/*!40000 ALTER TABLE `security_classification` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 18:05:06


-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgSecurityClearancePlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: access_justification_template
CREATE TABLE IF NOT EXISTS `access_justification_template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `paia_section` varchar(50) DEFAULT NULL,
  `template_text` text NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: object_classification_history
CREATE TABLE IF NOT EXISTS `object_classification_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `previous_classification_id` int unsigned DEFAULT NULL,
  `new_classification_id` int unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `changed_by` int DEFAULT NULL,
  `reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object_id` (`object_id`),
  KEY `idx_changed_by` (`changed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: object_compartment
CREATE TABLE IF NOT EXISTS `object_compartment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int unsigned NOT NULL,
  `compartment_id` int unsigned NOT NULL,
  `assigned_by` int unsigned DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_object_compartment` (`object_id`,`compartment_id`),
  KEY `idx_compartment` (`compartment_id`),
  CONSTRAINT `object_compartment_ibfk_1` FOREIGN KEY (`compartment_id`) REFERENCES `security_compartment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table: object_declassification_schedule
CREATE TABLE IF NOT EXISTS `object_declassification_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `from_classification_id` int DEFAULT NULL,
  `to_classification_id` int DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `processed` tinyint(1) DEFAULT '0',
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_scheduled` (`scheduled_date`),
  KEY `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Security Classification Object relationship
CREATE TABLE IF NOT EXISTS security_classification_object (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    classification_id BIGINT UNSIGNED NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sco_object (object_id),
    INDEX idx_sco_classification (classification_id),
    UNIQUE KEY unique_object_classification (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: user_compartment_access
CREATE TABLE IF NOT EXISTS `user_compartment_access` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `compartment_id` int unsigned NOT NULL,
  `granted_by` int unsigned NOT NULL,
  `granted_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `briefing_date` date DEFAULT NULL,
  `briefing_reference` varchar(100) DEFAULT NULL,
  `notes` text,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_compartment` (`user_id`,`compartment_id`),
  KEY `idx_compartment` (`compartment_id`),
  KEY `idx_expiry` (`expiry_date`,`active`),
  CONSTRAINT `user_compartment_access_ibfk_1` FOREIGN KEY (`compartment_id`) REFERENCES `security_compartment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/acl_groups.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ahgSecurityClearancePlugin: AtoM-native group ACL model (groups + permission matrix).
-- Additive; run on the live archive DB. No core-table changes.
-- acl_permission follows AtoM's QubitAclPermission: object_id NULL = root/class-level;
-- `constants` holds JSON scope (e.g. {"repository":"slug"}); class derived via object.class_name join.

CREATE TABLE IF NOT EXISTS `acl_group` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED NULL,
    `source_culture` VARCHAR(16) NOT NULL DEFAULT 'en',
    `serial_number` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acl_group_i18n` (
    `id` BIGINT UNSIGNED NOT NULL,
    `culture` VARCHAR(16) NOT NULL,
    `name` VARCHAR(255),
    `description` TEXT,
    `serial_number` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`, `culture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acl_user_group` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `serial_number` INT NOT NULL DEFAULT 0,
    INDEX `idx_aug_user` (`user_id`),
    INDEX `idx_aug_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acl_permission` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `group_id` BIGINT UNSIGNED NULL,
    `object_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(40) NOT NULL,
    `grant_deny` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=grant, 0=deny',
    `conditional` TEXT NULL,
    `constants` TEXT NULL COMMENT 'JSON scope, e.g. {"repository":"slug"}',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL,
    `serial_number` INT NOT NULL DEFAULT 0,
    INDEX `idx_ap_group` (`group_id`),
    INDEX `idx_ap_user` (`user_id`),
    INDEX `idx_ap_object` (`object_id`),
    INDEX `idx_ap_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/add_audit_chain_columns.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- #126: tamper-evident hash chaining for the security access log.
-- Also restores compartment_id + session_id (logAccess() writes them but the
-- columns were missing, so every audit write silently failed).
ALTER TABLE `security_access_log`
  ADD COLUMN `compartment_id` INT UNSIGNED NULL AFTER `classification_id`,
  ADD COLUMN `session_id` VARCHAR(255) NULL AFTER `user_agent`,
  ADD COLUMN `prev_hash` CHAR(64) NULL COMMENT 'SHA-256 entry_hash of the previous entry',
  ADD COLUMN `entry_hash` CHAR(64) NULL COMMENT 'SHA-256(prev_hash || canonical(content))',
  ADD KEY `idx_sal_entry_hash` (`entry_hash`);

-- An audit trail must be append-only and independent of the lifecycle of the
-- things it records. Drop the FKs so deleting a described object (CASCADE) or
-- a classification (RESTRICT) can neither erase nor block audit history — the
-- ids remain as plain snapshot values and the hash chain stays intact.
ALTER TABLE `security_access_log` DROP FOREIGN KEY `fk_sal_object`;
ALTER TABLE `security_access_log` DROP FOREIGN KEY `fk_sal_classification`;

-- ---------------------------------------------------------------------------
-- Merged in from database/add_webauthn_credential_table.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- #126 / #721: WebAuthn / FIDO2 passkey credentials (MFA second factor).
CREATE TABLE IF NOT EXISTS `ahg_webauthn_credential` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `credential_id` VARBINARY(512) NOT NULL,
  `public_key` MEDIUMBLOB NOT NULL COMMENT 'serialized PublicKeyCredentialSource (JSON)',
  `attestation_type` VARCHAR(32) NOT NULL DEFAULT 'none',
  `aaguid` CHAR(36) DEFAULT NULL,
  `sign_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `transports` JSON DEFAULT NULL,
  `label` VARCHAR(255) NOT NULL DEFAULT '',
  `last_used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_webauthn_credential_id` (`credential_id`),
  KEY `idx_webauthn_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
