-- =====================================================
-- ahgRightsPlugin Safe Upgrade Script
-- Handles existing data gracefully
-- Run: mysql -u root archive < upgrade.sql
-- =====================================================

-- =====================================================
-- CREATE TABLES (IF NOT EXISTS - safe for re-runs)
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_statement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    uri VARCHAR(255) NOT NULL,
    category ENUM('in_copyright', 'no_copyright', 'other') NOT NULL,
    allows_commercial_use TINYINT(1) DEFAULT NULL,
    allows_derivatives TINYINT(1) DEFAULT NULL,
    requires_attribution TINYINT(1) DEFAULT 1,
    icon_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_statement_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    definition TEXT,
    PRIMARY KEY (id, culture),
    FOREIGN KEY (id) REFERENCES rights_statement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_cc_license (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    version VARCHAR(10) DEFAULT '4.0',
    uri VARCHAR(255) NOT NULL,
    allows_commercial TINYINT(1) DEFAULT 1,
    allows_derivatives TINYINT(1) DEFAULT 1,
    requires_share_alike TINYINT(1) DEFAULT 0,
    requires_attribution TINYINT(1) DEFAULT 1,
    icon_url VARCHAR(255),
    badge_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_cc_license_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    legal_code_url VARCHAR(255),
    PRIMARY KEY (id, culture),
    FOREIGN KEY (id) REFERENCES rights_cc_license(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_tk_label (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    category ENUM('tk', 'bc', 'attribution', 'other') NOT NULL,
    uri VARCHAR(255),
    icon_url VARCHAR(255),
    svg_code TEXT,
    color VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_tk_label_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    provenance_text TEXT,
    PRIMARY KEY (id, culture),
    FOREIGN KEY (id) REFERENCES rights_tk_label(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_record (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'accession', 'actor', 'digital_object') DEFAULT 'information_object',
    
    -- PREMIS rights basis
    basis ENUM('copyright', 'license', 'statute', 'donor', 'policy', 'other') NOT NULL,
    
    -- Rights Statement (rightsstatements.org)
    rights_statement_id INT,
    
    -- Copyright specific
    copyright_status ENUM('copyrighted', 'public_domain', 'unknown', 'orphan') DEFAULT NULL,
    copyright_status_date DATE,
    copyright_jurisdiction VARCHAR(2),
    copyright_expiry_date DATE,
    copyright_holder VARCHAR(255),
    copyright_note TEXT,
    
    -- License specific
    license_type ENUM('cc', 'open', 'proprietary', 'custom') DEFAULT NULL,
    cc_license_id INT,
    license_identifier VARCHAR(100),
    license_url VARCHAR(255),
    license_terms TEXT,
    
    -- Statute specific
    statute_jurisdiction VARCHAR(2),
    statute_citation VARCHAR(255),
    statute_determination_date DATE,
    statute_note TEXT,
    
    -- Donor specific
    donor_name VARCHAR(255),
    
    -- Policy specific
    policy_identifier VARCHAR(100),
    
    -- Date range
    start_date DATE,
    end_date DATE,
    end_date_open TINYINT(1) DEFAULT 0,
    
    -- Rights holder
    rights_holder_id INT,
    rights_holder_name VARCHAR(255),
    
    -- Notes
    rights_note TEXT,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_basis (basis),
    INDEX idx_dates (start_date, end_date),
    FOREIGN KEY (rights_statement_id) REFERENCES rights_statement(id) ON DELETE SET NULL,
    FOREIGN KEY (cc_license_id) REFERENCES rights_cc_license(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_granted (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rights_record_id INT NOT NULL,
    act ENUM('delete', 'discover', 'display', 'disseminate', 'migrate', 'modify', 'replicate', 'use') NOT NULL,
    restriction ENUM('allow', 'disallow', 'conditional') DEFAULT 'allow',
    restriction_reason TEXT,
    start_date DATE,
    end_date DATE,
    notes TEXT,
    
    INDEX idx_rights (rights_record_id),
    INDEX idx_act (act),
    FOREIGN KEY (rights_record_id) REFERENCES rights_record(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_embargo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'accession', 'digital_object') DEFAULT 'information_object',
    
    embargo_type ENUM('full', 'metadata_only', 'digital_only', 'partial') DEFAULT 'full',
    reason ENUM('donor_restriction', 'privacy', 'legal', 'commercial', 'cultural', 'other') NOT NULL,
    reason_detail TEXT,
    
    start_date DATE NOT NULL,
    end_date DATE,
    end_date_indefinite TINYINT(1) DEFAULT 0,
    
    auto_release TINYINT(1) DEFAULT 0,
    notify_before_days INT DEFAULT 30,
    notification_sent TINYINT(1) DEFAULT 0,
    notification_date DATE,
    
    allowed_users JSON,
    allowed_groups JSON,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    released_at DATETIME,
    released_by INT,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_auto_release (auto_release, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_orphan_work (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'digital_object') DEFAULT 'information_object',
    
    status ENUM('suspected', 'confirmed', 'cleared', 'claimed') DEFAULT 'suspected',
    determination_date DATE,
    
    search_conducted TINYINT(1) DEFAULT 0,
    search_date DATE,
    search_sources TEXT,
    search_documentation TEXT,
    
    eu_orphan_database TINYINT(1) DEFAULT 0,
    eu_registration_date DATE,
    eu_registration_number VARCHAR(100),
    
    diligent_search_report TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_object_tk_label (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    tk_label_id INT NOT NULL,
    
    community_id INT,
    community_name VARCHAR(255),
    provenance_statement TEXT,
    
    verified TINYINT(1) DEFAULT 0,
    verified_date DATE,
    verified_by VARCHAR(255),
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    
    UNIQUE KEY unique_object_label (object_id, tk_label_id),
    INDEX idx_object (object_id),
    FOREIGN KEY (tk_label_id) REFERENCES rights_tk_label(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_territory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rights_record_id INT,
    object_id INT,
    
    territory_code VARCHAR(2) NOT NULL,
    territory_type ENUM('include', 'exclude') DEFAULT 'include',
    
    restriction_type ENUM('no_access', 'metadata_only', 'low_resolution', 'watermark') DEFAULT 'no_access',
    reason TEXT,
    
    FOREIGN KEY (rights_record_id) REFERENCES rights_record(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_derivative_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    
    watermark_enabled TINYINT(1) DEFAULT 0,
    watermark_text VARCHAR(255),
    watermark_image_path VARCHAR(255),
    watermark_position ENUM('center', 'bottom-right', 'bottom-left', 'top-right', 'top-left', 'tile') DEFAULT 'bottom-right',
    watermark_opacity INT DEFAULT 50,
    
    max_dimension INT,
    output_format ENUM('jpeg', 'png', 'webp', 'original') DEFAULT 'jpeg',
    jpeg_quality INT DEFAULT 80,
    strip_exif TINYINT(1) DEFAULT 1,
    
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type VARCHAR(50),
    
    action ENUM('create', 'update', 'delete', 'embargo_set', 'embargo_release', 'access_granted', 'access_denied') NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    user_id INT,
    user_name VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERT DATA (IGNORE duplicates)
-- =====================================================

-- RightsStatements.org vocabulary
INSERT IGNORE INTO rights_statement (code, uri, category, allows_commercial_use, allows_derivatives, sort_order) VALUES
('InC', 'http://rightsstatements.org/vocab/InC/1.0/', 'in_copyright', 0, 0, 1),
('InC-OW-EU', 'http://rightsstatements.org/vocab/InC-OW-EU/1.0/', 'in_copyright', 0, 0, 2),
('InC-EDU', 'http://rightsstatements.org/vocab/InC-EDU/1.0/', 'in_copyright', 0, 1, 3),
('InC-NC', 'http://rightsstatements.org/vocab/InC-NC/1.0/', 'in_copyright', 0, 1, 4),
('InC-RUU', 'http://rightsstatements.org/vocab/InC-RUU/1.0/', 'in_copyright', NULL, NULL, 5),
('NoC-CR', 'http://rightsstatements.org/vocab/NoC-CR/1.0/', 'no_copyright', 1, 1, 6),
('NoC-NC', 'http://rightsstatements.org/vocab/NoC-NC/1.0/', 'no_copyright', 0, 1, 7),
('NoC-OKLR', 'http://rightsstatements.org/vocab/NoC-OKLR/1.0/', 'no_copyright', 1, 1, 8),
('NoC-US', 'http://rightsstatements.org/vocab/NoC-US/1.0/', 'no_copyright', 1, 1, 9),
('CNE', 'http://rightsstatements.org/vocab/CNE/1.0/', 'other', NULL, NULL, 10),
('UND', 'http://rightsstatements.org/vocab/UND/1.0/', 'other', NULL, NULL, 11),
('NKC', 'http://rightsstatements.org/vocab/NKC/1.0/', 'other', NULL, NULL, 12);

-- RightsStatements i18n (using REPLACE to update if exists)
REPLACE INTO rights_statement_i18n (id, culture, name, description) 
SELECT id, 'en', name, description FROM (
    SELECT 
        (SELECT id FROM rights_statement WHERE code = 'InC') as id,
        'In Copyright' as name,
        'This item is protected by copyright and/or related rights.' as description
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'InC-OW-EU'),
        'In Copyright - EU Orphan Work',
        'This item has been identified as an orphan work in the EU.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'InC-EDU'),
        'In Copyright - Educational Use Permitted',
        'This item is protected by copyright but educational use is permitted.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'InC-NC'),
        'In Copyright - Non-Commercial Use Permitted',
        'This item is protected by copyright but non-commercial use is permitted.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'InC-RUU'),
        'In Copyright - Rights-holder(s) Unlocatable',
        'This item is protected but the rights-holder cannot be located.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'NoC-CR'),
        'No Copyright - Contractual Restrictions',
        'This item is not protected but has contractual restrictions.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'NoC-NC'),
        'No Copyright - Non-Commercial Use Only',
        'This item is not protected but use is limited to non-commercial purposes.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'NoC-OKLR'),
        'No Copyright - Other Known Legal Restrictions',
        'This item is not protected but has other legal restrictions.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'NoC-US'),
        'No Copyright - United States',
        'This item is not protected by copyright in the United States.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'CNE'),
        'Copyright Not Evaluated',
        'The copyright status has not been evaluated.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'UND'),
        'Copyright Undetermined',
        'The copyright status is unknown.'
    UNION ALL SELECT 
        (SELECT id FROM rights_statement WHERE code = 'NKC'),
        'No Known Copyright',
        'No copyright or related rights are known to exist.'
) AS t WHERE t.id IS NOT NULL;

-- Creative Commons licenses
INSERT IGNORE INTO rights_cc_license (code, version, uri, allows_commercial, allows_derivatives, requires_share_alike, sort_order) VALUES
('CC0', '1.0', 'https://creativecommons.org/publicdomain/zero/1.0/', 1, 1, 0, 1),
('CC-BY', '4.0', 'https://creativecommons.org/licenses/by/4.0/', 1, 1, 0, 2),
('CC-BY-SA', '4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 1, 1, 1, 3),
('CC-BY-NC', '4.0', 'https://creativecommons.org/licenses/by-nc/4.0/', 0, 1, 0, 4),
('CC-BY-NC-SA', '4.0', 'https://creativecommons.org/licenses/by-nc-sa/4.0/', 0, 1, 1, 5),
('CC-BY-ND', '4.0', 'https://creativecommons.org/licenses/by-nd/4.0/', 1, 0, 0, 6),
('CC-BY-NC-ND', '4.0', 'https://creativecommons.org/licenses/by-nc-nd/4.0/', 0, 0, 0, 7),
('PDM', '1.0', 'https://creativecommons.org/publicdomain/mark/1.0/', 1, 1, 0, 8);

-- CC license i18n
REPLACE INTO rights_cc_license_i18n (id, culture, name, description)
SELECT id, 'en', name, description FROM (
    SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC0') as id,
        'CC0 - Public Domain Dedication' as name,
        'No rights reserved. Waives all copyright.' as description
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC-BY'),
        'Attribution',
        'Credit must be given to the creator.'
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC-BY-SA'),
        'Attribution-ShareAlike',
        'Credit must be given; adaptations must be shared alike.'
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC-BY-NC'),
        'Attribution-NonCommercial',
        'Credit must be given; only non-commercial use allowed.'
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC-BY-NC-SA'),
        'Attribution-NonCommercial-ShareAlike',
        'Credit must be given; non-commercial; share alike.'
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC-BY-ND'),
        'Attribution-NoDerivatives',
        'Credit must be given; no derivatives allowed.'
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'CC-BY-NC-ND'),
        'Attribution-NonCommercial-NoDerivatives',
        'Credit must be given; non-commercial; no derivatives.'
    UNION ALL SELECT 
        (SELECT id FROM rights_cc_license WHERE code = 'PDM'),
        'Public Domain Mark',
        'This work is free of known copyright restrictions.'
) AS t WHERE t.id IS NOT NULL;

-- TK Labels
INSERT IGNORE INTO rights_tk_label (code, category, uri, color, sort_order) VALUES
('TK-A', 'attribution', 'https://localcontexts.org/label/tk-attribution/', '#4A90D9', 1),
('TK-NC', 'tk', 'https://localcontexts.org/label/tk-non-commercial/', '#7B8D42', 2),
('TK-C', 'tk', 'https://localcontexts.org/label/tk-community/', '#D35400', 3),
('TK-CV', 'tk', 'https://localcontexts.org/label/tk-culturally-sensitive/', '#8E44AD', 4),
('TK-S', 'tk', 'https://localcontexts.org/label/tk-secret-sacred/', '#C0392B', 5),
('TK-MC', 'tk', 'https://localcontexts.org/label/tk-multiple-communities/', '#16A085', 6),
('TK-MR', 'tk', 'https://localcontexts.org/label/tk-men-restricted/', '#2C3E50', 7),
('TK-WR', 'tk', 'https://localcontexts.org/label/tk-women-restricted/', '#E74C3C', 8),
('TK-SS', 'tk', 'https://localcontexts.org/label/tk-seasonal/', '#F39C12', 9),
('TK-F', 'tk', 'https://localcontexts.org/label/tk-family/', '#27AE60', 10),
('TK-O', 'tk', 'https://localcontexts.org/label/tk-outreach/', '#3498DB', 11),
('TK-V', 'tk', 'https://localcontexts.org/label/tk-verified/', '#1ABC9C', 12),
('TK-NV', 'tk', 'https://localcontexts.org/label/tk-non-verified/', '#95A5A6', 13),
('BC-R', 'bc', 'https://localcontexts.org/label/bc-research/', '#9B59B6', 14),
('BC-CB', 'bc', 'https://localcontexts.org/label/bc-consent/', '#E67E22', 15),
('BC-P', 'bc', 'https://localcontexts.org/label/bc-provenance/', '#1ABC9C', 16);

-- TK Label i18n
REPLACE INTO rights_tk_label_i18n (id, culture, name, description)
SELECT id, 'en', name, description FROM (
    SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-A') as id,
        'TK Attribution' as name,
        'This material has traditional knowledge associated with it.' as description
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-NC'),
        'TK Non-Commercial',
        'This material can only be used for non-commercial purposes.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-C'),
        'TK Community Voice',
        'Community protocols govern use of this material.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-CV'),
        'TK Culturally Sensitive',
        'This material contains culturally sensitive content.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-S'),
        'TK Secret/Sacred',
        'This material contains secret or sacred content.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-MC'),
        'TK Multiple Communities',
        'Multiple communities have interests in this material.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-MR'),
        'TK Men Restricted',
        'Access to this material is restricted to men only.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-WR'),
        'TK Women Restricted',
        'Access to this material is restricted to women only.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-SS'),
        'TK Seasonal',
        'Access to this material is seasonally restricted.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-F'),
        'TK Family',
        'This material belongs to a specific family.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-O'),
        'TK Outreach',
        'This material is approved for educational outreach.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-V'),
        'TK Verified',
        'Community attribution has been verified.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'TK-NV'),
        'TK Non-Verified',
        'Community attribution has not been verified.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'BC-R'),
        'BC Research Use',
        'Biocultural research use protocols apply.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'BC-CB'),
        'BC Consent/Benefit',
        'Consent and benefit sharing protocols apply.'
    UNION ALL SELECT 
        (SELECT id FROM rights_tk_label WHERE code = 'BC-P'),
        'BC Provenance',
        'Provenance has been documented.'
) AS t WHERE t.id IS NOT NULL;

-- Default derivative profiles
INSERT IGNORE INTO rights_derivative_profile (name, code, watermark_enabled, max_dimension, output_format, jpeg_quality, strip_exif, is_default) VALUES
('Public Access', 'public', 1, 1200, 'jpeg', 75, 1, 1),
('Research Access', 'research', 0, 2400, 'jpeg', 90, 0, 0),
('Staff Access', 'staff', 0, NULL, 'original', 100, 0, 0);

-- =====================================================
-- COMPLETE
-- =====================================================
SELECT 'ahgRightsPlugin upgrade complete!' as status;
SELECT COUNT(*) as rights_statements FROM rights_statement;
SELECT COUNT(*) as cc_licenses FROM rights_cc_license;
SELECT COUNT(*) as tk_labels FROM rights_tk_label;
