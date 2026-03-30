-- =====================================================
-- ahgRegistryPlugin - Migration: Feedback v1
-- Glenn & Richard feedback (2026-03-30)
-- New instance fields + password reset tokens
-- =====================================================

-- ---------------------------------------------------
-- 1. Instance: multi-repository, deployment architecture
-- ---------------------------------------------------
ALTER TABLE `registry_instance`
  ADD COLUMN `multi_repository` tinyint(1) DEFAULT 0 AFTER `descriptive_standard`,
  ADD COLUMN `repository_count` int DEFAULT NULL AFTER `multi_repository`,
  ADD COLUMN `deployment_architecture` VARCHAR(50) DEFAULT NULL COMMENT 'single_site, split_edit_public, mirror, other' AFTER `repository_count`;

-- ---------------------------------------------------
-- 2. Vendor: add lat/lng for map display
-- ---------------------------------------------------
ALTER TABLE `registry_vendor`
  ADD COLUMN `latitude` DECIMAL(10,7) DEFAULT NULL AFTER `country`,
  ADD COLUMN `longitude` DECIMAL(10,7) DEFAULT NULL AFTER `latitude`;

-- ---------------------------------------------------
-- 3. Password reset tokens
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `registry_password_reset` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token`),
  KEY `idx_reset_email` (`email`),
  KEY `idx_reset_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
