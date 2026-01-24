-- ============================================================
-- ahgRicExplorerPlugin - Database Schema
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
-- Table structure for table `ric_orphan_tracking`
--

DROP TABLE IF EXISTS `ric_orphan_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ric_orphan_tracking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ric_uri` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ric_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected_entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected_entity_id` int DEFAULT NULL,
  `detected_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `detection_method` enum('integrity_check','sync_failure','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('detected','reviewed','cleaned','retained','restored') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detected',
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `triple_count` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ric_orphan_uri` (`ric_uri`(255)),
  KEY `idx_ric_orphan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `ric_queue_status`
--

DROP TABLE IF EXISTS `ric_queue_status`;
/*!50001 DROP VIEW IF EXISTS `ric_queue_status`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `ric_queue_status` AS SELECT 
 1 AS `status`,
 1 AS `count`,
 1 AS `oldest`,
 1 AS `newest`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `ric_recent_operations`
--

DROP TABLE IF EXISTS `ric_recent_operations`;
/*!50001 DROP VIEW IF EXISTS `ric_recent_operations`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `ric_recent_operations` AS SELECT 
 1 AS `id`,
 1 AS `operation`,
 1 AS `entity_type`,
 1 AS `entity_id`,
 1 AS `ric_uri`,
 1 AS `status`,
 1 AS `triples_affected`,
 1 AS `details`,
 1 AS `error_message`,
 1 AS `execution_time_ms`,
 1 AS `triggered_by`,
 1 AS `user_id`,
 1 AS `batch_id`,
 1 AS `created_at`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ric_sync_config`
--

DROP TABLE IF EXISTS `ric_sync_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ric_sync_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ric_config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ric_sync_log`
--

DROP TABLE IF EXISTS `ric_sync_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ric_sync_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `operation` enum('create','update','delete','move','resync','cleanup','integrity_check') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `ric_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('success','failure','partial','skipped') COLLATE utf8mb4_unicode_ci NOT NULL,
  `triples_affected` int DEFAULT NULL,
  `details` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `execution_time_ms` int DEFAULT NULL,
  `triggered_by` enum('user','system','cron','api','cli') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `user_id` int DEFAULT NULL,
  `batch_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ric_log_entity` (`entity_type`,`entity_id`),
  KEY `idx_ric_log_date` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ric_sync_queue`
--

DROP TABLE IF EXISTS `ric_sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ric_sync_queue` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `operation` enum('create','update','delete','move') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` tinyint NOT NULL DEFAULT '5',
  `status` enum('queued','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `attempts` int NOT NULL DEFAULT '0',
  `max_attempts` int NOT NULL DEFAULT '3',
  `old_parent_id` int DEFAULT NULL,
  `new_parent_id` int DEFAULT NULL,
  `scheduled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ric_queue_status` (`status`,`priority`,`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ric_sync_status`
--

DROP TABLE IF EXISTS `ric_sync_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ric_sync_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `ric_uri` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ric_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_status` enum('synced','pending','failed','deleted','orphaned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_synced_at` datetime DEFAULT NULL,
  `last_sync_attempt` datetime DEFAULT NULL,
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  `retry_count` int NOT NULL DEFAULT '0',
  `content_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atom_updated_at` datetime DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `hierarchy_path` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ric_sync_entity` (`entity_type`,`entity_id`),
  KEY `idx_ric_sync_uri` (`ric_uri`(255)),
  KEY `idx_ric_sync_status` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `ric_sync_summary`
--

DROP TABLE IF EXISTS `ric_sync_summary`;
/*!50001 DROP VIEW IF EXISTS `ric_sync_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `ric_sync_summary` AS SELECT 
 1 AS `entity_type`,
 1 AS `sync_status`,
 1 AS `count`,
 1 AS `last_sync`,
 1 AS `with_retries`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `ric_queue_status`
--

/*!50001 DROP VIEW IF EXISTS `ric_queue_status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `ric_queue_status` AS select `ric_sync_queue`.`status` AS `status`,count(0) AS `count`,min(`ric_sync_queue`.`scheduled_at`) AS `oldest`,max(`ric_sync_queue`.`scheduled_at`) AS `newest` from `ric_sync_queue` group by `ric_sync_queue`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ric_recent_operations`
--

/*!50001 DROP VIEW IF EXISTS `ric_recent_operations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `ric_recent_operations` AS select `ric_sync_log`.`id` AS `id`,`ric_sync_log`.`operation` AS `operation`,`ric_sync_log`.`entity_type` AS `entity_type`,`ric_sync_log`.`entity_id` AS `entity_id`,`ric_sync_log`.`ric_uri` AS `ric_uri`,`ric_sync_log`.`status` AS `status`,`ric_sync_log`.`triples_affected` AS `triples_affected`,`ric_sync_log`.`details` AS `details`,`ric_sync_log`.`error_message` AS `error_message`,`ric_sync_log`.`execution_time_ms` AS `execution_time_ms`,`ric_sync_log`.`triggered_by` AS `triggered_by`,`ric_sync_log`.`user_id` AS `user_id`,`ric_sync_log`.`batch_id` AS `batch_id`,`ric_sync_log`.`created_at` AS `created_at` from `ric_sync_log` order by `ric_sync_log`.`created_at` desc limit 100 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ric_sync_summary`
--

/*!50001 DROP VIEW IF EXISTS `ric_sync_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `ric_sync_summary` AS select `ric_sync_status`.`entity_type` AS `entity_type`,`ric_sync_status`.`sync_status` AS `sync_status`,count(0) AS `count`,max(`ric_sync_status`.`last_synced_at`) AS `last_sync`,sum((case when (`ric_sync_status`.`retry_count` > 0) then 1 else 0 end)) AS `with_retries` from `ric_sync_status` group by `ric_sync_status`.`entity_type`,`ric_sync_status`.`sync_status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 17:15:59
