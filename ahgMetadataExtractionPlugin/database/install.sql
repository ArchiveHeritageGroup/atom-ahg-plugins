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
