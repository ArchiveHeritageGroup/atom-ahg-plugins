-- ============================================================
-- ahgConditionPlugin - Database Schema
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
-- Table structure for table `condition_assessment_schedule`
--

DROP TABLE IF EXISTS `condition_assessment_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_assessment_schedule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `frequency_months` int DEFAULT '12',
  `last_assessment_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `notes` text,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_due` (`next_due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_conservation_link`
--

DROP TABLE IF EXISTS `condition_conservation_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_conservation_link` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `condition_event_id` int unsigned NOT NULL,
  `treatment_id` int unsigned NOT NULL,
  `link_type` varchar(50) DEFAULT 'treatment',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event` (`condition_event_id`),
  KEY `idx_treatment` (`treatment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_damage`
--

DROP TABLE IF EXISTS `condition_damage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_damage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `condition_report_id` bigint unsigned NOT NULL,
  `damage_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'overall',
  `severity` enum('minor','moderate','severe') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'minor',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dimensions` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `treatment_required` tinyint(1) NOT NULL DEFAULT '0',
  `treatment_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cd_report` (`condition_report_id`),
  KEY `idx_cd_type` (`damage_type`),
  KEY `idx_cd_severity` (`severity`),
  CONSTRAINT `condition_damage_condition_report_id_foreign` FOREIGN KEY (`condition_report_id`) REFERENCES `condition_report` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_event`
--

DROP TABLE IF EXISTS `condition_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_event` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `event_type` varchar(50) NOT NULL DEFAULT 'assessment',
  `event_date` date NOT NULL,
  `assessor` varchar(255) DEFAULT NULL,
  `condition_status` varchar(50) DEFAULT NULL,
  `damage_types` json DEFAULT NULL,
  `severity` varchar(50) DEFAULT NULL,
  `notes` text,
  `risk_score` decimal(5,2) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_id`),
  KEY `idx_date` (`event_date`),
  KEY `idx_status` (`condition_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_image`
--

DROP TABLE IF EXISTS `condition_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_image` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `condition_report_id` bigint unsigned NOT NULL,
  `digital_object_id` int unsigned DEFAULT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_type` enum('general','detail','damage','before','after','raking','uv') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `annotations` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ci_report` (`condition_report_id`),
  CONSTRAINT `condition_image_condition_report_id_foreign` FOREIGN KEY (`condition_report_id`) REFERENCES `condition_report` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_report`
--

DROP TABLE IF EXISTS `condition_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_report` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `information_object_id` int unsigned NOT NULL,
  `assessor_user_id` int unsigned DEFAULT NULL,
  `assessment_date` date NOT NULL,
  `context` enum('acquisition','loan_out','loan_in','loan_return','exhibition','storage','conservation','routine','incident','insurance','deaccession') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'routine',
  `overall_rating` enum('excellent','good','fair','poor','unacceptable') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recommendations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','normal','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `next_check_date` date DEFAULT NULL,
  `environmental_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `handling_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `display_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `storage_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cr_object` (`information_object_id`),
  KEY `idx_cr_date` (`assessment_date`),
  KEY `idx_cr_rating` (`overall_rating`),
  KEY `idx_cr_next_check` (`next_check_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_vocabulary`
--

DROP TABLE IF EXISTS `condition_vocabulary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_vocabulary` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `vocabulary_type` enum('damage_type','severity','condition','priority','material','location_zone') COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'For UI display',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FontAwesome icon class',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_code` (`vocabulary_type`,`code`),
  KEY `idx_type_active` (`vocabulary_type`,`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `condition_vocabulary_term`
--

DROP TABLE IF EXISTS `condition_vocabulary_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `condition_vocabulary_term` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `vocabulary_type` varchar(50) NOT NULL,
  `term_code` varchar(50) NOT NULL,
  `term_label` varchar(255) NOT NULL,
  `term_description` text,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vocab_term` (`vocabulary_type`,`term_code`),
  KEY `idx_type` (`vocabulary_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_check`
--

DROP TABLE IF EXISTS `spectrum_condition_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_check` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `condition_reference` varchar(50) DEFAULT NULL,
  `check_date` datetime NOT NULL,
  `check_reason` varchar(100) DEFAULT NULL,
  `checked_by` varchar(255) NOT NULL,
  `overall_condition` varchar(50) DEFAULT NULL,
  `condition_note` text,
  `completeness_note` text,
  `hazard_note` text,
  `technical_assessment` text,
  `recommended_treatment` text,
  `treatment_priority` varchar(50) DEFAULT NULL,
  `next_check_date` date DEFAULT NULL,
  `environment_recommendation` text,
  `handling_recommendation` text,
  `display_recommendation` text,
  `storage_recommendation` text,
  `packing_recommendation` text,
  `image_reference` text,
  `photo_count` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `condition_check_reference` varchar(255) DEFAULT NULL,
  `completeness` varchar(50) DEFAULT NULL,
  `condition_description` text,
  `hazards_noted` text,
  `recommendations` text,
  `workflow_state` varchar(50) DEFAULT 'scheduled',
  `condition_rating` varchar(50) DEFAULT NULL COMMENT 'Overall condition rating',
  `condition_notes` text COMMENT 'Detailed condition notes',
  `template_id` int DEFAULT NULL,
  `material_type` varchar(50) DEFAULT NULL,
  `template_data` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `idx_condition_date` (`check_date`),
  KEY `idx_condition_reference` (`condition_reference`),
  KEY `idx_overall_condition` (`overall_condition`),
  KEY `idx_wf_cond` (`workflow_state`),
  KEY `idx_check_date` (`check_date`),
  CONSTRAINT `spectrum_condition_check_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_check_data`
--

DROP TABLE IF EXISTS `spectrum_condition_check_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_check_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `condition_check_id` int NOT NULL,
  `template_id` int NOT NULL,
  `field_id` int NOT NULL,
  `field_value` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_check_field` (`condition_check_id`,`field_id`),
  KEY `template_id` (`template_id`),
  KEY `field_id` (`field_id`),
  KEY `idx_check` (`condition_check_id`),
  CONSTRAINT `spectrum_condition_check_data_ibfk_1` FOREIGN KEY (`condition_check_id`) REFERENCES `spectrum_condition_check` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spectrum_condition_check_data_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `spectrum_condition_template` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spectrum_condition_check_data_ibfk_3` FOREIGN KEY (`field_id`) REFERENCES `spectrum_condition_template_field` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_photo`
--

DROP TABLE IF EXISTS `spectrum_condition_photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_photo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `condition_check_id` int NOT NULL,
  `digital_object_id` int DEFAULT NULL,
  `photo_type` enum('before','after','detail','damage','overall','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detail',
  `caption` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location_on_object` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `photographer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_date` date DEFAULT NULL,
  `camera_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0',
  `annotations` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `digital_object_id` (`digital_object_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_condition_check` (`condition_check_id`),
  KEY `idx_photo_type` (`photo_type`),
  KEY `idx_photo_date` (`photo_date`),
  KEY `idx_primary` (`is_primary`),
  CONSTRAINT `spectrum_condition_photo_ibfk_1` FOREIGN KEY (`condition_check_id`) REFERENCES `spectrum_condition_check` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spectrum_condition_photo_ibfk_2` FOREIGN KEY (`digital_object_id`) REFERENCES `digital_object` (`id`) ON DELETE SET NULL,
  CONSTRAINT `spectrum_condition_photo_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `spectrum_condition_photo_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_photo_comparison`
--

DROP TABLE IF EXISTS `spectrum_condition_photo_comparison`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_photo_comparison` (
  `id` int NOT NULL AUTO_INCREMENT,
  `condition_check_id` int NOT NULL,
  `before_photo_id` int NOT NULL,
  `after_photo_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_condition_check` (`condition_check_id`),
  CONSTRAINT `spectrum_condition_photo_comparison_ibfk_1` FOREIGN KEY (`condition_check_id`) REFERENCES `spectrum_condition_check` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_photos`
--

DROP TABLE IF EXISTS `spectrum_condition_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `condition_check_id` int NOT NULL COMMENT 'Reference to spectrum_condition_check',
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Stored filename',
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original uploaded filename',
  `category` enum('overall','detail','damage','before','after','reference') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'overall' COMMENT 'Photo category',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Description or notes about the photo',
  `annotations` json DEFAULT NULL COMMENT 'JSON annotations for damage markers',
  `file_size` int DEFAULT NULL COMMENT 'File size in bytes',
  `mime_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MIME type of the image',
  `width` int DEFAULT NULL COMMENT 'Image width in pixels',
  `height` int DEFAULT NULL COMMENT 'Image height in pixels',
  `captured_at` datetime DEFAULT NULL COMMENT 'When photo was taken (from EXIF)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL COMMENT 'User who uploaded the photo',
  `updated_at` datetime DEFAULT NULL COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_check` (`condition_check_id`),
  KEY `idx_category` (`category`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_template`
--

DROP TABLE IF EXISTS `spectrum_condition_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `material_type` varchar(100) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `is_default` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_material_type` (`material_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_template_field`
--

DROP TABLE IF EXISTS `spectrum_condition_template_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_template_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `section_id` int NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('text','textarea','select','multiselect','checkbox','radio','rating','date','number') NOT NULL,
  `options` json DEFAULT NULL COMMENT 'For select/multiselect/radio - array of options',
  `default_value` varchar(255) DEFAULT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `help_text` text,
  `is_required` tinyint(1) DEFAULT '0',
  `validation_rules` json DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_section` (`section_id`),
  CONSTRAINT `spectrum_condition_template_field_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `spectrum_condition_template_section` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum_condition_template_section`
--

DROP TABLE IF EXISTS `spectrum_condition_template_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `spectrum_condition_template_section` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `sort_order` int DEFAULT '0',
  `is_required` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  CONSTRAINT `spectrum_condition_template_section_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `spectrum_condition_template` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- View for condition with photos
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
-- Dumping data for table `condition_vocabulary`
--

LOCK TABLES `condition_vocabulary` WRITE;
/*!40000 ALTER TABLE `condition_vocabulary` DISABLE KEYS */;
INSERT IGNORE INTO `condition_vocabulary` VALUES (1,'damage_type','tear','Tear','Physical tear or rip in material','#dc3545',NULL,10,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (2,'damage_type','stain','Stain','Discoloration or marks','#fd7e14',NULL,20,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (3,'damage_type','foxing','Foxing','Brown spots typically on paper','#ffc107',NULL,30,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (4,'damage_type','fading','Fading','Loss of color intensity','#6c757d',NULL,40,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (5,'damage_type','water_damage','Water Damage','Damage from moisture or flooding','#0dcaf0',NULL,50,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (6,'damage_type','mold','Mold/Mildew','Fungal growth','#198754',NULL,60,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (7,'damage_type','pest_damage','Pest Damage','Damage from insects or rodents','#6f42c1',NULL,70,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (8,'damage_type','abrasion','Abrasion','Surface wear or scratching','#adb5bd',NULL,80,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (9,'damage_type','brittleness','Brittleness','Material becoming fragile','#495057',NULL,90,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (10,'damage_type','loss','Loss/Missing','Missing portions of material','#212529',NULL,100,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (11,'severity','minor','Minor','Minimal impact, low priority','#28a745',NULL,10,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (12,'severity','moderate','Moderate','Noticeable damage, should address','#ffc107',NULL,20,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (13,'severity','severe','Severe','Significant damage requiring attention','#fd7e14',NULL,30,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (14,'severity','critical','Critical','Immediate action required','#dc3545',NULL,40,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (15,'condition','excellent','Excellent','Like new, no visible issues','#198754',NULL,10,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (16,'condition','good','Good','Minor wear consistent with age','#28a745',NULL,20,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (17,'condition','fair','Fair','Some damage but stable','#ffc107',NULL,30,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (18,'condition','poor','Poor','Significant damage or deterioration','#fd7e14',NULL,40,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (19,'condition','critical','Critical','Severe damage, at risk','#dc3545',NULL,50,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (20,'priority','low','Low','Can be addressed when convenient','#6c757d',NULL,10,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (21,'priority','medium','Medium','Should be addressed in normal workflow','#17a2b8',NULL,20,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (22,'priority','high','High','Needs prompt attention','#fd7e14',NULL,30,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (23,'priority','urgent','Urgent','Requires immediate action','#dc3545',NULL,40,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (24,'material','paper','Paper','Paper-based materials','#f8f9fa',NULL,10,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (25,'material','parchment','Parchment/Vellum','Animal skin materials','#e9ecef',NULL,20,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (26,'material','textile','Textile','Fabric and cloth materials','#dee2e6',NULL,30,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (27,'material','leather','Leather','Leather bindings and materials','#795548',NULL,40,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (28,'material','photographic','Photographic','Photos, negatives, slides','#212529',NULL,50,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (29,'material','metal','Metal','Metal objects or components','#adb5bd',NULL,60,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (30,'material','wood','Wood','Wooden items or frames','#8d6e63',NULL,70,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (31,'material','glass','Glass','Glass plates, frames','#90caf9',NULL,80,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (32,'material','plastic','Plastic/Polymer','Synthetic materials','#ce93d8',NULL,90,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (33,'material','audiovisual','Audiovisual','Tapes, films, discs','#424242',NULL,100,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (34,'location_zone','recto','Recto (Front)','Front side of item','#e3f2fd',NULL,10,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (35,'location_zone','verso','Verso (Back)','Back side of item','#fce4ec',NULL,20,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (36,'location_zone','edge_top','Top Edge','Top edge of item','#f3e5f5',NULL,30,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (37,'location_zone','edge_bottom','Bottom Edge','Bottom edge of item','#e8eaf6',NULL,40,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (38,'location_zone','edge_left','Left Edge','Left edge of item','#e0f2f1',NULL,50,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (39,'location_zone','edge_right','Right Edge','Right edge of item','#fff3e0',NULL,60,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (40,'location_zone','spine','Spine','Spine/binding area','#efebe9',NULL,70,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (41,'location_zone','cover_front','Front Cover','Front cover of bound item','#eceff1',NULL,80,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (42,'location_zone','cover_back','Back Cover','Back cover of bound item','#fafafa',NULL,90,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
INSERT IGNORE INTO `condition_vocabulary` VALUES (43,'location_zone','center','Center','Central area of item','#fff8e1',NULL,100,1,'2025-12-19 10:05:43','2025-12-19 10:05:43');
/*!40000 ALTER TABLE `condition_vocabulary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `condition_vocabulary_term`
--

LOCK TABLES `condition_vocabulary_term` WRITE;
/*!40000 ALTER TABLE `condition_vocabulary_term` DISABLE KEYS */;
/*!40000 ALTER TABLE `condition_vocabulary_term` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

