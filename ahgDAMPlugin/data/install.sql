-- ============================================================
-- ahgDAMPlugin - Database Schema
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
-- Table structure for table `dam_iptc_metadata`
--

DROP TABLE IF EXISTS `dam_iptc_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dam_iptc_metadata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `creator` varchar(255) DEFAULT NULL,
  `creator_job_title` varchar(255) DEFAULT NULL,
  `creator_address` text,
  `creator_city` varchar(255) DEFAULT NULL,
  `creator_state` varchar(255) DEFAULT NULL,
  `creator_postal_code` varchar(50) DEFAULT NULL,
  `creator_country` varchar(255) DEFAULT NULL,
  `creator_phone` varchar(100) DEFAULT NULL,
  `creator_email` varchar(255) DEFAULT NULL,
  `creator_website` varchar(500) DEFAULT NULL,
  `headline` varchar(500) DEFAULT NULL,
  `caption` text,
  `keywords` text,
  `iptc_subject_code` varchar(255) DEFAULT NULL,
  `intellectual_genre` varchar(255) DEFAULT NULL,
  `iptc_scene` varchar(255) DEFAULT NULL,
  `date_created` date DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state_province` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `sublocation` varchar(500) DEFAULT NULL,
  `title` varchar(500) DEFAULT NULL,
  `job_id` varchar(255) DEFAULT NULL,
  `instructions` text,
  `credit_line` varchar(500) DEFAULT NULL,
  `source` varchar(500) DEFAULT NULL,
  `copyright_notice` text,
  `rights_usage_terms` text,
  `license_type` enum('rights_managed','royalty_free','creative_commons','public_domain','editorial','other') DEFAULT NULL,
  `license_url` varchar(500) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `model_release_status` enum('none','not_applicable','unlimited','limited') DEFAULT 'none',
  `model_release_id` varchar(255) DEFAULT NULL,
  `property_release_status` enum('none','not_applicable','unlimited','limited') DEFAULT 'none',
  `property_release_id` varchar(255) DEFAULT NULL,
  `artwork_title` varchar(500) DEFAULT NULL,
  `artwork_creator` varchar(255) DEFAULT NULL,
  `artwork_date` varchar(100) DEFAULT NULL,
  `artwork_source` varchar(500) DEFAULT NULL,
  `artwork_copyright` text,
  `persons_shown` text,
  `camera_make` varchar(100) DEFAULT NULL,
  `camera_model` varchar(100) DEFAULT NULL,
  `lens` varchar(255) DEFAULT NULL,
  `focal_length` varchar(50) DEFAULT NULL,
  `aperture` varchar(20) DEFAULT NULL,
  `shutter_speed` varchar(50) DEFAULT NULL,
  `iso_speed` int DEFAULT NULL,
  `flash_used` tinyint(1) DEFAULT NULL,
  `gps_latitude` decimal(10,8) DEFAULT NULL,
  `gps_longitude` decimal(11,8) DEFAULT NULL,
  `gps_altitude` decimal(10,2) DEFAULT NULL,
  `image_width` int DEFAULT NULL,
  `image_height` int DEFAULT NULL,
  `resolution_x` int DEFAULT NULL,
  `resolution_y` int DEFAULT NULL,
  `resolution_unit` varchar(20) DEFAULT NULL,
  `color_space` varchar(50) DEFAULT NULL,
  `bit_depth` int DEFAULT NULL,
  `orientation` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_id` (`object_id`),
  KEY `idx_object_id` (`object_id`),
  KEY `idx_creator` (`creator`),
  KEY `idx_keywords` (`keywords`(255)),
  KEY `idx_date_created` (`date_created`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 17:01:31

-- =====================================================
-- DAM Display Standard Term (taxonomy_id = 70)
-- =====================================================
INSERT INTO term (taxonomy_id, code, source_culture)
SELECT 70, 'dam', 'en' FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM term WHERE code = 'dam' AND taxonomy_id = 70);

SET @dam_id = (SELECT id FROM term WHERE code = 'dam' AND taxonomy_id = 70);

INSERT IGNORE INTO term_i18n (id, culture, name)
VALUES (@dam_id, 'en', 'Photo/DAM (IPTC/XMP)');
