-- =====================================================
-- ahgDAMPlugin - Database Schema
-- Digital Asset Management with IPTC, watermarks, 
-- derivatives, and Creative Commons licensing
-- =====================================================

-- =====================================================
-- DAM IPTC Metadata
-- =====================================================
CREATE TABLE IF NOT EXISTS dam_iptc_metadata (
    id INT NOT NULL AUTO_INCREMENT,
    object_id INT NOT NULL,
    creator VARCHAR(255) DEFAULT NULL,
    creator_job_title VARCHAR(255) DEFAULT NULL,
    creator_address TEXT,
    creator_city VARCHAR(255) DEFAULT NULL,
    creator_state VARCHAR(255) DEFAULT NULL,
    creator_postal_code VARCHAR(50) DEFAULT NULL,
    creator_country VARCHAR(255) DEFAULT NULL,
    creator_phone VARCHAR(100) DEFAULT NULL,
    creator_email VARCHAR(255) DEFAULT NULL,
    creator_website VARCHAR(500) DEFAULT NULL,
    headline VARCHAR(500) DEFAULT NULL,
    caption TEXT,
    keywords TEXT,
    iptc_subject_code VARCHAR(255) DEFAULT NULL,
    intellectual_genre VARCHAR(255) DEFAULT NULL,
    iptc_scene VARCHAR(255) DEFAULT NULL,
    date_created DATE DEFAULT NULL,
    city VARCHAR(255) DEFAULT NULL,
    state_province VARCHAR(255) DEFAULT NULL,
    country VARCHAR(255) DEFAULT NULL,
    country_code VARCHAR(10) DEFAULT NULL,
    sublocation VARCHAR(500) DEFAULT NULL,
    title VARCHAR(500) DEFAULT NULL,
    job_id VARCHAR(255) DEFAULT NULL,
    instructions TEXT,
    credit_line VARCHAR(500) DEFAULT NULL,
    source VARCHAR(500) DEFAULT NULL,
    copyright_notice TEXT,
    rights_usage_terms TEXT,
    license_type ENUM('rights_managed','royalty_free','creative_commons','public_domain','editorial','other') DEFAULT NULL,
    license_url VARCHAR(500) DEFAULT NULL,
    license_expiry DATE DEFAULT NULL,
    model_release_status ENUM('none','not_applicable','unlimited','limited') DEFAULT 'none',
    model_release_id VARCHAR(255) DEFAULT NULL,
    property_release_status ENUM('none','not_applicable','unlimited','limited') DEFAULT 'none',
    property_release_id VARCHAR(255) DEFAULT NULL,
    artwork_title VARCHAR(500) DEFAULT NULL,
    artwork_creator VARCHAR(255) DEFAULT NULL,
    artwork_date VARCHAR(100) DEFAULT NULL,
    artwork_source VARCHAR(500) DEFAULT NULL,
    artwork_copyright TEXT,
    persons_shown TEXT,
    camera_make VARCHAR(100) DEFAULT NULL,
    camera_model VARCHAR(100) DEFAULT NULL,
    lens VARCHAR(255) DEFAULT NULL,
    focal_length VARCHAR(50) DEFAULT NULL,
    aperture VARCHAR(20) DEFAULT NULL,
    shutter_speed VARCHAR(50) DEFAULT NULL,
    iso_speed INT DEFAULT NULL,
    flash_used TINYINT(1) DEFAULT NULL,
    gps_latitude DECIMAL(10,8) DEFAULT NULL,
    gps_longitude DECIMAL(11,8) DEFAULT NULL,
    gps_altitude DECIMAL(10,2) DEFAULT NULL,
    image_width INT DEFAULT NULL,
    image_height INT DEFAULT NULL,
    resolution_x INT DEFAULT NULL,
    resolution_y INT DEFAULT NULL,
    resolution_unit VARCHAR(20) DEFAULT NULL,
    color_space VARCHAR(50) DEFAULT NULL,
    bit_depth INT DEFAULT NULL,
    orientation VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY object_id (object_id),
    KEY idx_creator (creator),
    KEY idx_keywords (keywords(255)),
    KEY idx_date_created (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Media Derivatives
-- =====================================================
CREATE TABLE IF NOT EXISTS media_derivatives (
    id INT NOT NULL AUTO_INCREMENT,
    digital_object_id INT NOT NULL,
    derivative_type ENUM('thumbnail','poster','preview','waveform') NOT NULL,
    derivative_index INT DEFAULT 0,
    path VARCHAR(500) NOT NULL,
    metadata JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_digital_object (digital_object_id),
    KEY idx_type (derivative_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Watermark Type
-- =====================================================
CREATE TABLE IF NOT EXISTS watermark_type (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    image_file VARCHAR(255) NOT NULL,
    position VARCHAR(50) DEFAULT 'repeat',
    opacity DECIMAL(3,2) DEFAULT 0.30,
    active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watermark types seed data
INSERT IGNORE INTO watermark_type (code, name, image_file, position, opacity, active, sort_order) VALUES
('DRAFT', 'Draft', 'draft.png', 'center', 0.40, 1, 1),
('COPYRIGHT', 'Copyright', 'copyright.png', 'bottom right', 0.30, 1, 2),
('CONFIDENTIAL', 'Confidential', 'confidential.png', 'repeat', 0.40, 1, 3),
('SECRET', 'Secret', 'secret_copyright.png', 'repeat', 0.40, 1, 4),
('TOP_SECRET', 'Top Secret', 'top_secret_copyright.png', 'repeat', 0.50, 1, 5),
('NONE', 'No Watermark', '', 'none', 0.00, 1, 6),
('SAMPLE', 'Sample', 'sample.png', 'center', 0.50, 1, 7),
('PREVIEW', 'Preview Only', 'preview.png', 'center', 0.40, 1, 8),
('RESTRICTED', 'Restricted', 'restricted.png', 'repeat', 0.35, 1, 9);

-- =====================================================
-- Watermark Settings
-- =====================================================
CREATE TABLE IF NOT EXISTS watermark_setting (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watermark settings seed data
INSERT IGNORE INTO watermark_setting (setting_key, setting_value, description) VALUES
('default_watermark_enabled', '1', 'Enable watermarking by default'),
('default_watermark_type', 'COPYRIGHT', 'Default watermark type for new uploads'),
('apply_watermark_on_view', '1', 'Apply watermark when viewing images (IIIF)'),
('apply_watermark_on_download', '1', 'Apply watermark when downloading'),
('security_watermark_override', '1', 'Security classification overrides default watermark'),
('watermark_min_size', '200', 'Minimum image dimension (px) to apply watermark');

-- =====================================================
-- Custom Watermark
-- =====================================================
CREATE TABLE IF NOT EXISTS custom_watermark (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    object_id INT UNSIGNED DEFAULT NULL COMMENT 'NULL = global watermark',
    name VARCHAR(100) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    position VARCHAR(50) DEFAULT 'center',
    opacity DECIMAL(3,2) DEFAULT 0.40,
    created_by INT UNSIGNED DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_object (object_id),
    KEY idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Object Watermark Setting
-- =====================================================
CREATE TABLE IF NOT EXISTS object_watermark_setting (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    object_id INT UNSIGNED NOT NULL,
    watermark_enabled TINYINT(1) DEFAULT 1,
    watermark_type_id INT UNSIGNED DEFAULT NULL,
    custom_watermark_id INT UNSIGNED DEFAULT NULL,
    position VARCHAR(50) DEFAULT 'center',
    opacity DECIMAL(3,2) DEFAULT 0.40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY object_id (object_id),
    KEY watermark_type_id (watermark_type_id),
    KEY custom_watermark_id (custom_watermark_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Creative Commons License
-- =====================================================
CREATE TABLE IF NOT EXISTS creative_commons_license (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uri VARCHAR(255) NOT NULL,
    icon_url VARCHAR(255) DEFAULT NULL,
    code VARCHAR(30) NOT NULL,
    version VARCHAR(10) DEFAULT '4.0',
    allows_adaptation TINYINT(1) DEFAULT 1,
    allows_commercial TINYINT(1) DEFAULT 1,
    requires_attribution TINYINT(1) DEFAULT 1,
    requires_sharealike TINYINT(1) DEFAULT 0,
    icon_filename VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cc_uri (uri),
    UNIQUE KEY uq_cc_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creative Commons licenses seed data
INSERT IGNORE INTO creative_commons_license (uri, icon_url, code, version, allows_adaptation, allows_commercial, requires_attribution, requires_sharealike, icon_filename, sort_order) VALUES
('https://creativecommons.org/publicdomain/zero/1.0/', 'https://licensebuttons.net/l/zero/1.0/88x31.png', 'CC0-1.0', '1.0', 1, 1, 0, 0, 'cc-zero.png', 1),
('https://creativecommons.org/licenses/by/4.0/', 'https://licensebuttons.net/l/by/4.0/88x31.png', 'CC-BY-4.0', '4.0', 1, 1, 1, 0, 'cc-by.png', 2),
('https://creativecommons.org/licenses/by-sa/4.0/', 'https://licensebuttons.net/l/by-sa/4.0/88x31.png', 'CC-BY-SA-4.0', '4.0', 1, 1, 1, 1, 'cc-by-sa.png', 3),
('https://creativecommons.org/licenses/by-nc/4.0/', 'https://licensebuttons.net/l/by-nc/4.0/88x31.png', 'CC-BY-NC-4.0', '4.0', 1, 0, 1, 0, 'cc-by-nc.png', 4),
('https://creativecommons.org/licenses/by-nc-sa/4.0/', 'https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png', 'CC-BY-NC-SA-4.0', '4.0', 1, 0, 1, 1, 'cc-by-nc-sa.png', 5),
('https://creativecommons.org/licenses/by-nd/4.0/', 'https://licensebuttons.net/l/by-nd/4.0/88x31.png', 'CC-BY-ND-4.0', '4.0', 0, 1, 1, 0, 'cc-by-nd.png', 6),
('https://creativecommons.org/licenses/by-nc-nd/4.0/', 'https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png', 'CC-BY-NC-ND-4.0', '4.0', 0, 0, 1, 0, 'cc-by-nc-nd.png', 7),
('https://creativecommons.org/publicdomain/mark/1.0/', NULL, 'PDM-1.0', '1.0', 1, 1, 0, 0, 'publicdomain.png', 8);

-- =====================================================
-- Creative Commons License i18n
-- =====================================================
CREATE TABLE IF NOT EXISTS creative_commons_license_i18n (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    creative_commons_license_id BIGINT UNSIGNED NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cc_i18n (creative_commons_license_id, culture),
    KEY idx_cc_i18n_parent (creative_commons_license_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creative Commons i18n seed data (uses subqueries for FK)
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'CC0 1.0 Universal (Public Domain)', 'No rights reserved.' FROM creative_commons_license WHERE code = 'CC0-1.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Attribution 4.0 International', 'Credit required.' FROM creative_commons_license WHERE code = 'CC-BY-4.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Attribution-ShareAlike 4.0 International', 'Credit, share alike.' FROM creative_commons_license WHERE code = 'CC-BY-SA-4.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Attribution-NonCommercial 4.0 International', 'Credit, non-commercial.' FROM creative_commons_license WHERE code = 'CC-BY-NC-4.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Attribution-NonCommercial-ShareAlike 4.0', 'Credit, NC, share alike.' FROM creative_commons_license WHERE code = 'CC-BY-NC-SA-4.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Attribution-NoDerivatives 4.0 International', 'Credit, no derivatives.' FROM creative_commons_license WHERE code = 'CC-BY-ND-4.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Attribution-NonCommercial-NoDerivatives 4.0', 'Most restrictive.' FROM creative_commons_license WHERE code = 'CC-BY-NC-ND-4.0';
INSERT IGNORE INTO creative_commons_license_i18n (creative_commons_license_id, culture, name, description)
SELECT id, 'en', 'Public Domain Mark 1.0', 'Free of restrictions.' FROM creative_commons_license WHERE code = 'PDM-1.0';

-- =====================================================
-- Rights Derivative Rule
-- =====================================================
CREATE TABLE IF NOT EXISTS rights_derivative_rule (
    id INT NOT NULL AUTO_INCREMENT,
    object_id INT DEFAULT NULL COMMENT 'NULL = applies to collection or global',
    collection_id INT DEFAULT NULL COMMENT 'NULL = applies to object or global',
    is_global TINYINT(1) DEFAULT 0,
    rule_type ENUM('watermark','redaction','resize','format_conversion','metadata_strip') NOT NULL,
    priority INT DEFAULT 0,
    applies_to_roles JSON DEFAULT NULL COMMENT 'Array of role IDs, NULL = all',
    applies_to_clearance_levels JSON DEFAULT NULL COMMENT 'Array of clearance level codes',
    applies_to_purposes JSON DEFAULT NULL COMMENT 'Array of purpose codes',
    watermark_text VARCHAR(255) DEFAULT NULL,
    watermark_image_path VARCHAR(500) DEFAULT NULL,
    watermark_position ENUM('center','top_left','top_right','bottom_left','bottom_right','tile') DEFAULT 'bottom_right',
    watermark_opacity INT DEFAULT 50 COMMENT '0-100',
    redaction_areas JSON DEFAULT NULL COMMENT 'Array of {x, y, width, height, page}',
    redaction_color VARCHAR(7) DEFAULT '#000000',
    max_width INT DEFAULT NULL,
    max_height INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_object (object_id),
    KEY idx_collection (collection_id),
    KEY idx_rule_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Rights Derivative Log
-- =====================================================
CREATE TABLE IF NOT EXISTS rights_derivative_log (
    id INT NOT NULL AUTO_INCREMENT,
    digital_object_id INT NOT NULL,
    rule_id INT DEFAULT NULL,
    derivative_type VARCHAR(50) DEFAULT NULL,
    original_path VARCHAR(500) DEFAULT NULL,
    derivative_path VARCHAR(500) DEFAULT NULL,
    requested_by INT DEFAULT NULL,
    request_purpose VARCHAR(100) DEFAULT NULL,
    request_ip VARCHAR(45) DEFAULT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_digital_object (digital_object_id),
    KEY idx_rule (rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Security Watermark Log
-- =====================================================
CREATE TABLE IF NOT EXISTS security_watermark_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    object_id INT UNSIGNED NOT NULL,
    digital_object_id INT UNSIGNED DEFAULT NULL,
    watermark_type ENUM('visible','invisible','both') NOT NULL DEFAULT 'visible',
    watermark_text VARCHAR(500) NOT NULL,
    watermark_code VARCHAR(100) NOT NULL,
    file_hash VARCHAR(64) DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id, created_at),
    KEY idx_object (object_id),
    KEY idx_code (watermark_code),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DAM Display Standard Term (taxonomy_id = 70)
-- =====================================================
SET @dam_exists = (SELECT COUNT(*) FROM term WHERE code = 'dam' AND taxonomy_id = 70);

INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @dam_exists = 0;

SET @dam_id = LAST_INSERT_ID();

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT @dam_id, 70, 'dam', 'en' FROM DUAL WHERE @dam_exists = 0 AND @dam_id > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT @dam_id, 'en', 'Photo/DAM (IPTC/XMP)' FROM DUAL WHERE @dam_exists = 0 AND @dam_id > 0;

-- =====================================================
-- DAM Level of Description Terms (taxonomy_id = 34)
-- =====================================================

-- Photograph (shared with Gallery)
SET @photo_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Photograph' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @photo_exists IS NULL;
SET @photo_id = IF(@photo_exists IS NULL, LAST_INSERT_ID(), @photo_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @photo_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @photo_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @photo_id, 'en', 'Photograph' FROM DUAL WHERE @photo_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@photo_id, 'photograph');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@photo_id, 'dam', 10);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@photo_id, 'gallery', 20);

-- Audio
SET @audio_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Audio' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @audio_exists IS NULL;
SET @audio_id = IF(@audio_exists IS NULL, LAST_INSERT_ID(), @audio_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @audio_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @audio_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @audio_id, 'en', 'Audio' FROM DUAL WHERE @audio_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@audio_id, 'audio');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@audio_id, 'dam', 20);

-- Video
SET @video_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Video' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @video_exists IS NULL;
SET @video_id = IF(@video_exists IS NULL, LAST_INSERT_ID(), @video_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @video_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @video_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @video_id, 'en', 'Video' FROM DUAL WHERE @video_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@video_id, 'video');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@video_id, 'dam', 30);

-- Image
SET @image_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Image' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @image_exists IS NULL;
SET @image_id = IF(@image_exists IS NULL, LAST_INSERT_ID(), @image_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @image_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @image_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @image_id, 'en', 'Image' FROM DUAL WHERE @image_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@image_id, 'image');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@image_id, 'dam', 40);

-- Dataset
SET @data_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Dataset' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @data_exists IS NULL;
SET @data_id = IF(@data_exists IS NULL, LAST_INSERT_ID(), @data_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @data_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @data_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @data_id, 'en', 'Dataset' FROM DUAL WHERE @data_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@data_id, 'dataset');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@data_id, 'dam', 70);
