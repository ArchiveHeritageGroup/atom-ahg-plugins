-- =====================================================
-- ahgRightsPlugin Database Schema
-- Comprehensive Rights Management
-- =====================================================

-- =====================================================
-- RIGHTS STATEMENTS (rightsstatements.org vocabulary)
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

-- =====================================================
-- CREATIVE COMMONS LICENSES
-- =====================================================

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

-- =====================================================
-- TRADITIONAL KNOWLEDGE LABELS (Local Contexts)
-- =====================================================

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

-- =====================================================
-- ENHANCED RIGHTS RECORD
-- Extends/replaces core AtoM rights table
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_record (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'accession', 'actor', 'digital_object') DEFAULT 'information_object',
    
    -- PREMIS basis
    basis ENUM('copyright', 'license', 'statute', 'donor', 'policy', 'other') NOT NULL,
    basis_note TEXT,
    
    -- Rights statement (rightsstatements.org)
    rights_statement_id INT,
    
    -- Copyright specific
    copyright_status ENUM('copyrighted', 'public_domain', 'unknown') DEFAULT NULL,
    copyright_jurisdiction CHAR(2),
    copyright_status_date DATE,
    copyright_holder VARCHAR(500),
    copyright_holder_contact TEXT,
    copyright_expiry_date DATE,
    copyright_note TEXT,
    
    -- License specific
    license_type ENUM('cc', 'custom', 'proprietary', 'open') DEFAULT NULL,
    cc_license_id INT,
    license_identifier VARCHAR(255),
    license_terms TEXT,
    license_url VARCHAR(500),
    license_note TEXT,
    
    -- Statute specific
    statute_jurisdiction CHAR(2),
    statute_citation VARCHAR(500),
    statute_determination_date DATE,
    statute_note TEXT,
    
    -- Donor/Policy specific
    donor_name VARCHAR(255),
    policy_identifier VARCHAR(255),
    agreement_date DATE,
    other_basis VARCHAR(255),
    other_basis_note TEXT,
    
    -- Duration
    start_date DATE,
    end_date DATE,
    end_date_open TINYINT(1) DEFAULT 0,
    
    -- Rights holder
    rights_holder_id INT,
    rights_holder_name VARCHAR(500),
    rights_holder_role VARCHAR(100),
    
    -- General
    rights_note TEXT,
    documentation_identifier VARCHAR(255),
    documentation_url VARCHAR(500),
    
    -- Audit
    created_by INT,
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_basis (basis),
    INDEX idx_dates (start_date, end_date),
    FOREIGN KEY (rights_statement_id) REFERENCES rights_statement(id) ON DELETE SET NULL,
    FOREIGN KEY (cc_license_id) REFERENCES rights_cc_license(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- GRANTED RIGHTS (PREMIS acts)
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_granted (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rights_record_id INT NOT NULL,
    act ENUM('render', 'disseminate', 'replicate', 'migrate', 'modify', 'delete', 'print', 'use', 'other') NOT NULL,
    restriction ENUM('allow', 'disallow', 'conditional') DEFAULT 'allow',
    restriction_reason VARCHAR(500),
    start_date DATE,
    end_date DATE,
    grant_note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rights (rights_record_id),
    INDEX idx_act (act),
    FOREIGN KEY (rights_record_id) REFERENCES rights_record(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- EMBARGOES
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_embargo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'digital_object', 'accession') DEFAULT 'information_object',
    
    embargo_type ENUM('full', 'metadata_only', 'digital_only', 'partial') DEFAULT 'full',
    reason ENUM('donor_restriction', 'copyright', 'privacy', 'legal', 'commercial', 'research', 'other') NOT NULL,
    reason_detail TEXT,
    
    start_date DATE NOT NULL,
    end_date DATE,
    auto_release TINYINT(1) DEFAULT 1,
    release_notification_days INT DEFAULT 30,
    
    -- Access override
    allow_staff TINYINT(1) DEFAULT 1,
    allow_researchers TINYINT(1) DEFAULT 0,
    access_password VARCHAR(255),
    access_note TEXT,
    
    -- Notifications
    notification_sent TINYINT(1) DEFAULT 0,
    notification_date DATETIME,
    released_at DATETIME,
    released_by INT,
    
    -- Audit
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_auto_release (auto_release, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ORPHAN WORKS DUE DILIGENCE
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_orphan_work (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    
    status ENUM('suspected', 'diligent_search_started', 'diligent_search_complete', 'confirmed_orphan', 'rights_holder_found', 'licensed') DEFAULT 'suspected',
    
    -- Work details
    work_type VARCHAR(100),
    work_title VARCHAR(500),
    creation_date VARCHAR(100),
    publication_date VARCHAR(100),
    creator_name VARCHAR(500),
    creator_death_date VARCHAR(100),
    
    -- Search documentation
    search_started_date DATE,
    search_completed_date DATE,
    search_conducted_by VARCHAR(255),
    search_methodology TEXT,
    sources_searched TEXT,
    search_results TEXT,
    
    -- Evidence
    evidence_summary TEXT,
    documentation_path VARCHAR(500),
    
    -- If rights holder found
    rights_holder_found_date DATE,
    rights_holder_contact TEXT,
    license_obtained TINYINT(1) DEFAULT 0,
    license_details TEXT,
    
    -- Jurisdiction
    jurisdiction CHAR(2),
    applicable_law VARCHAR(255),
    
    -- Audit
    created_by INT,
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_orphan_search_source (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orphan_work_id INT NOT NULL,
    source_name VARCHAR(255) NOT NULL,
    source_type ENUM('database', 'registry', 'organization', 'publication', 'web', 'archive', 'other') NOT NULL,
    source_url VARCHAR(500),
    searched_date DATE,
    search_terms TEXT,
    results_found TINYINT(1) DEFAULT 0,
    results_note TEXT,
    evidence_path VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_orphan (orphan_work_id),
    FOREIGN KEY (orphan_work_id) REFERENCES rights_orphan_work(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TERRITORY RESTRICTIONS (GDPR/Geographic)
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_territory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'digital_object') DEFAULT 'information_object',
    
    restriction_type ENUM('allow', 'deny') DEFAULT 'deny',
    territory_code CHAR(2) NOT NULL,
    territory_type ENUM('country', 'region', 'eu', 'eea', 'custom') DEFAULT 'country',
    
    reason ENUM('gdpr', 'copyright', 'legal', 'commercial', 'other') NOT NULL,
    reason_detail TEXT,
    
    applies_to ENUM('all', 'digital_only', 'metadata_only') DEFAULT 'all',
    
    start_date DATE,
    end_date DATE,
    
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    INDEX idx_territory (territory_code),
    INDEX idx_type (restriction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TK LABEL ASSIGNMENTS
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_object_tk_label (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'digital_object', 'actor') DEFAULT 'information_object',
    tk_label_id INT NOT NULL,
    
    community_name VARCHAR(255),
    community_contact TEXT,
    provenance_statement TEXT,
    cultural_note TEXT,
    
    assigned_by INT,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    FOREIGN KEY (tk_label_id) REFERENCES rights_tk_label(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ACCESS DERIVATIVE PROFILES (Watermarking, etc.)
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_derivative_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    
    -- Watermark settings
    watermark_enabled TINYINT(1) DEFAULT 0,
    watermark_text VARCHAR(255),
    watermark_image_path VARCHAR(500),
    watermark_position ENUM('center', 'tile', 'bottom_right', 'bottom_left', 'top_right', 'top_left') DEFAULT 'bottom_right',
    watermark_opacity INT DEFAULT 50,
    
    -- Resolution limits
    max_dimension INT,
    max_resolution_dpi INT,
    
    -- Format conversion
    output_format ENUM('original', 'jpeg', 'png', 'webp', 'pdf') DEFAULT 'jpeg',
    jpeg_quality INT DEFAULT 85,
    
    -- Metadata stripping
    strip_exif TINYINT(1) DEFAULT 0,
    strip_iptc TINYINT(1) DEFAULT 0,
    strip_xmp TINYINT(1) DEFAULT 0,
    
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rights_object_derivative_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type ENUM('information_object', 'digital_object') DEFAULT 'information_object',
    derivative_profile_id INT NOT NULL,
    
    applies_to_user_type ENUM('all', 'anonymous', 'authenticated', 'researcher', 'staff') DEFAULT 'anonymous',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id, object_type),
    FOREIGN KEY (derivative_profile_id) REFERENCES rights_derivative_profile(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- RIGHTS AUDIT LOG
-- =====================================================

CREATE TABLE IF NOT EXISTS rights_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    object_type VARCHAR(50) NOT NULL,
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
-- DEFAULT DATA
-- =====================================================

-- RightsStatements.org vocabulary
INSERT INTO rights_statement (code, uri, category, allows_commercial_use, allows_derivatives, sort_order) VALUES
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

INSERT INTO rights_statement_i18n (id, culture, name, description) VALUES
(1, 'en', 'In Copyright', 'This item is protected by copyright and/or related rights.'),
(2, 'en', 'In Copyright - EU Orphan Work', 'This item has been identified as an orphan work in the EU.'),
(3, 'en', 'In Copyright - Educational Use Permitted', 'This item is protected by copyright but educational use is permitted.'),
(4, 'en', 'In Copyright - Non-Commercial Use Permitted', 'This item is protected by copyright but non-commercial use is permitted.'),
(5, 'en', 'In Copyright - Rights-holder(s) Unlocatable', 'This item is protected but the rights-holder cannot be located.'),
(6, 'en', 'No Copyright - Contractual Restrictions', 'This item is not protected but has contractual restrictions.'),
(7, 'en', 'No Copyright - Non-Commercial Use Only', 'This item is not protected but use is limited to non-commercial purposes.'),
(8, 'en', 'No Copyright - Other Known Legal Restrictions', 'This item is not protected but has other legal restrictions.'),
(9, 'en', 'No Copyright - United States', 'This item is not protected by copyright in the United States.'),
(10, 'en', 'Copyright Not Evaluated', 'The copyright status has not been evaluated.'),
(11, 'en', 'Copyright Undetermined', 'The copyright status is unknown.'),
(12, 'en', 'No Known Copyright', 'No copyright or related rights are known to exist.');

-- Creative Commons licenses
INSERT INTO rights_cc_license (code, version, uri, allows_commercial, allows_derivatives, requires_share_alike, sort_order) VALUES
('CC0', '1.0', 'https://creativecommons.org/publicdomain/zero/1.0/', 1, 1, 0, 1),
('CC-BY', '4.0', 'https://creativecommons.org/licenses/by/4.0/', 1, 1, 0, 2),
('CC-BY-SA', '4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 1, 1, 1, 3),
('CC-BY-NC', '4.0', 'https://creativecommons.org/licenses/by-nc/4.0/', 0, 1, 0, 4),
('CC-BY-NC-SA', '4.0', 'https://creativecommons.org/licenses/by-nc-sa/4.0/', 0, 1, 1, 5),
('CC-BY-ND', '4.0', 'https://creativecommons.org/licenses/by-nd/4.0/', 1, 0, 0, 6),
('CC-BY-NC-ND', '4.0', 'https://creativecommons.org/licenses/by-nc-nd/4.0/', 0, 0, 0, 7),
('PDM', '1.0', 'https://creativecommons.org/publicdomain/mark/1.0/', 1, 1, 0, 8);

INSERT INTO rights_cc_license_i18n (id, culture, name, description) VALUES
(1, 'en', 'CC0 - Public Domain Dedication', 'No rights reserved. Waives all copyright.'),
(2, 'en', 'Attribution', 'Credit must be given to the creator.'),
(3, 'en', 'Attribution-ShareAlike', 'Credit must be given; adaptations must be shared alike.'),
(4, 'en', 'Attribution-NonCommercial', 'Credit must be given; only non-commercial use allowed.'),
(5, 'en', 'Attribution-NonCommercial-ShareAlike', 'Credit must be given; non-commercial; share alike.'),
(6, 'en', 'Attribution-NoDerivatives', 'Credit must be given; no derivatives allowed.'),
(7, 'en', 'Attribution-NonCommercial-NoDerivatives', 'Credit must be given; non-commercial; no derivatives.'),
(8, 'en', 'Public Domain Mark', 'This work is free of known copyright restrictions.');

-- TK Labels
INSERT INTO rights_tk_label (code, category, uri, color, sort_order) VALUES
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

INSERT INTO rights_tk_label_i18n (id, culture, name, description) VALUES
(1, 'en', 'TK Attribution', 'This material has traditional knowledge associated with it.'),
(2, 'en', 'TK Non-Commercial', 'This material can only be used for non-commercial purposes.'),
(3, 'en', 'TK Community Voice', 'Community protocols govern use of this material.'),
(4, 'en', 'TK Culturally Sensitive', 'This material contains culturally sensitive content.'),
(5, 'en', 'TK Secret/Sacred', 'This material contains secret or sacred content.'),
(6, 'en', 'TK Multiple Communities', 'Multiple communities have interests in this material.'),
(7, 'en', 'TK Men Restricted', 'Access to this material is restricted to men only.'),
(8, 'en', 'TK Women Restricted', 'Access to this material is restricted to women only.'),
(9, 'en', 'TK Seasonal', 'Access to this material is seasonally restricted.'),
(10, 'en', 'TK Family', 'This material belongs to a specific family.'),
(11, 'en', 'TK Outreach', 'This material is approved for educational outreach.'),
(12, 'en', 'TK Verified', 'Community attribution has been verified.'),
(13, 'en', 'TK Non-Verified', 'Community attribution has not been verified.'),
(14, 'en', 'BC Research Use', 'Biocultural research use protocols apply.'),
(15, 'en', 'BC Consent/Benefit', 'Consent and benefit sharing protocols apply.'),
(16, 'en', 'BC Provenance', 'Provenance has been documented.');

-- Default derivative profile
INSERT INTO rights_derivative_profile (name, code, watermark_enabled, max_dimension, output_format, jpeg_quality, strip_exif, is_default) VALUES
('Public Access', 'public', 1, 1200, 'jpeg', 75, 1, 1),
('Research Access', 'research', 0, 2400, 'jpeg', 90, 0, 0),
('Staff Access', 'staff', 0, NULL, 'original', 100, 0, 0);
