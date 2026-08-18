-- ============================================================
-- ahg3DModelPlugin - Database Schema
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
-- Table structure for table `iiif_3d_manifest`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `iiif_3d_manifest` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL,
  `manifest_json` longtext,
  `manifest_hash` varchar(64) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_id` (`model_id`),
  KEY `idx_model_id` (`model_id`),
  CONSTRAINT `iiif_3d_manifest_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `object_3d_model` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_audit_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int DEFAULT NULL,
  `object_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` VARCHAR(88) COMMENT 'upload, update, delete, view, ar_view, download, hotspot_add, hotspot_delete' NOT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_object_id` (`object_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_hotspot`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_hotspot` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL,
  `hotspot_type` VARCHAR(50) COMMENT 'annotation, info, link, damage, detail' DEFAULT 'annotation',
  `position_x` decimal(10,6) NOT NULL,
  `position_y` decimal(10,6) NOT NULL,
  `position_z` decimal(10,6) NOT NULL,
  `normal_x` decimal(10,6) DEFAULT '0.000000',
  `normal_y` decimal(10,6) DEFAULT '1.000000',
  `normal_z` decimal(10,6) DEFAULT '0.000000',
  `icon` varchar(50) DEFAULT 'info',
  `color` varchar(20) DEFAULT '#1a73e8',
  `link_url` varchar(500) DEFAULT NULL,
  `link_target` VARCHAR(25) COMMENT '_self, _blank' DEFAULT '_blank',
  `display_order` int DEFAULT '0',
  `is_visible` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  CONSTRAINT `object_3d_hotspot_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `object_3d_model` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_hotspot_i18n`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_hotspot_i18n` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotspot_id` int NOT NULL,
  `culture` varchar(10) NOT NULL DEFAULT 'en',
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hotspot_culture` (`hotspot_id`,`culture`),
  CONSTRAINT `object_3d_hotspot_i18n_ibfk_1` FOREIGN KEY (`hotspot_id`) REFERENCES `object_3d_hotspot` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_model`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_model` (
  `id` int NOT NULL AUTO_INCREMENT,
  `object_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `format` VARCHAR(47) COMMENT 'glb, gltf, obj, stl, ply, usdz' DEFAULT 'glb',
  `vertex_count` int DEFAULT NULL,
  `face_count` int DEFAULT NULL,
  `texture_count` int DEFAULT NULL,
  `animation_count` int DEFAULT '0',
  `has_materials` tinyint(1) DEFAULT '1',
  `auto_rotate` tinyint(1) DEFAULT '1',
  `rotation_speed` decimal(3,2) DEFAULT '1.00',
  `camera_orbit` varchar(100) DEFAULT '0deg 75deg 105%',
  `min_camera_orbit` varchar(100) DEFAULT NULL,
  `max_camera_orbit` varchar(100) DEFAULT NULL,
  `field_of_view` varchar(20) DEFAULT '30deg',
  `exposure` decimal(3,2) DEFAULT '1.00',
  `shadow_intensity` decimal(3,2) DEFAULT '1.00',
  `shadow_softness` decimal(3,2) DEFAULT '1.00',
  `environment_image` varchar(255) DEFAULT NULL,
  `skybox_image` varchar(255) DEFAULT NULL,
  `background_color` varchar(20) DEFAULT '#f5f5f5',
  `ar_enabled` tinyint(1) DEFAULT '1',
  `ar_scale` varchar(20) DEFAULT 'auto',
  `ar_placement` VARCHAR(23) COMMENT 'floor, wall' DEFAULT 'floor',
  `poster_image` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '1',
  `display_order` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `turntable_mp4_path` varchar(500) DEFAULT NULL,
  `turntable_generated_at` datetime DEFAULT NULL,
  `real_width` decimal(12,4) DEFAULT NULL,
  `real_height` decimal(12,4) DEFAULT NULL,
  `real_depth` decimal(12,4) DEFAULT NULL,
  `dimension_unit` varchar(16) DEFAULT NULL COMMENT 'dropdown model_3d_units',
  `scale_note` varchar(64) DEFAULT NULL,
  `coordinate_system` varchar(16) DEFAULT NULL COMMENT 'dropdown model_3d_coordinate_system',
  `bounding_box` varchar(96) DEFAULT NULL COMMENT 'auto: minX,minY,minZ maxX,maxY,maxZ (model units)',
  `format_version` varchar(32) DEFAULT NULL COMMENT 'auto: e.g. glTF 2.0',
  `compression` varchar(24) DEFAULT NULL COMMENT 'dropdown model_3d_compression',
  `is_lossless_master` tinyint(1) DEFAULT NULL,
  `pbr_maps` varchar(128) DEFAULT NULL COMMENT 'baseColor,normal,metalRough,occlusion,emissive',
  `texture_colorspace` varchar(24) DEFAULT NULL,
  `lod_levels` int DEFAULT NULL,
  `is_watertight` tinyint(1) DEFAULT NULL,
  `has_rig` tinyint(1) DEFAULT NULL,
  `capture_method` varchar(40) DEFAULT NULL COMMENT 'dropdown model_3d_capture_method',
  `capture_device` varchar(255) DEFAULT NULL,
  `capture_date` date DEFAULT NULL,
  `capture_operator` varchar(255) DEFAULT NULL,
  `source_count` int DEFAULT NULL COMMENT 'e.g. number of source photos',
  `point_density` varchar(64) DEFAULT NULL,
  `accuracy_mm` decimal(8,3) DEFAULT NULL,
  `processing_software` varchar(255) DEFAULT NULL,
  `processing_notes` text,
  `georeference` varchar(255) DEFAULT NULL,
  `model_author` varchar(255) DEFAULT NULL,
  `derivation_note` text,
  `model_license` varchar(100) DEFAULT NULL COMMENT 'dropdown model_3d_licence',
  `model_license_holder` varchar(255) DEFAULT NULL,
  `attribution` varchar(500) DEFAULT NULL,
  `alt_text` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_object_id` (`object_id`),
  KEY `idx_format` (`format`),
  KEY `idx_is_public` (`is_public`),
  CONSTRAINT `object_3d_model_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `information_object` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_model_i18n`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_model_i18n` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL,
  `culture` varchar(10) NOT NULL DEFAULT 'en',
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `alt_text` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_model_culture` (`model_id`,`culture`),
  CONSTRAINT `object_3d_model_i18n_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `object_3d_model` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_settings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `digital_object_id` int NOT NULL,
  `auto_rotate` tinyint(1) DEFAULT '1',
  `rotation_speed` decimal(3,2) DEFAULT '1.00',
  `camera_orbit` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0deg 75deg 105%',
  `field_of_view` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '30deg',
  `exposure` decimal(3,2) DEFAULT '1.00',
  `shadow_intensity` decimal(3,2) DEFAULT '1.00',
  `background_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#f5f5f5',
  `ar_enabled` tinyint(1) DEFAULT '1',
  `ar_scale` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'auto',
  `ar_placement` VARCHAR(23) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'floor' COMMENT 'floor, wall',
  `poster_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `digital_object_id` (`digital_object_id`),
  KEY `idx_digital_object` (`digital_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_3d_texture`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `object_3d_texture` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL,
  `texture_type` VARCHAR(75) COMMENT 'diffuse, normal, roughness, metallic, ao, emissive, environment' DEFAULT 'diffuse',
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  CONSTRAINT `object_3d_texture_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `object_3d_model` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `viewer_3d_settings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `viewer_3d_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` VARCHAR(42) COMMENT 'string, integer, boolean, json' DEFAULT 'string',
  `description` varchar(500) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 16:53:54

-- ---------------------------------------------------------------------------
-- Merged in from database/camera_bookmarks.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ahg3DModelPlugin - saved camera viewpoints ("bookmarks") for a 3D model.
-- Lets curators capture named camera orbits the viewer can jump back to.

CREATE TABLE IF NOT EXISTS `object_3d_camera_bookmark` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `model_id`       INT NOT NULL COMMENT 'object_3d_model.id (logical FK)',
    `name`           VARCHAR(120) NOT NULL,
    `camera_orbit`   VARCHAR(64) NOT NULL COMMENT 'model-viewer camera-orbit, e.g. "45deg 55deg 4m"',
    `field_of_view`  VARCHAR(32) DEFAULT NULL COMMENT 'model-viewer field-of-view, e.g. "30deg"',
    `display_order`  INT NOT NULL DEFAULT 0,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_model` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/migration_3d_capture_metadata.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ahg3DModelPlugin — 3D capture / provenance / PBR / LOD / licensing metadata.
-- Parity with Heratio object_3d_model (DB audit 2026-06-15). All columns
-- nullable + additive (safe on populated tables). Run once on existing installs.

ALTER TABLE `object_3d_model`
  ADD COLUMN `turntable_mp4_path`    VARCHAR(500) NULL,
  ADD COLUMN `turntable_generated_at` DATETIME NULL,
  ADD COLUMN `real_width`            DECIMAL(12,4) NULL,
  ADD COLUMN `real_height`           DECIMAL(12,4) NULL,
  ADD COLUMN `real_depth`            DECIMAL(12,4) NULL,
  ADD COLUMN `dimension_unit`        VARCHAR(16) NULL COMMENT 'dropdown model_3d_units',
  ADD COLUMN `scale_note`            VARCHAR(64) NULL,
  ADD COLUMN `coordinate_system`     VARCHAR(16) NULL COMMENT 'dropdown model_3d_coordinate_system',
  ADD COLUMN `bounding_box`          VARCHAR(96) NULL COMMENT 'auto: minX,minY,minZ maxX,maxY,maxZ (model units)',
  ADD COLUMN `format_version`        VARCHAR(32) NULL COMMENT 'auto: e.g. glTF 2.0',
  ADD COLUMN `compression`           VARCHAR(24) NULL COMMENT 'dropdown model_3d_compression',
  ADD COLUMN `is_lossless_master`    TINYINT(1) NULL,
  ADD COLUMN `pbr_maps`              VARCHAR(128) NULL COMMENT 'baseColor,normal,metalRough,occlusion,emissive',
  ADD COLUMN `texture_colorspace`    VARCHAR(24) NULL,
  ADD COLUMN `lod_levels`            INT NULL,
  ADD COLUMN `is_watertight`         TINYINT(1) NULL,
  ADD COLUMN `has_rig`               TINYINT(1) NULL,
  ADD COLUMN `capture_method`        VARCHAR(40) NULL COMMENT 'dropdown model_3d_capture_method',
  ADD COLUMN `capture_device`        VARCHAR(255) NULL,
  ADD COLUMN `capture_date`          DATE NULL,
  ADD COLUMN `capture_operator`      VARCHAR(255) NULL,
  ADD COLUMN `source_count`          INT NULL COMMENT 'e.g. number of source photos',
  ADD COLUMN `point_density`         VARCHAR(64) NULL,
  ADD COLUMN `accuracy_mm`           DECIMAL(8,3) NULL,
  ADD COLUMN `processing_software`   VARCHAR(255) NULL,
  ADD COLUMN `processing_notes`      TEXT NULL,
  ADD COLUMN `georeference`          VARCHAR(255) NULL,
  ADD COLUMN `model_author`          VARCHAR(255) NULL,
  ADD COLUMN `derivation_note`       TEXT NULL,
  ADD COLUMN `model_license`         VARCHAR(100) NULL COMMENT 'dropdown model_3d_licence',
  ADD COLUMN `model_license_holder`  VARCHAR(255) NULL,
  ADD COLUMN `attribution`           VARCHAR(500) NULL,
  ADD COLUMN `alt_text`              VARCHAR(500) NULL;

-- ---------------------------------------------------------------------------
-- Merged in from database/triposr_schema.sql on 2026-08-18.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php, so
-- a clean install silently lacked whatever it defines. Our own instances had it
-- because someone applied the file by hand. A plugin's schema is install.sql.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so re-running stays safe;
-- on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ============================================================
-- TripoSR - Image to 3D Model Generation
-- Database Schema for ahg3DModelPlugin
-- ============================================================

-- Job tracking table
CREATE TABLE IF NOT EXISTS `triposr_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `object_id` INT NULL COMMENT 'Link to information_object.id',
    `model_id` INT NULL COMMENT 'Link to object_3d_model.id after import',
    `input_image` VARCHAR(500) NOT NULL COMMENT 'Path to input image',
    `output_model` VARCHAR(500) NULL COMMENT 'Path to generated 3D model',
    `output_format` VARCHAR(20) COMMENT 'glb, obj' DEFAULT 'glb',
    `status` VARCHAR(50) COMMENT 'pending, processing, completed, failed' DEFAULT 'pending',
    `processing_mode` VARCHAR(25) COMMENT 'local, remote' DEFAULT 'local',
    `processing_time` DECIMAL(10,2) NULL COMMENT 'Time in seconds',
    `error_message` TEXT NULL,
    `options` JSON NULL COMMENT 'Generation options used',
    `created_by` INT NULL COMMENT 'User who initiated',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_object_id (`object_id`),
    INDEX idx_model_id (`model_id`),
    INDEX idx_status (`status`),
    INDEX idx_created_at (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings for TripoSR
INSERT IGNORE INTO `viewer_3d_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('triposr_enabled', '1', 'boolean', 'Enable TripoSR image-to-3D conversion'),
('triposr_api_url', 'http://127.0.0.1:5050', 'string', 'Local TripoSR API server URL'),
('triposr_mode', 'local', 'string', 'Processing mode: local or remote'),
('triposr_remote_url', '', 'string', 'Remote GPU server URL'),
('triposr_remote_api_key', '', 'string', 'API key for remote GPU server'),
('triposr_timeout', '300', 'integer', 'Request timeout in seconds'),
('triposr_remove_bg', '1', 'boolean', 'Remove background from input image'),
('triposr_foreground_ratio', '0.85', 'string', 'Foreground ratio after background removal'),
('triposr_mc_resolution', '256', 'integer', 'Marching cubes resolution (higher = more detail)'),
('triposr_bake_texture', '0', 'boolean', 'Bake texture into model (exports as OBJ)')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);
