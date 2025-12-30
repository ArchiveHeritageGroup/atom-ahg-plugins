-- =============================================================================
-- ahgRightsPlugin Database Migration
-- Version: 1.0.0
-- AtoM 2.10 / Laravel / MySQL 8 / PHP 8.3
-- =============================================================================
-- Comprehensive Rights Management:
-- 1. Rights Statements (rightsstatements.org)
-- 2. Creative Commons Licenses
-- 3. Traditional Knowledge Labels
-- 4. Orphan Works Due Diligence
-- 5. Embargo Management
-- 6. Territory Restrictions
-- 7. Rights Grants (PREMIS Acts)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. RIGHTS STATEMENTS (rightsstatements.org vocabulary)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_statement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    uri VARCHAR(255) NOT NULL,
    category ENUM('in_copyright', 'no_copyright', 'other') NOT NULL,
    allows_commercial_use TINYINT(1) DEFAULT NULL,
    allows_derivatives TINYINT(1) DEFAULT NULL,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_statement_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    name VARCHAR(255),
    description TEXT,
    usage_guidelines TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_statement_i18n FOREIGN KEY (id) 
        REFERENCES rights_statement(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. CREATIVE COMMONS LICENSES
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_cc_license (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    version VARCHAR(10) NOT NULL DEFAULT '4.0',
    uri VARCHAR(255) NOT NULL,
    allows_commercial TINYINT(1) DEFAULT 1,
    allows_derivatives TINYINT(1) DEFAULT 1,
    requires_share_alike TINYINT(1) DEFAULT 0,
    requires_attribution TINYINT(1) DEFAULT 1,
    icon VARCHAR(100),
    badge_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_cc_license_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    name VARCHAR(255),
    description TEXT,
    human_readable TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_cc_license_i18n FOREIGN KEY (id) 
        REFERENCES rights_cc_license(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TRADITIONAL KNOWLEDGE LABELS (Local Contexts)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_tk_label (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    category ENUM('tk', 'bc', 'attribution') NOT NULL DEFAULT 'tk',
    uri VARCHAR(255),
    color VARCHAR(7) COMMENT 'Hex color code',
    icon_path VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_tk_label_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    name VARCHAR(255),
    description TEXT,
    usage_protocol TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_tk_label_i18n FOREIGN KEY (id) 
        REFERENCES rights_tk_label(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. OBJECT RIGHTS (Main rights records linked to information_object)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_record (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'FK to information_object.id',
    
    -- PREMIS Rights Basis
    basis ENUM('copyright', 'license', 'statute', 'donor', 'policy', 'other') NOT NULL DEFAULT 'copyright',
    
    -- Rights Statement (rightsstatements.org)
    rights_statement_id INT,
    
    -- Creative Commons
    cc_license_id INT,
    
    -- Copyright specifics
    copyright_status ENUM('copyrighted', 'public_domain', 'unknown') DEFAULT 'unknown',
    copyright_holder VARCHAR(255),
    copyright_holder_actor_id INT COMMENT 'FK to actor.id',
    copyright_jurisdiction VARCHAR(10) DEFAULT 'ZA' COMMENT 'ISO 3166-1 alpha-2',
    copyright_determination_date DATE,
    copyright_note TEXT,
    
    -- License specifics
    license_identifier VARCHAR(255),
    license_terms TEXT,
    license_note TEXT,
    
    -- Statute specifics
    statute_citation VARCHAR(255),
    statute_jurisdiction VARCHAR(10),
    statute_determination_date DATE,
    statute_note TEXT,
    
    -- Donor specifics  
    donor_name VARCHAR(255),
    donor_actor_id INT COMMENT 'FK to actor.id',
    donor_agreement_date DATE,
    donor_note TEXT,
    
    -- Policy specifics
    policy_identifier VARCHAR(100),
    policy_note TEXT,
    
    -- Date range
    start_date DATE,
    end_date DATE,
    
    -- Documentation
    documentation_identifier VARCHAR(255),
    documentation_role VARCHAR(100),
    
    -- Metadata
    created_by INT COMMENT 'FK to user.id',
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_basis (basis),
    INDEX idx_status (copyright_status),
    CONSTRAINT fk_rights_statement FOREIGN KEY (rights_statement_id) 
        REFERENCES rights_statement(id) ON DELETE SET NULL,
    CONSTRAINT fk_rights_cc_license FOREIGN KEY (cc_license_id) 
        REFERENCES rights_cc_license(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_record_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    rights_note TEXT,
    restriction_note TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_record_i18n FOREIGN KEY (id) 
        REFERENCES rights_record(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. RIGHTS GRANTS (PREMIS Acts - what can be done with the object)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_grant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rights_record_id INT NOT NULL,
    
    -- PREMIS Act
    act ENUM('render', 'disseminate', 'replicate', 'migrate', 'modify', 'delete', 'print', 'use', 'publish', 'excerpt', 'annotate', 'move', 'sell') NOT NULL,
    
    -- Restriction
    restriction ENUM('allow', 'disallow', 'conditional') NOT NULL DEFAULT 'allow',
    
    -- Date range
    start_date DATE,
    end_date DATE,
    
    -- Conditions
    condition_type VARCHAR(50),
    condition_value TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rights_record (rights_record_id),
    INDEX idx_act (act),
    INDEX idx_restriction (restriction),
    CONSTRAINT fk_rights_grant_record FOREIGN KEY (rights_record_id) 
        REFERENCES rights_record(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_grant_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    restriction_note TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_grant_i18n FOREIGN KEY (id) 
        REFERENCES rights_grant(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. EMBARGO MANAGEMENT
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_embargo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'FK to information_object.id',
    
    -- Embargo type
    embargo_type ENUM('full', 'metadata_only', 'digital_only', 'partial') NOT NULL DEFAULT 'full',
    
    -- Embargo reason
    reason ENUM('donor_restriction', 'copyright', 'privacy', 'legal', 'commercial', 'research', 'cultural', 'security', 'other') NOT NULL,
    
    -- Dates
    start_date DATE NOT NULL,
    end_date DATE COMMENT 'NULL = indefinite',
    auto_release TINYINT(1) DEFAULT 1 COMMENT 'Auto-lift on end_date',
    
    -- Review
    review_date DATE,
    review_interval_months INT DEFAULT 12,
    last_reviewed_at DATETIME,
    last_reviewed_by INT,
    
    -- Status
    status ENUM('active', 'pending', 'lifted', 'expired', 'extended') DEFAULT 'active',
    lifted_at DATETIME,
    lifted_by INT,
    lift_reason TEXT,
    
    -- Notification
    notify_before_days INT DEFAULT 30,
    notification_sent TINYINT(1) DEFAULT 0,
    notify_emails TEXT COMMENT 'JSON array of emails',
    
    -- Metadata
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date),
    INDEX idx_review_date (review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_embargo_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    reason_note TEXT,
    internal_note TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_embargo_i18n FOREIGN KEY (id) 
        REFERENCES rights_embargo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Embargo history log
CREATE TABLE IF NOT EXISTS rights_embargo_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    embargo_id INT NOT NULL,
    action ENUM('created', 'extended', 'lifted', 'reviewed', 'notification_sent', 'auto_released') NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    old_end_date DATE,
    new_end_date DATE,
    notes TEXT,
    performed_by INT,
    performed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_embargo (embargo_id),
    CONSTRAINT fk_embargo_log FOREIGN KEY (embargo_id) 
        REFERENCES rights_embargo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. ORPHAN WORKS DUE DILIGENCE
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_orphan_work (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'FK to information_object.id',
    
    -- Status
    status ENUM('in_progress', 'completed', 'rights_holder_found', 'abandoned') DEFAULT 'in_progress',
    
    -- Work type
    work_type ENUM('literary', 'dramatic', 'musical', 'artistic', 'film', 'sound_recording', 'broadcast', 'typographical', 'database', 'photograph', 'other') NOT NULL,
    
    -- Search details
    search_started_date DATE,
    search_completed_date DATE,
    search_jurisdiction VARCHAR(10) DEFAULT 'ZA',
    
    -- Results
    rights_holder_found TINYINT(1) DEFAULT 0,
    rights_holder_name VARCHAR(255),
    rights_holder_contact TEXT,
    contact_attempted TINYINT(1) DEFAULT 0,
    contact_date DATE,
    contact_response TEXT,
    
    -- Usage intention
    intended_use TEXT,
    proposed_fee DECIMAL(10,2),
    fee_held_in_escrow TINYINT(1) DEFAULT 0,
    
    -- Metadata
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rights_orphan_work_i18n (
    id INT NOT NULL,
    culture VARCHAR(16) NOT NULL DEFAULT 'en',
    notes TEXT,
    search_summary TEXT,
    PRIMARY KEY (id, culture),
    CONSTRAINT fk_rights_orphan_work_i18n FOREIGN KEY (id) 
        REFERENCES rights_orphan_work(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Due diligence search steps
CREATE TABLE IF NOT EXISTS rights_orphan_search_step (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orphan_work_id INT NOT NULL,
    
    -- Search source
    source_type ENUM('database', 'registry', 'publisher', 'author_society', 'archive', 'library', 'internet', 'newspaper', 'other') NOT NULL,
    source_name VARCHAR(255) NOT NULL,
    source_url VARCHAR(500),
    
    -- Search details
    search_date DATE NOT NULL,
    search_terms TEXT,
    results_found TINYINT(1) DEFAULT 0,
    results_description TEXT,
    
    -- Evidence
    evidence_file_path VARCHAR(500),
    screenshot_path VARCHAR(500),
    
    performed_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_orphan_work (orphan_work_id),
    CONSTRAINT fk_orphan_search_step FOREIGN KEY (orphan_work_id) 
        REFERENCES rights_orphan_work(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. TERRITORY RESTRICTIONS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_territory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rights_record_id INT NOT NULL,
    
    -- Territory
    territory_type ENUM('include', 'exclude') NOT NULL DEFAULT 'include',
    country_code VARCHAR(10) NOT NULL COMMENT 'ISO 3166-1 alpha-2 or region code',
    
    -- GDPR specific
    is_gdpr_territory TINYINT(1) DEFAULT 0,
    gdpr_legal_basis VARCHAR(50),
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rights_record (rights_record_id),
    INDEX idx_country (country_code),
    CONSTRAINT fk_rights_territory_record FOREIGN KEY (rights_record_id) 
        REFERENCES rights_record(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. TK LABEL ASSIGNMENTS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_object_tk_label (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL COMMENT 'FK to information_object.id',
    tk_label_id INT NOT NULL,
    
    -- Community info
    community_name VARCHAR(255),
    community_contact TEXT,
    
    -- Customization
    custom_text TEXT,
    
    -- Verification
    verified TINYINT(1) DEFAULT 0,
    verified_by VARCHAR(255),
    verified_date DATE,
    
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_label (tk_label_id),
    UNIQUE KEY uk_object_label (object_id, tk_label_id),
    CONSTRAINT fk_object_tk_label FOREIGN KEY (tk_label_id) 
        REFERENCES rights_tk_label(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. ACCESS DERIVATIVES (watermarks, redactions)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rights_derivative_rule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Scope (object, collection, or global)
    object_id INT COMMENT 'NULL = applies to collection or global',
    collection_id INT COMMENT 'NULL = applies to object or global',
    is_global TINYINT(1) DEFAULT 0,
    
    -- Rule type
    rule_type ENUM('watermark', 'redaction', 'resize', 'format_conversion', 'metadata_strip') NOT NULL,
    
    -- Priority (higher = processed first)
    priority INT DEFAULT 0,
    
    -- Conditions
    applies_to_roles JSON COMMENT 'Array of role IDs, NULL = all',
    applies_to_clearance_levels JSON COMMENT 'Array of clearance level codes',
    applies_to_purposes JSON COMMENT 'Array of purpose codes',
    
    -- Watermark settings
    watermark_text VARCHAR(255),
    watermark_image_path VARCHAR(500),
    watermark_position ENUM('center', 'top_left', 'top_right', 'bottom_left', 'bottom_right', 'tile') DEFAULT 'bottom_right',
    watermark_opacity INT DEFAULT 50 COMMENT '0-100',
    
    -- Redaction settings
    redaction_areas JSON COMMENT 'Array of {x, y, width, height, page}',
    redaction_color VARCHAR(7) DEFAULT '#000000',
    
    -- Resize settings
    max_width INT,
    max_height INT,
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_object (object_id),
    INDEX idx_collection (collection_id),
    INDEX idx_rule_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated derivatives log
CREATE TABLE IF NOT EXISTS rights_derivative_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    digital_object_id INT NOT NULL,
    rule_id INT,
    
    derivative_type VARCHAR(50),
    original_path VARCHAR(500),
    derivative_path VARCHAR(500),
    
    requested_by INT,
    request_purpose VARCHAR(100),
    request_ip VARCHAR(45),
    
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_digital_object (digital_object_id),
    INDEX idx_rule (rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Rights Statements (rightsstatements.org)
INSERT INTO rights_statement (code, uri, category, allows_commercial_use, allows_derivatives, icon, sort_order) VALUES
('InC', 'http://rightsstatements.org/vocab/InC/1.0/', 'in_copyright', 0, 0, 'inc.svg', 1),
('InC-OW-EU', 'http://rightsstatements.org/vocab/InC-OW-EU/1.0/', 'in_copyright', 0, 0, 'inc-ow-eu.svg', 2),
('InC-EDU', 'http://rightsstatements.org/vocab/InC-EDU/1.0/', 'in_copyright', 0, 1, 'inc-edu.svg', 3),
('InC-NC', 'http://rightsstatements.org/vocab/InC-NC/1.0/', 'in_copyright', 0, 1, 'inc-nc.svg', 4),
('InC-RUU', 'http://rightsstatements.org/vocab/InC-RUU/1.0/', 'in_copyright', NULL, NULL, 'inc-ruu.svg', 5),
('NoC-CR', 'http://rightsstatements.org/vocab/NoC-CR/1.0/', 'no_copyright', 1, 1, 'noc-cr.svg', 6),
('NoC-NC', 'http://rightsstatements.org/vocab/NoC-NC/1.0/', 'no_copyright', 0, 1, 'noc-nc.svg', 7),
('NoC-OKLR', 'http://rightsstatements.org/vocab/NoC-OKLR/1.0/', 'no_copyright', 1, 1, 'noc-oklr.svg', 8),
('NoC-US', 'http://rightsstatements.org/vocab/NoC-US/1.0/', 'no_copyright', 1, 1, 'noc-us.svg', 9),
('CNE', 'http://rightsstatements.org/vocab/CNE/1.0/', 'other', NULL, NULL, 'cne.svg', 10),
('UND', 'http://rightsstatements.org/vocab/UND/1.0/', 'other', NULL, NULL, 'und.svg', 11),
('NKC', 'http://rightsstatements.org/vocab/NKC/1.0/', 'other', NULL, NULL, 'nkc.svg', 12);

INSERT INTO rights_statement_i18n (id, culture, name, description, usage_guidelines) VALUES
(1, 'en', 'In Copyright', 'This item is protected by copyright and/or related rights. You are free to use this item in any way that is permitted by the copyright and related rights legislation that applies to your use.', 'Obtain permission from the rights holder before use.'),
(2, 'en', 'In Copyright - EU Orphan Work', 'This item has been identified as an orphan work in the EU. Use is permitted in accordance with the EU Orphan Works Directive.', 'May be used by cultural heritage institutions for specific purposes.'),
(3, 'en', 'In Copyright - Educational Use Permitted', 'This item is protected by copyright but educational use is permitted without obtaining additional permission.', 'Use is limited to non-commercial educational contexts.'),
(4, 'en', 'In Copyright - Non-Commercial Use Permitted', 'This item is protected by copyright but non-commercial use is permitted without obtaining additional permission.', 'Commercial use requires permission from the rights holder.'),
(5, 'en', 'In Copyright - Rights-holder(s) Unlocatable', 'This item is protected but the rights-holder cannot be located after diligent search.', 'Use at your own risk. Document your due diligence.'),
(6, 'en', 'No Copyright - Contractual Restrictions', 'Use of this item is not restricted by copyright but is subject to contractual restrictions.', 'Review the specific contractual terms before use.'),
(7, 'en', 'No Copyright - Non-Commercial Use Only', 'This item is not protected by copyright but use is limited to non-commercial purposes.', 'Commercial use is not permitted under the terms of access.'),
(8, 'en', 'No Copyright - Other Known Legal Restrictions', 'This item is not protected by copyright but has other legal restrictions on use.', 'Research applicable legal restrictions before use.'),
(9, 'en', 'No Copyright - United States', 'This item is not protected by copyright in the United States. It may be protected in other jurisdictions.', 'Check copyright status in your jurisdiction before use.'),
(10, 'en', 'Copyright Not Evaluated', 'The copyright and related rights status of this item has not been evaluated.', 'Assume the item is protected and seek permission.'),
(11, 'en', 'Copyright Undetermined', 'The copyright status of this item could not be determined despite reasonable efforts.', 'Use with caution and document your assessment.'),
(12, 'en', 'No Known Copyright', 'No copyright or related rights are known to exist for this item.', 'This does not guarantee the item is free of rights.');

-- Creative Commons Licenses
INSERT INTO rights_cc_license (code, version, uri, allows_commercial, allows_derivatives, requires_share_alike, requires_attribution, badge_url, sort_order) VALUES
('CC0', '1.0', 'https://creativecommons.org/publicdomain/zero/1.0/', 1, 1, 0, 0, 'https://licensebuttons.net/p/zero/1.0/88x31.png', 1),
('CC-BY', '4.0', 'https://creativecommons.org/licenses/by/4.0/', 1, 1, 0, 1, 'https://licensebuttons.net/l/by/4.0/88x31.png', 2),
('CC-BY-SA', '4.0', 'https://creativecommons.org/licenses/by-sa/4.0/', 1, 1, 1, 1, 'https://licensebuttons.net/l/by-sa/4.0/88x31.png', 3),
('CC-BY-NC', '4.0', 'https://creativecommons.org/licenses/by-nc/4.0/', 0, 1, 0, 1, 'https://licensebuttons.net/l/by-nc/4.0/88x31.png', 4),
('CC-BY-NC-SA', '4.0', 'https://creativecommons.org/licenses/by-nc-sa/4.0/', 0, 1, 1, 1, 'https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png', 5),
('CC-BY-ND', '4.0', 'https://creativecommons.org/licenses/by-nd/4.0/', 1, 0, 0, 1, 'https://licensebuttons.net/l/by-nd/4.0/88x31.png', 6),
('CC-BY-NC-ND', '4.0', 'https://creativecommons.org/licenses/by-nc-nd/4.0/', 0, 0, 0, 1, 'https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png', 7),
('PDM', '1.0', 'https://creativecommons.org/publicdomain/mark/1.0/', 1, 1, 0, 0, 'https://licensebuttons.net/p/mark/1.0/88x31.png', 8);

INSERT INTO rights_cc_license_i18n (id, culture, name, description, human_readable) VALUES
(1, 'en', 'CC0 - Public Domain Dedication', 'The creator has waived all copyright and related rights to the extent possible under law.', 'No Rights Reserved'),
(2, 'en', 'Attribution 4.0 International', 'You may share and adapt for any purpose if you give appropriate credit.', 'Credit must be given to the creator'),
(3, 'en', 'Attribution-ShareAlike 4.0 International', 'You may share and adapt if you credit and share alike.', 'Credit must be given; adaptations must be shared under identical terms'),
(4, 'en', 'Attribution-NonCommercial 4.0 International', 'You may share and adapt for non-commercial purposes if you credit.', 'Only non-commercial use is permitted'),
(5, 'en', 'Attribution-NonCommercial-ShareAlike 4.0 International', 'You may share and adapt for non-commercial purposes if you credit and share alike.', 'Non-commercial only; adaptations must be shared alike'),
(6, 'en', 'Attribution-NoDerivatives 4.0 International', 'You may share if you credit but you may not adapt.', 'Credit must be given; no derivatives permitted'),
(7, 'en', 'Attribution-NonCommercial-NoDerivatives 4.0 International', 'You may share for non-commercial purposes if you credit but you may not adapt.', 'Non-commercial only; no derivatives permitted'),
(8, 'en', 'Public Domain Mark 1.0', 'This work has been identified as being free of known copyright restrictions.', 'No known copyright');

-- Traditional Knowledge Labels
INSERT INTO rights_tk_label (code, category, uri, color, sort_order) VALUES
('TK-A', 'attribution', 'https://localcontexts.org/label/tk-attribution/', '#4A90D9', 1),
('TK-NC', 'tk', 'https://localcontexts.org/label/tk-non-commercial/', '#7B8D42', 2),
('TK-C', 'tk', 'https://localcontexts.org/label/tk-community/', '#D35400', 3),
('TK-CV', 'tk', 'https://localcontexts.org/label/tk-culturally-sensitive/', '#8E44AD', 4),
('TK-SS', 'tk', 'https://localcontexts.org/label/tk-secret-sacred/', '#C0392B', 5),
('TK-MC', 'tk', 'https://localcontexts.org/label/tk-multiple-communities/', '#16A085', 6),
('TK-MR', 'tk', 'https://localcontexts.org/label/tk-men-restricted/', '#2C3E50', 7),
('TK-WR', 'tk', 'https://localcontexts.org/label/tk-women-restricted/', '#E74C3C', 8),
('TK-SR', 'tk', 'https://localcontexts.org/label/tk-seasonal/', '#F39C12', 9),
('TK-F', 'tk', 'https://localcontexts.org/label/tk-family/', '#27AE60', 10),
('TK-O', 'tk', 'https://localcontexts.org/label/tk-outreach/', '#3498DB', 11),
('TK-V', 'tk', 'https://localcontexts.org/label/tk-verified/', '#1ABC9C', 12),
('TK-NV', 'tk', 'https://localcontexts.org/label/tk-non-verified/', '#95A5A6', 13),
('BC-R', 'bc', 'https://localcontexts.org/label/bc-research/', '#9B59B6', 14),
('BC-CB', 'bc', 'https://localcontexts.org/label/bc-consent-before/', '#E67E22', 15),
('BC-P', 'bc', 'https://localcontexts.org/label/bc-provenance/', '#1ABC9C', 16),
('BC-MC', 'bc', 'https://localcontexts.org/label/bc-multiple-communities/', '#3498DB', 17),
('BC-CL', 'bc', 'https://localcontexts.org/label/bc-clan/', '#9B59B6', 18),
('BC-O', 'bc', 'https://localcontexts.org/label/bc-outreach/', '#2ECC71', 19);

INSERT INTO rights_tk_label_i18n (id, culture, name, description, usage_protocol) VALUES
(1, 'en', 'TK Attribution', 'Attribution is required. This label asks users to respect traditional citation practices.', 'Include attribution to the community when using this material.'),
(2, 'en', 'TK Non-Commercial', 'This material should only be used for non-commercial purposes.', 'Do not use this material for commercial gain without community permission.'),
(3, 'en', 'TK Community Voice', 'This material should only be used with the consent of the community.', 'Contact the community before any use.'),
(4, 'en', 'TK Culturally Sensitive', 'This material contains culturally sensitive content.', 'Treat this material with cultural respect and sensitivity.'),
(5, 'en', 'TK Secret/Sacred', 'This material contains secret or sacred content with restricted access.', 'Access is restricted. Do not share without explicit permission.'),
(6, 'en', 'TK Multiple Communities', 'Multiple communities have interests in this material.', 'Consult with all relevant communities before use.'),
(7, 'en', 'TK Men Restricted', 'Access to this material is restricted to men only within the community.', 'Respect gender-specific cultural protocols.'),
(8, 'en', 'TK Women Restricted', 'Access to this material is restricted to women only within the community.', 'Respect gender-specific cultural protocols.'),
(9, 'en', 'TK Seasonal', 'Access to this material may be seasonally or ceremonially restricted.', 'Check with the community about appropriate times for access.'),
(10, 'en', 'TK Family', 'This material belongs to a specific family within the community.', 'Contact the specific family for permissions.'),
(11, 'en', 'TK Outreach', 'The community has designated this material for educational outreach.', 'May be used for educational purposes with attribution.'),
(12, 'en', 'TK Verified', 'Community protocols for this material have been verified.', 'Follow the verified community protocols.'),
(13, 'en', 'TK Non-Verified', 'Community protocols for this material have not yet been verified.', 'Exercise additional caution; protocols may change.'),
(14, 'en', 'BC Research Use', 'This material has been collected with consent for research purposes.', 'Use is limited to approved research activities.'),
(15, 'en', 'BC Consent Before', 'Consent was obtained prior to collection of this material.', 'Original consent terms apply to subsequent use.'),
(16, 'en', 'BC Provenance', 'The provenance and history of this material is documented.', 'Review provenance information before use.'),
(17, 'en', 'BC Multiple Communities', 'Multiple communities contributed to this collection.', 'Acknowledge all contributing communities.'),
(18, 'en', 'BC Clan', 'This material relates to a specific clan within the community.', 'Contact the relevant clan for permissions.'),
(19, 'en', 'BC Outreach', 'This material is designated for educational outreach by the community.', 'May be used for education with community acknowledgment.');

-- =============================================================================
-- VERIFICATION
-- =============================================================================

SELECT 'rights_statement' as table_name, COUNT(*) as count FROM rights_statement
UNION ALL SELECT 'rights_cc_license', COUNT(*) FROM rights_cc_license
UNION ALL SELECT 'rights_tk_label', COUNT(*) FROM rights_tk_label;
