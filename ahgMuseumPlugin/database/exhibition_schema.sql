-- =====================================================
-- Exhibition Module Schema
-- Version: 1.0.0
-- Author: Johan Pieterse <johan@theahg.co.za>
-- =====================================================

-- =====================================================
-- Core Exhibition Tables
-- =====================================================

-- Main exhibition record
CREATE TABLE IF NOT EXISTS exhibition (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Basic Information
    title VARCHAR(500) NOT NULL,
    subtitle VARCHAR(500),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    theme TEXT,

    -- Type and Status
    exhibition_type ENUM('permanent', 'temporary', 'traveling', 'online', 'pop_up') DEFAULT 'temporary',
    status ENUM('concept', 'planning', 'preparation', 'installation', 'open', 'closing', 'closed', 'archived', 'canceled') DEFAULT 'concept',

    -- Dates
    planning_start_date DATE,
    preparation_start_date DATE,
    installation_start_date DATE,
    opening_date DATE,
    closing_date DATE,
    actual_closing_date DATE,

    -- Venue Information
    venue_id BIGINT UNSIGNED,
    venue_name VARCHAR(255),
    venue_address TEXT,
    venue_city VARCHAR(100),
    venue_country VARCHAR(100),
    is_external_venue TINYINT(1) DEFAULT 0,

    -- Gallery/Space
    gallery_ids JSON, -- Array of gallery IDs within venue
    total_square_meters DECIMAL(10,2),

    -- Visitor Information
    admission_fee DECIMAL(10,2),
    admission_currency VARCHAR(3) DEFAULT 'ZAR',
    is_free_admission TINYINT(1) DEFAULT 0,
    expected_visitors INT,
    actual_visitors INT,

    -- Accessibility
    wheelchair_accessible TINYINT(1) DEFAULT 1,
    audio_guide_available TINYINT(1) DEFAULT 0,
    braille_available TINYINT(1) DEFAULT 0,
    sign_language_tours TINYINT(1) DEFAULT 0,

    -- Budget
    budget_amount DECIMAL(12,2),
    budget_currency VARCHAR(3) DEFAULT 'ZAR',
    actual_cost DECIMAL(12,2),

    -- Insurance
    total_insurance_value DECIMAL(15,2),
    insurance_policy_number VARCHAR(100),
    insurance_provider VARCHAR(255),

    -- Catalog/Publication
    has_catalog TINYINT(1) DEFAULT 0,
    catalog_isbn VARCHAR(20),
    catalog_publication_date DATE,

    -- Online/Virtual
    has_virtual_tour TINYINT(1) DEFAULT 0,
    virtual_tour_url VARCHAR(500),
    online_exhibition_url VARCHAR(500),

    -- Credits
    curator_id INT,
    curator_name VARCHAR(255),
    designer_name VARCHAR(255),
    organized_by VARCHAR(255),
    sponsored_by TEXT,

    -- Internal tracking
    project_code VARCHAR(50),
    notes TEXT,
    internal_notes TEXT,

    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_exhibition_status (status),
    INDEX idx_exhibition_type (exhibition_type),
    INDEX idx_exhibition_dates (opening_date, closing_date),
    INDEX idx_exhibition_venue (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exhibition sections/galleries (subdivisions within an exhibition)
CREATE TABLE IF NOT EXISTS exhibition_section (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    narrative TEXT, -- Storyline/interpretive text

    section_type ENUM('gallery', 'room', 'alcove', 'corridor', 'outdoor', 'virtual') DEFAULT 'gallery',
    sequence_order INT DEFAULT 0,

    -- Physical space
    gallery_name VARCHAR(100),
    floor_level VARCHAR(20),
    square_meters DECIMAL(8,2),

    -- Environment
    target_temperature_min DECIMAL(4,1),
    target_temperature_max DECIMAL(4,1),
    target_humidity_min DECIMAL(4,1),
    target_humidity_max DECIMAL(4,1),
    max_lux_level INT,

    -- Theme/narrative
    theme VARCHAR(255),
    color_scheme VARCHAR(100),

    -- Audio/multimedia
    has_audio_guide TINYINT(1) DEFAULT 0,
    audio_guide_number VARCHAR(20),
    has_video TINYINT(1) DEFAULT 0,
    has_interactive TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_section_exhibition (exhibition_id),
    INDEX idx_section_order (exhibition_id, sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objects in exhibition (linking table with placement details)
CREATE TABLE IF NOT EXISTS exhibition_object (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED,
    information_object_id INT NOT NULL,

    -- Display order and position
    sequence_order INT DEFAULT 0,
    display_position VARCHAR(100), -- e.g., "Wall A", "Case 3", "Pedestal 2"

    -- Object status in exhibition
    status ENUM('proposed', 'confirmed', 'on_loan_request', 'installed', 'removed', 'returned') DEFAULT 'proposed',

    -- If external loan required
    requires_loan TINYINT(1) DEFAULT 0,
    loan_id BIGINT UNSIGNED,
    lender_institution VARCHAR(255),

    -- Display requirements
    display_case_required TINYINT(1) DEFAULT 0,
    mount_required TINYINT(1) DEFAULT 0,
    mount_description TEXT,
    special_lighting TINYINT(1) DEFAULT 0,
    lighting_notes TEXT,
    security_level ENUM('standard', 'enhanced', 'maximum') DEFAULT 'standard',

    -- Environment requirements
    climate_controlled TINYINT(1) DEFAULT 0,
    max_lux_level INT,
    uv_filtering_required TINYINT(1) DEFAULT 0,

    -- Rotation (for light-sensitive objects)
    rotation_required TINYINT(1) DEFAULT 0,
    max_display_days INT,
    display_start_date DATE,
    display_end_date DATE,

    -- Condition
    pre_installation_condition_report_id BIGINT UNSIGNED,
    post_exhibition_condition_report_id BIGINT UNSIGNED,

    -- Insurance for this specific object
    insurance_value DECIMAL(15,2),

    -- Label
    label_text TEXT,
    label_credits TEXT,
    extended_label TEXT,
    audio_stop_number VARCHAR(20),

    -- Notes
    installation_notes TEXT,
    handling_notes TEXT,

    installed_by INT,
    installed_at TIMESTAMP NULL,
    removed_by INT,
    removed_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES exhibition_section(id) ON DELETE SET NULL,
    INDEX idx_exobj_exhibition (exhibition_id),
    INDEX idx_exobj_section (section_id),
    INDEX idx_exobj_object (information_object_id),
    INDEX idx_exobj_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Storytelling/Narrative Tables
-- =====================================================

-- Storylines/Narratives that connect objects
CREATE TABLE IF NOT EXISTS exhibition_storyline (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    description TEXT,
    narrative_type ENUM('thematic', 'chronological', 'biographical', 'geographical', 'technique', 'custom') DEFAULT 'thematic',

    -- Content
    introduction TEXT,
    body_text TEXT,
    conclusion TEXT,

    -- Sequence within exhibition
    sequence_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0, -- Main narrative path

    -- Target audience
    target_audience ENUM('general', 'children', 'students', 'specialists', 'all') DEFAULT 'all',
    reading_level ENUM('basic', 'intermediate', 'advanced') DEFAULT 'intermediate',

    -- Duration for audio/tour
    estimated_duration_minutes INT,

    -- Multimedia
    has_audio TINYINT(1) DEFAULT 0,
    audio_file_path VARCHAR(500),
    has_video TINYINT(1) DEFAULT 0,
    video_url VARCHAR(500),

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_storyline_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Storyline stops (objects in a narrative sequence)
CREATE TABLE IF NOT EXISTS exhibition_storyline_stop (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    storyline_id BIGINT UNSIGNED NOT NULL,
    exhibition_object_id BIGINT UNSIGNED NOT NULL,

    sequence_order INT DEFAULT 0,
    stop_number VARCHAR(10),

    -- Interpretive content for this stop
    title VARCHAR(255),
    narrative_text TEXT,
    key_points TEXT, -- JSON array of bullet points
    discussion_questions TEXT,

    -- Connections
    connection_to_next TEXT, -- How this relates to the next stop
    connection_to_theme TEXT,

    -- Multimedia
    audio_transcript TEXT,
    audio_duration_seconds INT,

    -- Timing
    suggested_viewing_minutes INT DEFAULT 2,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (storyline_id) REFERENCES exhibition_storyline(id) ON DELETE CASCADE,
    FOREIGN KEY (exhibition_object_id) REFERENCES exhibition_object(id) ON DELETE CASCADE,
    INDEX idx_stop_storyline (storyline_id),
    INDEX idx_stop_order (storyline_id, sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Installation & Checklist Tables
-- =====================================================

-- Installation checklist templates
CREATE TABLE IF NOT EXISTS exhibition_checklist_template (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    description TEXT,
    checklist_type ENUM('planning', 'preparation', 'installation', 'opening', 'during', 'closing', 'deinstallation') NOT NULL,

    -- Items as JSON array
    items JSON, -- [{name, description, required, category}]

    is_default TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exhibition checklist instances
CREATE TABLE IF NOT EXISTS exhibition_checklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED,

    name VARCHAR(255) NOT NULL,
    checklist_type ENUM('planning', 'preparation', 'installation', 'opening', 'during', 'closing', 'deinstallation') NOT NULL,

    due_date DATE,
    completed_date DATE,
    status ENUM('not_started', 'in_progress', 'completed', 'overdue') DEFAULT 'not_started',

    assigned_to INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_checklist_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual checklist items
CREATE TABLE IF NOT EXISTS exhibition_checklist_item (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    checklist_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),

    is_required TINYINT(1) DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    completed_by INT,

    notes TEXT,
    sequence_order INT DEFAULT 0,

    FOREIGN KEY (checklist_id) REFERENCES exhibition_checklist(id) ON DELETE CASCADE,
    INDEX idx_item_checklist (checklist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Venue & Gallery Tables
-- =====================================================

-- Venues (museums, galleries, external locations)
CREATE TABLE IF NOT EXISTS exhibition_venue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    venue_type ENUM('internal', 'partner', 'external', 'online') DEFAULT 'internal',

    -- Address
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    province_state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'South Africa',

    -- Contact
    contact_name VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    website VARCHAR(500),

    -- Facilities
    total_square_meters DECIMAL(10,2),
    has_climate_control TINYINT(1) DEFAULT 0,
    has_security_system TINYINT(1) DEFAULT 0,
    has_loading_dock TINYINT(1) DEFAULT 0,
    accessibility_rating ENUM('none', 'partial', 'full') DEFAULT 'partial',

    -- Insurance
    has_facility_insurance TINYINT(1) DEFAULT 0,
    insurance_policy_number VARCHAR(100),

    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galleries/Rooms within venues
CREATE TABLE IF NOT EXISTS exhibition_gallery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    gallery_type ENUM('gallery', 'hall', 'room', 'corridor', 'outdoor', 'foyer', 'stairwell') DEFAULT 'gallery',

    floor_level VARCHAR(20),
    square_meters DECIMAL(8,2),
    ceiling_height_meters DECIMAL(4,2),
    wall_linear_meters DECIMAL(8,2),

    -- Environment
    has_climate_control TINYINT(1) DEFAULT 0,
    target_temperature DECIMAL(4,1),
    target_humidity DECIMAL(4,1),
    natural_light TINYINT(1) DEFAULT 0,
    max_lux_level INT,

    -- Capacity
    max_visitors INT,
    max_objects INT,

    -- Features
    has_display_cases TINYINT(1) DEFAULT 0,
    number_of_cases INT DEFAULT 0,
    has_pedestals TINYINT(1) DEFAULT 0,
    number_of_pedestals INT DEFAULT 0,
    has_track_lighting TINYINT(1) DEFAULT 0,
    has_av_equipment TINYINT(1) DEFAULT 0,

    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (venue_id) REFERENCES exhibition_venue(id) ON DELETE CASCADE,
    INDEX idx_gallery_venue (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Events & Programs
-- =====================================================

-- Exhibition-related events (openings, tours, lectures)
CREATE TABLE IF NOT EXISTS exhibition_event (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    event_type ENUM('opening', 'closing', 'tour', 'lecture', 'workshop', 'performance', 'family', 'school', 'vip', 'press', 'other') NOT NULL,
    description TEXT,

    -- Schedule
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    is_recurring TINYINT(1) DEFAULT 0,
    recurrence_pattern VARCHAR(100), -- e.g., "every Saturday"

    -- Location
    venue_id BIGINT UNSIGNED,
    gallery_id BIGINT UNSIGNED,
    location_notes VARCHAR(255),

    -- Capacity
    max_attendees INT,
    registered_attendees INT DEFAULT 0,
    actual_attendees INT,

    -- Registration
    requires_registration TINYINT(1) DEFAULT 0,
    registration_url VARCHAR(500),
    registration_deadline DATETIME,

    -- Cost
    is_free TINYINT(1) DEFAULT 1,
    ticket_price DECIMAL(10,2),
    ticket_currency VARCHAR(3) DEFAULT 'ZAR',

    -- Presenter
    presenter_name VARCHAR(255),
    presenter_bio TEXT,

    -- Status
    status ENUM('scheduled', 'confirmed', 'canceled', 'completed') DEFAULT 'scheduled',

    notes TEXT,

    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_event_exhibition (exhibition_id),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Media & Documentation
-- =====================================================

-- Exhibition images and media
CREATE TABLE IF NOT EXISTS exhibition_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED,

    media_type ENUM('image', 'video', 'audio', 'document', 'floorplan', 'poster', 'press') NOT NULL,
    usage_type ENUM('promotional', 'installation', 'documentation', 'press', 'catalog', 'internal') DEFAULT 'documentation',

    file_path VARCHAR(500),
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    file_size BIGINT,

    title VARCHAR(255),
    caption TEXT,
    credits VARCHAR(500),

    is_primary TINYINT(1) DEFAULT 0, -- Main promotional image
    is_public TINYINT(1) DEFAULT 1,

    sequence_order INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_media_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Default Checklist Templates
-- =====================================================

INSERT INTO exhibition_checklist_template (name, description, checklist_type, items, is_default) VALUES
('Standard Planning Checklist', 'Basic planning checklist for exhibitions', 'planning',
 '[{"name":"Define exhibition concept","description":"Establish theme, narrative, and goals","required":true,"category":"Concept"},
   {"name":"Identify target audience","description":"Define primary and secondary audiences","required":true,"category":"Concept"},
   {"name":"Set budget","description":"Establish preliminary budget","required":true,"category":"Budget"},
   {"name":"Set timeline","description":"Create project timeline with milestones","required":true,"category":"Planning"},
   {"name":"Identify potential objects","description":"Create preliminary object list","required":true,"category":"Objects"},
   {"name":"Assess loan requirements","description":"Identify objects requiring loans","required":false,"category":"Objects"},
   {"name":"Select venue/galleries","description":"Confirm exhibition spaces","required":true,"category":"Venue"},
   {"name":"Assign project team","description":"Identify curator, designer, registrar","required":true,"category":"Team"}]',
 1),

('Standard Installation Checklist', 'Basic installation checklist', 'installation',
 '[{"name":"Confirm object locations","description":"Finalize placement plan","required":true,"category":"Layout"},
   {"name":"Prepare mounts and cases","description":"All display furniture ready","required":true,"category":"Display"},
   {"name":"Check environmental conditions","description":"Verify temperature and humidity","required":true,"category":"Environment"},
   {"name":"Install lighting","description":"Set up and focus lights","required":true,"category":"Lighting"},
   {"name":"Install objects","description":"Place all objects per plan","required":true,"category":"Objects"},
   {"name":"Complete condition reports","description":"Document pre-installation condition","required":true,"category":"Documentation"},
   {"name":"Install labels","description":"Place all object labels","required":true,"category":"Labels"},
   {"name":"Install graphics","description":"Place wall text and panels","required":true,"category":"Graphics"},
   {"name":"Test AV equipment","description":"Verify all multimedia works","required":false,"category":"AV"},
   {"name":"Security check","description":"Verify all security measures","required":true,"category":"Security"},
   {"name":"Final walkthrough","description":"Complete review before opening","required":true,"category":"Review"}]',
 1),

('Standard Closing Checklist', 'Basic deinstallation checklist', 'closing',
 '[{"name":"Post-exhibition condition reports","description":"Document condition of all objects","required":true,"category":"Documentation"},
   {"name":"Photography","description":"Final installation photography","required":false,"category":"Documentation"},
   {"name":"Deinstall objects","description":"Carefully remove all objects","required":true,"category":"Objects"},
   {"name":"Pack objects","description":"Pack for storage or return","required":true,"category":"Objects"},
   {"name":"Return loans","description":"Arrange return of borrowed objects","required":true,"category":"Loans"},
   {"name":"Remove graphics","description":"Take down all text panels","required":true,"category":"Graphics"},
   {"name":"Remove labels","description":"Collect all object labels","required":true,"category":"Labels"},
   {"name":"Archive materials","description":"File all exhibition documentation","required":true,"category":"Archive"},
   {"name":"Visitor statistics","description":"Compile final visitor numbers","required":true,"category":"Statistics"},
   {"name":"Budget reconciliation","description":"Final budget accounting","required":true,"category":"Budget"},
   {"name":"Team debrief","description":"Post-exhibition review meeting","required":false,"category":"Review"}]',
 1);

-- =====================================================
-- Workflow History for Exhibitions
-- =====================================================

-- Exhibition status history (uses existing workflow tables if available)
CREATE TABLE IF NOT EXISTS exhibition_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exhibition_id BIGINT UNSIGNED NOT NULL,

    from_status VARCHAR(50),
    to_status VARCHAR(50) NOT NULL,

    changed_by INT,
    change_reason TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE,
    INDEX idx_history_exhibition (exhibition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
