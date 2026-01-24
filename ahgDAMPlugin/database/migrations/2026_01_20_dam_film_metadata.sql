-- DAM Plugin Enhancement: Film/Video Metadata Fields
-- Date: 2026-01-20
-- Purpose: Add production country, duration, versions, holdings, and external links

-- ============================================================================
-- 1. ALTER dam_iptc_metadata - Add duration and production country
-- ============================================================================

-- Check and add duration_minutes if not exists
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dam_iptc_metadata' AND COLUMN_NAME = 'duration_minutes');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE dam_iptc_metadata ADD COLUMN duration_minutes INT UNSIGNED NULL COMMENT "Running time in minutes (rounded)" AFTER headline',
    'SELECT "Column duration_minutes already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add production_country if not exists
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dam_iptc_metadata' AND COLUMN_NAME = 'production_country');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE dam_iptc_metadata ADD COLUMN production_country VARCHAR(100) NULL COMMENT "Country where film/video was produced" AFTER country_code',
    'SELECT "Column production_country already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add production_country_code if not exists
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dam_iptc_metadata' AND COLUMN_NAME = 'production_country_code');
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE dam_iptc_metadata ADD COLUMN production_country_code CHAR(3) NULL COMMENT "ISO 3166-1 alpha-3 production country code" AFTER production_country',
    'SELECT "Column production_country_code already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 2. CREATE dam_version_links - Alternative language/format versions
-- ============================================================================

CREATE TABLE IF NOT EXISTS dam_version_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'Main asset information_object.id',
    related_object_id INT NULL COMMENT 'Related AtoM object if catalogued separately',
    version_type ENUM('language', 'format', 'restoration', 'directors_cut', 'censored', 'other') NOT NULL DEFAULT 'language',
    title VARCHAR(255) NOT NULL COMMENT 'Title of this version, e.g., Kuddes van die veld',
    language_code CHAR(3) NULL COMMENT 'ISO 639-2 code (afr, eng, nld)',
    language_name VARCHAR(50) NULL COMMENT 'Human readable: Afrikaans, English, Dutch',
    year VARCHAR(10) NULL COMMENT 'Release year of this version',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dam_version_object (object_id),
    INDEX idx_dam_version_related (related_object_id),
    INDEX idx_dam_version_type (version_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. CREATE dam_format_holdings - Physical formats at institutions
-- ============================================================================

CREATE TABLE IF NOT EXISTS dam_format_holdings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'information_object.id',
    format_type ENUM(
        '35mm', '16mm', '8mm', 'Super8',
        'VHS', 'Betacam', 'U-matic', 'DV',
        'DVD', 'Blu-ray', 'LaserDisc',
        'Digital_File', 'DCP', 'ProRes',
        'Nitrate', 'Safety', 'Polyester',
        'Audio_Reel', 'Audio_Cassette', 'Vinyl', 'CD',
        'Other'
    ) NOT NULL,
    format_details VARCHAR(255) NULL COMMENT 'Additional info: color, sound type, aspect ratio',
    holding_institution VARCHAR(255) NOT NULL COMMENT 'e.g., NFVSA, Western Cape Provincial Library',
    holding_location VARCHAR(255) NULL COMMENT 'Specific location/department within institution',
    accession_number VARCHAR(100) NULL COMMENT 'Institution catalog/reference number',
    condition_status ENUM('excellent', 'good', 'fair', 'poor', 'deteriorating', 'unknown') DEFAULT 'unknown',
    access_status ENUM(
        'available',
        'restricted',
        'preservation_only',
        'digitized_available',
        'on_request',
        'staff_only',
        'unknown'
    ) DEFAULT 'unknown',
    access_url VARCHAR(500) NULL COMMENT 'URL for digitized/streaming versions',
    access_notes TEXT NULL COMMENT 'How to request access, viewing conditions',
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'Primary viewing copy flag',
    verified_date DATE NULL COMMENT 'Date holding was last verified',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dam_holdings_object (object_id),
    INDEX idx_dam_holdings_institution (holding_institution),
    INDEX idx_dam_holdings_format (format_type),
    INDEX idx_dam_holdings_access (access_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. CREATE dam_external_links - ESAT, IMDb, and other references
-- ============================================================================

CREATE TABLE IF NOT EXISTS dam_external_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'information_object.id',
    link_type ENUM(
        'ESAT',
        'IMDb',
        'SAFILM',
        'NFVSA',
        'Wikipedia',
        'Wikidata',
        'VIAF',
        'YouTube',
        'Vimeo',
        'Archive_org',
        'BFI',
        'AFI',
        'Letterboxd',
        'MUBI',
        'Filmography',
        'Review',
        'Academic',
        'Press',
        'Other'
    ) NOT NULL,
    url VARCHAR(500) NOT NULL,
    title VARCHAR(255) NULL COMMENT 'Link display text',
    description TEXT NULL COMMENT 'What this link provides',
    person_name VARCHAR(255) NULL COMMENT 'For person-specific links: Donald Swanson',
    person_role VARCHAR(100) NULL COMMENT 'Role in production: Director, Actor, etc.',
    verified_date DATE NULL COMMENT 'Last verified link is working',
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'Primary reference link flag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dam_links_object (object_id),
    INDEX idx_dam_links_type (link_type),
    INDEX idx_dam_links_person (person_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
