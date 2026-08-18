-- ============================================================
-- ahgAuditTrailPlugin - Database Schema
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
-- Table structure for table `ahg_audit_access`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ahg_audit_access` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `entity_slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `security_classification` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `security_clearance_level` int unsigned DEFAULT NULL,
  `clearance_verified` tinyint(1) NOT NULL DEFAULT '0',
  `file_path` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `denial_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ahg_access_uuid` (`uuid`),
  KEY `idx_ahg_access_user` (`user_id`),
  KEY `idx_ahg_access_type` (`access_type`),
  KEY `idx_ahg_access_entity` (`entity_type`,`entity_id`),
  KEY `idx_ahg_access_security` (`security_classification`),
  KEY `idx_ahg_access_created` (`created_at`),
  CONSTRAINT `fk_ahg_audit_access_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ahg_audit_authentication`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ahg_audit_authentication` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_attempts` int unsigned NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ahg_auth_uuid` (`uuid`),
  KEY `idx_ahg_auth_user` (`user_id`),
  KEY `idx_ahg_auth_event` (`event_type`),
  KEY `idx_ahg_auth_ip` (`ip_address`),
  KEY `idx_ahg_auth_created` (`created_at`),
  CONSTRAINT `fk_ahg_audit_auth_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ahg_audit_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ahg_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `entity_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_uri` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `changed_fields` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `security_classification` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `culture_id` int DEFAULT NULL,
  `prev_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kid` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seq` bigint DEFAULT NULL,
  `signature` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tenant_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ahg_audit_uuid` (`uuid`),
  KEY `idx_ahg_audit_user` (`user_id`),
  KEY `idx_ahg_audit_action` (`action`),
  KEY `idx_ahg_audit_entity_type` (`entity_type`),
  KEY `idx_ahg_audit_entity_id` (`entity_id`),
  KEY `idx_ahg_audit_created` (`created_at`),
  KEY `idx_ahg_audit_status` (`status`),
  KEY `idx_ahg_audit_ip` (`ip_address`),
  KEY `idx_ahg_audit_security` (`security_classification`),
  KEY `idx_ahg_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_entry_hash` (`entry_hash`),
  CONSTRAINT `fk_ahg_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ahg_audit_retention_policy`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ahg_audit_retention_policy` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retention_days` int unsigned NOT NULL DEFAULT '2555',
  `archive_before_delete` tinyint(1) NOT NULL DEFAULT '1',
  `archive_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_cleanup_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ahg_retention_type` (`log_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ahg_audit_settings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ahg_audit_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ahg_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

-- Seed: Default audit settings (enabled by default for security compliance)
INSERT IGNORE INTO `ahg_audit_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('audit_enabled', '1', 'boolean', 'Enable audit trail logging'),
('audit_authentication', '1', 'boolean', 'Log authentication events (login, logout, failed login)'),
('audit_views', '0', 'boolean', 'Log view-only actions (high volume — enable only when needed)'),
('retention_days', '365', 'integer', 'Number of days to retain audit log entries');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 17:01:31

-- ---------------------------------------------------------------------------
-- Merged in from database/add_audit_chain.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- #126: cryptographic hash chaining for ahg_audit_log (seal-forward).
-- Historical rows (entry_hash IS NULL) are left as-is; every NEW entry from the
-- seal point on is SHA-256 linked to the previous, making post-seal history
-- tamper-evident.
ALTER TABLE `ahg_audit_log`
  ADD COLUMN `prev_hash` CHAR(64) NULL COMMENT 'SHA-256 entry_hash of the previous chained entry',
  ADD COLUMN `entry_hash` CHAR(64) NULL COMMENT 'SHA-256(prev_hash || canonical(content))',
  ADD KEY `idx_audit_entry_hash` (`entry_hash`);

-- Single-row chain head + seal anchor. Locked FOR UPDATE on each append so
-- concurrent writers cannot fork the chain.
CREATE TABLE IF NOT EXISTS `ahg_audit_chain_state` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `genesis_hash` CHAR(64) NOT NULL COMMENT 'anchor binding the chain to the seal moment',
  `last_hash` CHAR(64) NOT NULL COMMENT 'entry_hash of the most recent chained entry',
  `last_audit_id` BIGINT UNSIGNED NULL,
  `sealed_from_id` BIGINT UNSIGNED NULL COMMENT 'MAX(ahg_audit_log.id) when sealed',
  `sealed_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Merged in from database/add_audit_seal.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ahgAuditTrailPlugin — cryptographic seal columns (#5 / DB-audit build-order #5)
--
-- The hash chain (#126, add_audit_chain.sql) already makes ahg_audit_log
-- tamper-EVIDENT. These columns add the tamper-PROOF seal on top:
--   kid       — id of the Ed25519 key that signed the entry
--   seq       — monotonic per-chain ordinal (gap-detectable)
--   signature — base64 detached Ed25519 signature over entry_hash
--   tenant_id — multi-tenant scoping (nullable; PSIS multi-tenancy disabled)
--
-- All nullable/additive — pre-seal rows keep their entry_hash and still verify.
-- Run-once. ALTER on a large table: MySQL 8 ADD COLUMN is INSTANT.

ALTER TABLE `ahg_audit_log`
    ADD COLUMN `kid` VARCHAR(32) NULL COMMENT 'Ed25519 signing key id' AFTER `entry_hash`,
    ADD COLUMN `seq` BIGINT NULL COMMENT 'monotonic per-chain ordinal' AFTER `kid`,
    ADD COLUMN `signature` VARCHAR(128) NULL COMMENT 'base64 detached Ed25519 signature over entry_hash' AFTER `seq`,
    ADD COLUMN `tenant_id` INT NULL COMMENT 'multi-tenant scoping (nullable)' AFTER `signature`;

-- Track the monotonic seq counter on the single chain-state row.
ALTER TABLE `ahg_audit_chain_state`
    ADD COLUMN `last_seq` BIGINT NOT NULL DEFAULT 0 COMMENT 'last issued seq' AFTER `last_audit_id`;
