-- ahgRedactionPlugin: manual visual redaction of digital objects.
--
-- Regions are stored, not burnt in: the original file is never modified, and an
-- applied output is generated into the cache below. Removing a region and
-- re-applying restores what it covered, which a destructive edit could not.

CREATE TABLE IF NOT EXISTS `redaction_region` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT NOT NULL COMMENT 'information_object.id',
    `digital_object_id` INT DEFAULT NULL COMMENT 'digital_object.id if specific',
    `page_number` INT NOT NULL DEFAULT 1 COMMENT 'Page number (1-indexed)',
    `region_type` VARCHAR(40) NOT NULL DEFAULT 'rectangle' COMMENT 'rectangle, polygon, freehand',
    `coordinates` JSON NOT NULL COMMENT 'Normalised 0-1 coords: {x, y, width, height}',
    `normalized` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether coords are normalised 0-1',
    `source` VARCHAR(48) NOT NULL DEFAULT 'manual' COMMENT 'manual, imported',
    `label` VARCHAR(255) DEFAULT NULL COMMENT 'Optional label for the region',
    `color` VARCHAR(7) NOT NULL DEFAULT '#000000' COMMENT 'Redaction colour (hex)',
    `status` VARCHAR(48) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, applied, rejected',
    `created_by` INT DEFAULT NULL COMMENT 'user.id who created',
    `reviewed_by` INT DEFAULT NULL COMMENT 'user.id who reviewed',
    `reviewed_at` DATETIME DEFAULT NULL,
    `applied_at` DATETIME DEFAULT NULL COMMENT 'When the redaction was applied to output',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_object` (`object_id`),
    KEY `idx_digital_object` (`digital_object_id`),
    KEY `idx_page` (`object_id`, `page_number`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `redaction_cache` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT NOT NULL,
    `digital_object_id` INT DEFAULT NULL,
    `original_path` VARCHAR(500) NOT NULL,
    `redacted_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(22) NOT NULL DEFAULT 'pdf' COMMENT 'pdf, image',
    `regions_hash` VARCHAR(64) NOT NULL COMMENT 'SHA256 of the applied region ids',
    `region_count` INT NOT NULL DEFAULT 0,
    `file_size` BIGINT UNSIGNED DEFAULT NULL,
    `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_object_hash` (`object_id`, `regions_hash`),
    KEY `idx_object` (`object_id`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
