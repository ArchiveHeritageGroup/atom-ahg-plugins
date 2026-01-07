-- ============================================================
-- ahgResearchPlugin - Database Schema
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
-- Table structure for table `research_annotation`
--

DROP TABLE IF EXISTS `research_annotation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_annotation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int NOT NULL,
  `object_id` int NOT NULL,
  `digital_object_id` int DEFAULT NULL,
  `annotation_type` enum('note','highlight','bookmark','tag','transcription') DEFAULT 'note',
  `title` varchar(255) DEFAULT NULL,
  `content` text,
  `target_selector` text,
  `tags` varchar(500) DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_booking`
--

DROP TABLE IF EXISTS `research_booking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_booking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int NOT NULL,
  `reading_room_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text,
  `status` enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
  `confirmed_by` int DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_room` (`reading_room_id`),
  KEY `idx_date` (`booking_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_citation_log`
--

DROP TABLE IF EXISTS `research_citation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_citation_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int DEFAULT NULL,
  `object_id` int NOT NULL,
  `citation_style` varchar(50) NOT NULL,
  `citation_text` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2915 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_collection`
--

DROP TABLE IF EXISTS `research_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_collection` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `is_public` tinyint(1) DEFAULT '0',
  `share_token` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_researcher` (`researcher_id`),
  KEY `idx_share_token` (`share_token`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_collection_item`
--

DROP TABLE IF EXISTS `research_collection_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_collection_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `collection_id` int NOT NULL,
  `object_id` int NOT NULL,
  `notes` text,
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item` (`collection_id`,`object_id`),
  KEY `idx_collection` (`collection_id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_material_request`
--

DROP TABLE IF EXISTS `research_material_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_material_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `object_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `notes` text,
  `status` enum('requested','retrieved','delivered','in_use','returned','unavailable') DEFAULT 'requested',
  `retrieved_by` int DEFAULT NULL,
  `retrieved_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `condition_notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_object` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_password_reset`
--

DROP TABLE IF EXISTS `research_password_reset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_password_reset` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user` (`user_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_reading_room`
--

DROP TABLE IF EXISTS `research_reading_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_reading_room` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text,
  `amenities` text,
  `capacity` int DEFAULT '10',
  `location` varchar(255) DEFAULT NULL,
  `operating_hours` text,
  `rules` text,
  `advance_booking_days` int DEFAULT '14',
  `max_booking_hours` int DEFAULT '4',
  `cancellation_hours` int DEFAULT '24',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `opening_time` time DEFAULT '09:00:00',
  `closing_time` time DEFAULT '17:00:00',
  `days_open` varchar(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_researcher`
--

DROP TABLE IF EXISTS `research_researcher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_researcher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `affiliation_type` enum('academic','government','private','independent','student','other') DEFAULT 'independent',
  `institution` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `student_id` varchar(100) DEFAULT NULL,
  `research_interests` text,
  `current_project` text,
  `orcid_id` varchar(50) DEFAULT NULL,
  `id_type` enum('passport','national_id','drivers_license','student_card','other') DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `id_verified` tinyint(1) DEFAULT '0',
  `id_verified_by` int DEFAULT NULL,
  `id_verified_at` datetime DEFAULT NULL,
  `status` enum('pending','approved','suspended','expired','rejected') DEFAULT 'pending',
  `rejection_reason` text,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `research_saved_search`
--

DROP TABLE IF EXISTS `research_saved_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `research_saved_search` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `search_query` text NOT NULL,
  `search_filters` text,
  `search_type` varchar(50) DEFAULT 'informationobject',
  `alert_enabled` tinyint(1) DEFAULT '0',
  `alert_frequency` enum('daily','weekly','monthly') DEFAULT 'weekly',
  `last_alert_at` datetime DEFAULT NULL,
  `new_results_count` int DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_researcher` (`researcher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
