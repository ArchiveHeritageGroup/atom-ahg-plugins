-- ahgDAMPlugin Installation Schema
-- Version: 1.0.0
-- Digital Asset Management with watermarks, derivatives, licensing

-- IPTC Metadata for digital objects
CREATE TABLE IF NOT EXISTS dam_iptc_metadata (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    
    -- Creator Contact Info
    creator VARCHAR(255) NULL,
    creator_job_title VARCHAR(255) NULL,
    creator_address TEXT NULL,
    creator_city VARCHAR(255) NULL,
    creator_state VARCHAR(255) NULL,
    creator_postal_code VARCHAR(50) NULL,
    creator_country VARCHAR(255) NULL,
    creator_phone VARCHAR(100) NULL,
    creator_email VARCHAR(255) NULL,
    creator_website VARCHAR(500) NULL,
    
    -- Content Description
    headline VARCHAR(500) NULL,
    caption TEXT NULL,
    keywords TEXT NULL,
    iptc_subject_code VARCHAR(255) NULL,
    intellectual_genre VARCHAR(255) NULL,
    iptc_scene VARCHAR(255) NULL,
    
    -- Date & Location
    date_created DATE NULL,
    city VARCHAR(255) NULL,
    state_province VARCHAR(255) NULL,
    country VARCHAR(255) NULL,
    country_code VARCHAR(10) NULL,
    sublocation VARCHAR(500) NULL,
    
    -- Administrative
    title VARCHAR(500) NULL,
    job_id VARCHAR(255) NULL,
    instructions TEXT NULL,
    credit_line VARCHAR(500) NULL,
    source VARCHAR(500) NULL,
    
    -- Rights
    copyright_notice TEXT NULL,
    rights_usage_terms TEXT NULL,
    license_type ENUM('rights_managed', 'royalty_free', 'creative_commons', 'public_domain', 'editorial', 'other') NULL,
    license_url VARCHAR(500) NULL,
    license_expiry DATE NULL,
    
    -- Releases
    model_release_status ENUM('none', 'not_applicable', 'unlimited', 'limited') DEFAULT 'none',
    model_release_id VARCHAR(255) NULL,
    property_release_status ENUM('none', 'not_applicable', 'unlimited', 'limited') DEFAULT 'none',
    property_release_id VARCHAR(255) NULL,
    
    -- Artwork
    artwork_title VARCHAR(500) NULL,
    artwork_creator VARCHAR(255) NULL,
    artwork_date VARCHAR(100) NULL,
    artwork_source VARCHAR(500) NULL,
    artwork_copyright TEXT NULL,
    persons_shown TEXT NULL,
    
    -- Technical (EXIF)
    camera_make VARCHAR(100) NULL,
    camera_model VARCHAR(100) NULL,
    lens VARCHAR(255) NULL,
    focal_length VARCHAR(50) NULL,
    aperture VARCHAR(20) NULL,
    shutter_speed VARCHAR(50) NULL,
    iso_speed INT NULL,
    flash_used TINYINT(1) NULL,
    gps_latitude DECIMAL(10,8) NULL,
    gps_longitude DECIMAL(11,8) NULL,
    gps_altitude DECIMAL(10,2) NULL,
    image_width INT NULL,
    image_height INT NULL,
    resolution_x INT NULL,
    resolution_y INT NULL,
    resolution_unit VARCHAR(20) NULL,
    color_space VARCHAR(50) NULL,
    bit_depth INT NULL,
    orientation VARCHAR(50) NULL,
    
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    
    UNIQUE KEY uk_object (object_id),
    INDEX idx_object_id (object_id),
    INDEX idx_creator (creator),
    INDEX idx_keywords (keywords(255)),
    INDEX idx_date_created (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Media derivatives (thumbnails, posters, previews)
CREATE TABLE IF NOT EXISTS media_derivatives (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    digital_object_id INT NOT NULL,
    derivative_type ENUM('thumbnail', 'poster', 'preview', 'waveform') NOT NULL,
    derivative_index INT DEFAULT 0,
    path VARCHAR(500) NOT NULL,
    metadata JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_digital_object (digital_object_id),
    INDEX idx_type (derivative_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watermark settings (global)
CREATE TABLE IF NOT EXISTS watermark_setting (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watermark types (predefined watermarks)
CREATE TABLE IF NOT EXISTS watermark_type (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    image_file VARCHAR(255) NOT NULL,
    position VARCHAR(50) DEFAULT 'repeat',
    opacity DECIMAL(3,2) DEFAULT 0.30,
    active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom watermarks (user uploaded)
CREATE TABLE IF NOT EXISTS custom_watermark (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    object_id INT UNSIGNED NULL COMMENT 'NULL = global watermark',
    name VARCHAR(100) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    position VARCHAR(50) DEFAULT 'center',
    opacity DECIMAL(3,2) DEFAULT 0.40,
    created_by INT UNSIGNED NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-object watermark settings
CREATE TABLE IF NOT EXISTS object_watermark_setting (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    object_id INT UNSIGNED NOT NULL UNIQUE,
    watermark_enabled TINYINT(1) DEFAULT 1,
    watermark_type_id INT UNSIGNED NULL,
    custom_watermark_id INT UNSIGNED NULL,
    position VARCHAR(50) DEFAULT 'center',
    opacity DECIMAL(3,2) DEFAULT 0.40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object_id (object_id),
    CONSTRAINT fk_ows_watermark_type FOREIGN KEY (watermark_type_id) 
        REFERENCES watermark_type(id) ON DELETE SET NULL,
    CONSTRAINT fk_ows_custom_watermark FOREIGN KEY (custom_watermark_id) 
        REFERENCES custom_watermark(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creative Commons licenses
CREATE TABLE IF NOT EXISTS creative_commons_license (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uri VARCHAR(255) NOT NULL UNIQUE,
    icon_url VARCHAR(255) NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    version VARCHAR(10) DEFAULT '4.0',
    allows_adaptation TINYINT(1) DEFAULT 1,
    allows_commercial TINYINT(1) DEFAULT 1,
    requires_attribution TINYINT(1) DEFAULT 1,
    requires_sharealike TINYINT(1) DEFAULT 0,
    icon_filename VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creative Commons license i18n
CREATE TABLE IF NOT EXISTS creative_commons_license_i18n (
    id BIGINT UNSIGNED NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_ccl_i18n FOREIGN KEY (id) 
        REFERENCES creative_commons_license(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Derivative rules (watermark, redaction, resize based on permissions)
CREATE TABLE IF NOT EXISTS rights_derivative_rule (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    object_id INT NULL COMMENT 'NULL = applies to collection or global',
    collection_id INT NULL COMMENT 'NULL = applies to object or global',
    is_global TINYINT(1) DEFAULT 0,
    rule_type ENUM('watermark', 'redaction', 'resize', 'format_conversion', 'metadata_strip') NOT NULL,
    priority INT DEFAULT 0,
    applies_to_roles JSON NULL COMMENT 'Array of role IDs, NULL = all',
    applies_to_clearance_levels JSON NULL COMMENT 'Array of clearance level codes',
    applies_to_purposes JSON NULL COMMENT 'Array of purpose codes',
    watermark_text VARCHAR(255) NULL,
    watermark_image_path VARCHAR(500) NULL,
    watermark_position ENUM('center', 'top_left', 'top_right', 'bottom_left', 'bottom_right', 'tile') DEFAULT 'bottom_right',
    watermark_opacity INT DEFAULT 50 COMMENT '0-100',
    redaction_areas JSON NULL COMMENT 'Array of {x, y, width, height, page}',
    redaction_color VARCHAR(7) DEFAULT '#000000',
    max_width INT NULL,
    max_height INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_collection (collection_id),
    INDEX idx_rule_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Derivative rule log (audit trail)
CREATE TABLE IF NOT EXISTS rights_derivative_log (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NULL,
    object_id INT NOT NULL,
    user_id INT NULL,
    action_type VARCHAR(50) NOT NULL,
    original_path VARCHAR(500) NULL,
    derivative_path VARCHAR(500) NULL,
    parameters JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rule (rule_id),
    INDEX idx_object (object_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security watermark log
CREATE TABLE IF NOT EXISTS security_watermark_log (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    user_id INT NULL,
    watermark_type VARCHAR(50) NOT NULL,
    access_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default watermark settings
INSERT IGNORE INTO watermark_setting (setting_key, setting_value, description) VALUES
('watermark_enabled', '1', 'Enable watermarking globally'),
('default_position', 'center', 'Default watermark position'),
('default_opacity', '0.4', 'Default watermark opacity'),
('apply_to_downloads', '1', 'Apply watermark to downloaded files'),
('apply_to_previews', '0', 'Apply watermark to preview images');

-- Default watermark types
INSERT IGNORE INTO watermark_type (code, name, image_file, position, opacity, sort_order) VALUES
('draft', 'Draft', 'draft.png', 'tile', 0.20, 1),
('confidential', 'Confidential', 'confidential.png', 'center', 0.30, 2),
('copyright', 'Copyright', 'copyright.png', 'bottom_right', 0.40, 3),
('sample', 'Sample', 'sample.png', 'tile', 0.25, 4),
('restricted', 'Restricted', 'restricted.png', 'center', 0.35, 5);

-- Default Creative Commons licenses
INSERT IGNORE INTO creative_commons_license (uri, code, version, allows_adaptation, allows_commercial, requires_attribution, requires_sharealike, sort_order) VALUES
('https://creativecommons.org/licenses/by/4.0/', 'CC-BY', '4.0', 1, 1, 1, 0, 1),
('https://creativecommons.org/licenses/by-sa/4.0/', 'CC-BY-SA', '4.0', 1, 1, 1, 1, 2),
('https://creativecommons.org/licenses/by-nd/4.0/', 'CC-BY-ND', '4.0', 0, 1, 1, 0, 3),
('https://creativecommons.org/licenses/by-nc/4.0/', 'CC-BY-NC', '4.0', 1, 0, 1, 0, 4),
('https://creativecommons.org/licenses/by-nc-sa/4.0/', 'CC-BY-NC-SA', '4.0', 1, 0, 1, 1, 5),
('https://creativecommons.org/licenses/by-nc-nd/4.0/', 'CC-BY-NC-ND', '4.0', 0, 0, 1, 0, 6),
('https://creativecommons.org/publicdomain/zero/1.0/', 'CC0', '1.0', 1, 1, 0, 0, 7);

INSERT IGNORE INTO creative_commons_license_i18n (id, culture, name, description) VALUES
(1, 'en', 'Attribution', 'Allows others to distribute, remix, adapt, and build upon your work, even commercially, as long as they credit you.'),
(2, 'en', 'Attribution-ShareAlike', 'Allows remix and commercial use, but derivatives must be licensed under identical terms.'),
(3, 'en', 'Attribution-NoDerivs', 'Allows commercial use and redistribution, but no derivatives or adaptations.'),
(4, 'en', 'Attribution-NonCommercial', 'Allows remix and adaptation for non-commercial purposes with attribution.'),
(5, 'en', 'Attribution-NonCommercial-ShareAlike', 'Non-commercial remix with same license required.'),
(6, 'en', 'Attribution-NonCommercial-NoDerivs', 'Most restrictive - non-commercial, no derivatives.'),
(7, 'en', 'Public Domain', 'No rights reserved. Work is in the public domain.');
