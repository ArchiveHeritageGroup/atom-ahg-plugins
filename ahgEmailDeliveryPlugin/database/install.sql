-- ahgEmailDeliveryPlugin (#145) — email bounce capture + suppression list.
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `ahg_email_suppression` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `reason` VARCHAR(20) NOT NULL DEFAULT 'bounce' COMMENT 'bounce, complaint, manual, unsubscribe',
    `bounce_type` VARCHAR(20) DEFAULT NULL COMMENT 'hard, soft, blocked, null',
    `source` VARCHAR(40) NOT NULL DEFAULT 'webhook' COMMENT 'webhook, manual, import, cli',
    `detail` TEXT DEFAULT NULL COMMENT 'raw provider diagnostic / note',
    `bounce_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_event_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`),
    KEY `idx_reason` (`reason`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
