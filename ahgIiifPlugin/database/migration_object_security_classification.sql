-- Migration: Add object_security_classification table
-- Issue: #70 Authorization Flow 2.0 clearance propagation
-- Description: Stores clearance/classification level per information_object.
-- Maps to EAD <accessrestrict> / <userestrict> / NDA rules.
-- Clearance level drives which auth service protects the object.

CREATE TABLE IF NOT EXISTS `object_security_classification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `object_id` INT UNSIGNED NOT NULL COMMENT 'information_object.id',
    `classification_level` VARCHAR(50) NOT NULL DEFAULT 'restricted'
        COMMENT 'open | restricted | confidential | secret | top-secret',
    `classification_authority` VARCHAR(255) DEFAULT NULL COMMENT 'Policy or legal basis',
    `exemption_category` VARCHAR(100) DEFAULT NULL COMMENT 'FOIA / PA / other exemption',
    `available_on` DATETIME DEFAULT NULL COMMENT 'Declassification date (NULL = never)',
    `issued_at` DATETIME DEFAULT NULL COMMENT 'Date classification was applied',
    `issued_by` INT UNSIGNED DEFAULT NULL COMMENT 'actor.id who classified',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_object_classification` (`object_id`),
    INDEX `idx_classification_level` (`classification_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
