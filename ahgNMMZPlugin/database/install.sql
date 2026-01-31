-- ahgNMMZPlugin Database Schema
-- National Museums and Monuments of Zimbabwe Act [Chapter 25:11]
--
-- Key provisions:
-- - National monuments registration and protection
-- - Antiquities (objects > 100 years old) management
-- - Export permit requirements
-- - Archaeological site protection
-- - Heritage impact assessments

-- Configuration
CREATE TABLE IF NOT EXISTS nmmz_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default configuration
INSERT INTO nmmz_config (config_key, config_value, description) VALUES
('antiquity_age_years', '100', 'Age threshold for antiquity classification (years)'),
('export_permit_fee_usd', '50', 'Standard export permit fee (US$)'),
('export_permit_validity_days', '90', 'Export permit validity period (days)'),
('hia_required_threshold', '1000', 'Project value threshold requiring HIA (US$)'),
('nmmz_contact_email', 'info@nmmz.co.zw', 'NMMZ contact email'),
('nmmz_contact_phone', '+263 242 752797', 'NMMZ contact phone'),
('director_name', '', 'Executive Director name')
ON DUPLICATE KEY UPDATE config_key = config_key;

-- Monument categories
CREATE TABLE IF NOT EXISTS nmmz_monument_category (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    protection_level ENUM('national', 'provincial', 'local') DEFAULT 'national',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default categories
INSERT INTO nmmz_monument_category (code, name, description, protection_level) VALUES
('ARCH', 'Archaeological Sites', 'Prehistoric and historic archaeological sites', 'national'),
('HIST', 'Historical Buildings', 'Buildings of historical significance', 'national'),
('NATL', 'Natural Heritage', 'Natural formations of heritage significance', 'national'),
('CULT', 'Cultural Landscapes', 'Areas of cultural significance', 'national'),
('ROCK', 'Rock Art Sites', 'Sites with San/Bushman rock paintings', 'national'),
('RUIN', 'Ruins', 'Ancient ruins and settlements (e.g., Great Zimbabwe)', 'national'),
('MEM', 'Memorials', 'War memorials and commemorative sites', 'provincial'),
('GRAVE', 'Burial Sites', 'Historic cemeteries and burial grounds', 'provincial')
ON DUPLICATE KEY UPDATE code = code;

-- National Monuments register
CREATE TABLE IF NOT EXISTS nmmz_monument (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    monument_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Official NMMZ registration number',
    information_object_id INT COMMENT 'Link to AtoM record',
    category_id BIGINT UNSIGNED,

    name VARCHAR(500) NOT NULL,
    description TEXT,
    historical_significance TEXT,

    -- Location
    province VARCHAR(100),
    district VARCHAR(100),
    location_description TEXT,
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    area_hectares DECIMAL(12, 4),

    -- Legal status
    gazette_date DATE COMMENT 'Date gazetted as national monument',
    gazette_reference VARCHAR(100),
    protection_level ENUM('national', 'provincial', 'local') DEFAULT 'national',
    legal_status ENUM('gazetted', 'provisional', 'proposed', 'delisted') DEFAULT 'proposed',

    -- Ownership/Management
    ownership_type ENUM('state', 'private', 'communal', 'mixed') DEFAULT 'state',
    owner_name VARCHAR(255),
    custodian VARCHAR(255),
    management_authority VARCHAR(255),

    -- Condition
    condition_rating ENUM('excellent', 'good', 'fair', 'poor', 'critical', 'destroyed') DEFAULT 'good',
    last_inspection_date DATE,
    conservation_priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    threats TEXT,

    -- World Heritage
    world_heritage_status ENUM('inscribed', 'tentative', 'none') DEFAULT 'none',
    world_heritage_year INT,
    world_heritage_criteria VARCHAR(100),

    status ENUM('active', 'at_risk', 'destroyed', 'delisted') DEFAULT 'active',
    notes TEXT,

    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category_id),
    INDEX idx_province (province),
    INDEX idx_status (status),
    INDEX idx_legal_status (legal_status),
    FOREIGN KEY (category_id) REFERENCES nmmz_monument_category(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Antiquities register (objects > 100 years old)
CREATE TABLE IF NOT EXISTS nmmz_antiquity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    information_object_id INT COMMENT 'Link to AtoM record',

    name VARCHAR(500) NOT NULL,
    description TEXT,
    object_type VARCHAR(100),
    material VARCHAR(255),

    -- Dating
    estimated_age_years INT,
    date_earliest DATE,
    date_latest DATE,
    dating_method VARCHAR(100),

    -- Provenance
    provenance TEXT,
    find_location VARCHAR(255),
    find_date DATE,
    excavation_reference VARCHAR(100),

    -- Physical
    dimensions VARCHAR(255),
    weight_kg DECIMAL(10, 4),
    condition_rating ENUM('excellent', 'good', 'fair', 'poor', 'fragmentary') DEFAULT 'good',

    -- Current location
    current_location VARCHAR(255),
    repository_id INT COMMENT 'Link to AtoM repository',
    storage_reference VARCHAR(100),

    -- Legal status
    ownership_type ENUM('state', 'museum', 'private', 'unknown') DEFAULT 'state',
    owner_name VARCHAR(255),
    acquisition_method ENUM('excavation', 'donation', 'purchase', 'confiscation', 'transfer', 'unknown') DEFAULT 'unknown',
    acquisition_date DATE,

    -- Export status
    export_restricted TINYINT(1) DEFAULT 1 COMMENT 'Subject to export restrictions',
    export_history TEXT,

    -- Value (for insurance/customs)
    estimated_value DECIMAL(15, 2),
    value_currency VARCHAR(3) DEFAULT 'USD',

    status ENUM('in_collection', 'on_loan', 'missing', 'repatriated', 'destroyed') DEFAULT 'in_collection',
    notes TEXT,

    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_object_type (object_type),
    INDEX idx_status (status),
    INDEX idx_repository (repository_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Export permits
CREATE TABLE IF NOT EXISTS nmmz_export_permit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_number VARCHAR(50) NOT NULL UNIQUE,

    -- Applicant
    applicant_name VARCHAR(255) NOT NULL,
    applicant_address TEXT,
    applicant_email VARCHAR(255),
    applicant_phone VARCHAR(50),
    applicant_type ENUM('individual', 'institution', 'dealer', 'researcher') NOT NULL,

    -- Object details
    antiquity_id BIGINT UNSIGNED COMMENT 'Link to antiquity if registered',
    object_description TEXT NOT NULL,
    quantity INT DEFAULT 1,
    estimated_value DECIMAL(15, 2),
    value_currency VARCHAR(3) DEFAULT 'USD',

    -- Purpose
    export_purpose ENUM('exhibition', 'research', 'conservation', 'sale', 'personal', 'return') NOT NULL,
    purpose_details TEXT,

    -- Destination
    destination_country VARCHAR(100) NOT NULL,
    destination_institution VARCHAR(255),
    destination_address TEXT,

    -- Dates
    application_date DATE NOT NULL,
    export_date_proposed DATE,
    return_date DATE COMMENT 'For temporary exports',
    validity_end DATE,

    -- Approval
    reviewed_by INT,
    review_date DATE,
    approval_conditions TEXT,

    -- Fees
    fee_amount DECIMAL(10, 2),
    fee_currency VARCHAR(3) DEFAULT 'USD',
    fee_paid TINYINT(1) DEFAULT 0,
    fee_receipt VARCHAR(100),

    status ENUM('pending', 'approved', 'rejected', 'issued', 'used', 'expired', 'cancelled') DEFAULT 'pending',
    rejection_reason TEXT,

    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_applicant (applicant_name),
    INDEX idx_status (status),
    INDEX idx_validity (validity_end),
    FOREIGN KEY (antiquity_id) REFERENCES nmmz_antiquity(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Archaeological sites
CREATE TABLE IF NOT EXISTS nmmz_archaeological_site (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_number VARCHAR(50) NOT NULL UNIQUE,
    information_object_id INT,
    monument_id BIGINT UNSIGNED COMMENT 'Link to monument if gazetted',

    name VARCHAR(500) NOT NULL,
    site_type VARCHAR(100) COMMENT 'Stone Age, Iron Age, Historical, etc.',
    description TEXT,

    -- Location
    province VARCHAR(100),
    district VARCHAR(100),
    location_description TEXT,
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    area_sqm DECIMAL(12, 2),

    -- Dating
    period VARCHAR(100),
    date_earliest VARCHAR(50),
    date_latest VARCHAR(50),

    -- Discovery
    discovery_date DATE,
    discovered_by VARCHAR(255),

    -- Excavation
    excavated TINYINT(1) DEFAULT 0,
    excavation_years VARCHAR(100),
    excavator VARCHAR(255),
    excavation_institution VARCHAR(255),

    -- Protection
    protection_status ENUM('protected', 'unprotected', 'at_risk', 'destroyed') DEFAULT 'unprotected',
    threats TEXT,
    fencing TINYINT(1) DEFAULT 0,
    signage TINYINT(1) DEFAULT 0,

    -- Research
    research_potential ENUM('high', 'medium', 'low', 'exhausted') DEFAULT 'medium',
    publications TEXT,

    status ENUM('active', 'destroyed', 'submerged', 'built_over') DEFAULT 'active',
    notes TEXT,

    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_province (province),
    INDEX idx_site_type (site_type),
    INDEX idx_protection (protection_status),
    FOREIGN KEY (monument_id) REFERENCES nmmz_monument(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Heritage Impact Assessments
CREATE TABLE IF NOT EXISTS nmmz_heritage_impact_assessment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50) NOT NULL UNIQUE,

    -- Project details
    project_name VARCHAR(500) NOT NULL,
    project_type VARCHAR(100) COMMENT 'Construction, Mining, Infrastructure, etc.',
    project_description TEXT,
    project_location TEXT,
    province VARCHAR(100),
    district VARCHAR(100),

    -- Developer/Applicant
    developer_name VARCHAR(255) NOT NULL,
    developer_contact VARCHAR(255),
    developer_email VARCHAR(255),

    -- Assessment
    assessor_name VARCHAR(255),
    assessor_qualification VARCHAR(255),
    assessment_date DATE,

    -- Affected heritage
    monuments_affected JSON COMMENT 'Array of monument IDs',
    sites_affected JSON COMMENT 'Array of site IDs',
    heritage_resources_found TEXT,

    -- Impact
    impact_level ENUM('none', 'low', 'moderate', 'high', 'severe') DEFAULT 'moderate',
    impact_description TEXT,

    -- Mitigation
    mitigation_measures TEXT,
    monitoring_plan TEXT,

    -- Recommendation
    recommendation ENUM('approve', 'approve_with_conditions', 'reject', 'further_study') DEFAULT 'further_study',
    conditions TEXT,

    -- Status
    status ENUM('submitted', 'under_review', 'approved', 'rejected', 'expired') DEFAULT 'submitted',
    decision_date DATE,
    decision_by INT,

    validity_period_months INT DEFAULT 24,

    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_province (province),
    INDEX idx_status (status),
    INDEX idx_impact (impact_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monument inspections
CREATE TABLE IF NOT EXISTS nmmz_monument_inspection (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    monument_id BIGINT UNSIGNED NOT NULL,
    inspection_date DATE NOT NULL,
    inspector_name VARCHAR(255) NOT NULL,

    condition_rating ENUM('excellent', 'good', 'fair', 'poor', 'critical', 'destroyed') NOT NULL,
    previous_rating ENUM('excellent', 'good', 'fair', 'poor', 'critical', 'destroyed'),

    structural_condition TEXT,
    vegetation_encroachment TINYINT(1) DEFAULT 0,
    vandalism_observed TINYINT(1) DEFAULT 0,
    erosion_observed TINYINT(1) DEFAULT 0,
    other_damage TEXT,

    visitor_facilities_condition TEXT,
    signage_condition TEXT,
    boundary_condition TEXT,

    recommendations TEXT,
    urgent_action_required TINYINT(1) DEFAULT 0,
    follow_up_date DATE,

    photos_taken TINYINT(1) DEFAULT 0,
    photo_references TEXT,

    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_monument (monument_id),
    INDEX idx_date (inspection_date),
    INDEX idx_condition (condition_rating),
    FOREIGN KEY (monument_id) REFERENCES nmmz_monument(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log
CREATE TABLE IF NOT EXISTS nmmz_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    old_value JSON,
    new_value JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
