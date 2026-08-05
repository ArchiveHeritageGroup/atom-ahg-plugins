-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgMetadataExtractionPlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------

-- Table: metadata_extraction_log
CREATE TABLE IF NOT EXISTS `metadata_extraction_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `digital_object_id` int DEFAULT NULL,
  `file_path` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `operation` VARCHAR(62) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'extract, face_detect, face_match, index_face, bulk',
  `status` VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'success, partial, failed, skipped',
  `metadata_extracted` tinyint(1) DEFAULT '0',
  `faces_detected` int DEFAULT '0',
  `faces_matched` int DEFAULT '0',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `processing_time_ms` int DEFAULT NULL,
  `triggered_by` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'upload, job, manual, api',
  `job_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_digital_object` (`digital_object_id`),
  KEY `idx_operation` (`operation`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- ahgMetadataExtractionPlugin — full embedded-metadata capture (#113)
-- ============================================================================
-- One row per master digital object holding the COMPLETE ExifTool tag set
-- (exiftool -json -a -G1 -struct -u) as grouped JSON, alongside the existing
-- curated fields. Plugin-owned table — does NOT touch the core `property`
-- schema. LONGTEXT gives ample headroom for large MakerNotes/XMP tag sets.

CREATE TABLE IF NOT EXISTS ahg_embedded_metadata (
    digital_object_id BIGINT UNSIGNED NOT NULL PRIMARY KEY COMMENT 'master digital_object.id',
    information_object_id BIGINT UNSIGNED NULL COMMENT 'owning information_object.id (for lookups)',
    raw_metadata LONGTEXT NOT NULL COMMENT 'Full exiftool -G1 grouped JSON ({"Group:Tag": value, ...})',
    has_gps TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if any GPS/location tag present',
    tag_count INT NOT NULL DEFAULT 0 COMMENT 'number of captured tags',
    extracted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_information_object (information_object_id),
    INDEX idx_has_gps (has_gps)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
