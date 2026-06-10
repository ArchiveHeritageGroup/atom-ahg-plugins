-- ahgAnnotationsPlugin (#146) — W3C Web Annotation store.
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `ahg_web_annotation` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `anno_uuid` VARCHAR(36) NOT NULL COMMENT 'opaque id used in the @id URL',
    `target_uri` VARCHAR(1024) DEFAULT NULL COMMENT 'canonical target IRI (source) for query',
    `target_hash` CHAR(40) DEFAULT NULL COMMENT 'sha1(target_uri) for indexed lookup',
    `motivation` VARCHAR(64) DEFAULT NULL,
    `creator` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `body_json` LONGTEXT NOT NULL COMMENT 'full W3C Web Annotation JSON-LD',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_uuid` (`anno_uuid`),
    KEY `idx_target_hash` (`target_hash`),
    KEY `idx_motivation` (`motivation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
