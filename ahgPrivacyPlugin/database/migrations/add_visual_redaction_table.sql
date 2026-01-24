-- =====================================================
-- Visual Redaction Table Migration
-- Stores coordinate-based redaction regions for PDFs and images
-- =====================================================

CREATE TABLE IF NOT EXISTS `privacy_visual_redaction` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT NOT NULL COMMENT 'information_object.id',
    `digital_object_id` INT DEFAULT NULL COMMENT 'digital_object.id if specific',
    `page_number` INT NOT NULL DEFAULT 1 COMMENT 'Page number (1-indexed)',
    `region_type` ENUM('rectangle', 'polygon', 'freehand') NOT NULL DEFAULT 'rectangle',
    `coordinates` JSON NOT NULL COMMENT 'Normalized 0-1 coords: {x, y, width, height}',
    `normalized` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether coords are normalized 0-1',
    `source` ENUM('manual', 'auto_ner', 'auto_pii', 'imported') NOT NULL DEFAULT 'manual',
    `linked_entity_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Links to ahg_ner_entity.id if from NER',
    `label` VARCHAR(255) DEFAULT NULL COMMENT 'Optional label for the region',
    `color` VARCHAR(7) NOT NULL DEFAULT '#000000' COMMENT 'Redaction color (hex)',
    `status` ENUM('pending', 'approved', 'applied', 'rejected') NOT NULL DEFAULT 'pending',
    `created_by` INT DEFAULT NULL COMMENT 'user.id who created',
    `reviewed_by` INT DEFAULT NULL COMMENT 'user.id who reviewed',
    `reviewed_at` DATETIME DEFAULT NULL,
    `applied_at` DATETIME DEFAULT NULL COMMENT 'When redaction was applied to output',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_digital_object` (`digital_object_id`),
    KEY `idx_page` (`object_id`, `page_number`),
    KEY `idx_status` (`status`),
    KEY `idx_source` (`source`),
    KEY `idx_linked_entity` (`linked_entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache table for applied redaction outputs
CREATE TABLE IF NOT EXISTS `privacy_redaction_cache` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT NOT NULL,
    `digital_object_id` INT DEFAULT NULL,
    `original_path` VARCHAR(500) NOT NULL,
    `redacted_path` VARCHAR(500) NOT NULL,
    `file_type` ENUM('pdf', 'image') NOT NULL DEFAULT 'pdf',
    `regions_hash` VARCHAR(64) NOT NULL COMMENT 'SHA256 of applied region IDs',
    `region_count` INT NOT NULL DEFAULT 0,
    `file_size` BIGINT UNSIGNED DEFAULT NULL,
    `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_object_hash` (`object_id`, `regions_hash`),
    KEY `idx_object` (`object_id`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
