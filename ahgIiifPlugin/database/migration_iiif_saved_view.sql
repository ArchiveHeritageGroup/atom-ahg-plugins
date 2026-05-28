-- Migration: Add iiif_saved_view table for Content State sharing
-- Issue: #70 Content State API
-- Description: Persists short tokens for IIIF Content State sharing.
-- Short tokens (32-char alphanumeric) are stored in DB with TTL.
-- Long-form tokens are direct base64url(JSON) — no DB needed.

CREATE TABLE IF NOT EXISTS `iiif_saved_view` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `token` CHAR(32) NOT NULL COMMENT 'Short token lookup key',
    `state_json` TEXT NOT NULL COMMENT 'Full Content State JSON',
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'User who created this saved view',
    `object_id` INT UNSIGNED DEFAULT NULL COMMENT 'Associated information_object.id',
    `expires_at` DATETIME NOT NULL COMMENT 'Auto-delete after TTL',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `click_count` INT UNSIGNED DEFAULT 0 COMMENT 'Analytics: how many times this link was used',
    `created_ip` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 of creator',
    UNIQUE KEY `uk_token` (`token`),
    INDEX `idx_saved_view_object` (`object_id`),
    INDEX `idx_saved_view_user` (`user_id`),
    INDEX `idx_saved_view_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
