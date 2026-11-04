-- ahgAccessionManagePlugin: Accession V2 Tables
-- Issue #200: First-Class Accessions (M1 + M2 + M3)

-- =============================================================================
-- M1: INTAKE QUEUE (5 tables)
-- =============================================================================

-- 1. accession_v2: 1:1 extension of base accession table
CREATE TABLE IF NOT EXISTS accession_v2 (
    accession_id INT NOT NULL PRIMARY KEY,
    status VARCHAR(20) NOT NULL DEFAULT 'draft'
        COMMENT 'draft, submitted, under_review, accepted, rejected, returned',
    priority VARCHAR(10) NOT NULL DEFAULT 'normal'
        COMMENT 'low, normal, high, urgent',
    assigned_to INT UNSIGNED NULL,
    submitted_at DATETIME NULL,
    accepted_at DATETIME NULL,
    rejected_at DATETIME NULL,
    rejection_reason TEXT NULL,
    intake_notes TEXT NULL,
    donor_agreement_id INT UNSIGNED NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_tenant (tenant_id)
);

-- 2. accession_intake_checklist: configurable checklist items per accession
CREATE TABLE IF NOT EXISTS accession_intake_checklist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_by INT UNSIGNED NULL,
    completed_at DATETIME NULL,
    sort_order INT NOT NULL DEFAULT 0,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. accession_intake_template: reusable checklist templates
CREATE TABLE IF NOT EXISTS accession_intake_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    items JSON NOT NULL COMMENT 'Array of {label, sort_order}',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. accession_timeline: event log for chain-of-custody
CREATE TABLE IF NOT EXISTS accession_timeline (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    event_type VARCHAR(30) NOT NULL
        COMMENT 'created, submitted, assigned, reviewed, accepted, rejected, returned, appraised, containerized, rights_assigned, deaccessioned, note',
    actor_id INT UNSIGNED NULL,
    description TEXT NULL,
    metadata JSON NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accession (accession_id),
    INDEX idx_tenant (tenant_id)
);

-- 5. accession_attachment: file attachments (deed of gift, photos, etc.)
CREATE TABLE IF NOT EXISTS accession_attachment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    category VARCHAR(30) NOT NULL DEFAULT 'general'
        COMMENT 'general, deed_of_gift, photo, correspondence, inventory, other',
    description TEXT NULL,
    uploaded_by INT UNSIGNED NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accession (accession_id)
);

-- =============================================================================
-- M2: APPRAISAL & VALUATION (4 tables)
-- =============================================================================

-- 6. accession_appraisal: formal appraisal record
CREATE TABLE IF NOT EXISTS accession_appraisal (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    appraiser_id INT UNSIGNED NULL,
    appraisal_type VARCHAR(20) NOT NULL DEFAULT 'archival'
        COMMENT 'archival, monetary, insurance, historical, research',
    monetary_value DECIMAL(15,2) NULL,
    currency VARCHAR(3) NULL DEFAULT 'ZAR',
    significance VARCHAR(20) NULL
        COMMENT 'low, medium, high, exceptional, national_significance',
    recommendation VARCHAR(20) NOT NULL DEFAULT 'pending'
        COMMENT 'pending, accept, reject, partial, defer',
    summary TEXT NULL,
    detailed_notes TEXT NULL,
    appraised_at DATETIME NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_accession (accession_id)
);

-- 7. accession_appraisal_criterion: scoring criteria per appraisal
CREATE TABLE IF NOT EXISTS accession_appraisal_criterion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT UNSIGNED NOT NULL,
    criterion_name VARCHAR(100) NOT NULL,
    score INT NULL COMMENT '1-5 scale',
    weight DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    notes TEXT NULL
);

-- 8. accession_appraisal_template: reusable appraisal criteria templates
CREATE TABLE IF NOT EXISTS accession_appraisal_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    criteria JSON NOT NULL COMMENT 'Array of {criterion_name, weight, description}',
    sector VARCHAR(20) NULL COMMENT 'archive, library, museum, gallery, dam',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. accession_valuation_history: track value changes over time (GRAP 103/IPSAS 45)
CREATE TABLE IF NOT EXISTS accession_valuation_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    valuation_type VARCHAR(20) NOT NULL
        COMMENT 'initial, revaluation, impairment, disposal',
    monetary_value DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'ZAR',
    valuation_date DATE NOT NULL,
    valuer VARCHAR(255) NULL,
    method VARCHAR(50) NULL
        COMMENT 'cost, market, income, replacement, nominal',
    reference_document VARCHAR(255) NULL,
    notes TEXT NULL,
    recorded_by INT UNSIGNED NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accession (accession_id)
);

-- =============================================================================
-- M3: CONTAINERS & RIGHTS (6 tables)
-- =============================================================================

-- 10. accession_container: physical container tracking
CREATE TABLE IF NOT EXISTS accession_container (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    container_type VARCHAR(30) NOT NULL DEFAULT 'box'
        COMMENT 'box, folder, envelope, crate, tube, flat_file, digital_media, other',
    label VARCHAR(255) NOT NULL,
    barcode VARCHAR(100) NULL,
    location_id INT UNSIGNED NULL,
    location_detail VARCHAR(255) NULL,
    dimensions VARCHAR(100) NULL,
    item_count INT UNSIGNED NULL,
    weight_kg DECIMAL(8,2) NULL,
    condition_status VARCHAR(20) NULL
        COMMENT 'excellent, good, fair, poor, critical',
    notes TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_accession (accession_id),
    INDEX idx_barcode (barcode),
    INDEX idx_location (location_id)
);

-- 11. accession_container_item: items within containers
CREATE TABLE IF NOT EXISTS accession_container_item (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    container_id INT UNSIGNED NOT NULL,
    information_object_id INT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    format VARCHAR(50) NULL,
    date_range VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 12. accession_rights: rights attached to accession (pre-arrangement)
CREATE TABLE IF NOT EXISTS accession_rights (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    rights_basis VARCHAR(30) NOT NULL
        COMMENT 'copyright, license, statute, policy, donor, other',
    rights_holder VARCHAR(255) NULL,
    rights_holder_id INT UNSIGNED NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    restriction_type VARCHAR(20) NOT NULL DEFAULT 'none'
        COMMENT 'none, restricted, conditional, closed, partial',
    conditions TEXT NULL,
    grant_act VARCHAR(30) NULL
        COMMENT 'publish, disseminate, modify, migrate, replicate, delete, discover',
    grant_restriction VARCHAR(20) NULL
        COMMENT 'allow, disallow, conditional',
    notes TEXT NULL,
    inherit_to_children TINYINT(1) NOT NULL DEFAULT 1,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_accession (accession_id)
);

-- 13. accession_rights_inherited: tracks rights pushed to child IOs
CREATE TABLE IF NOT EXISTS accession_rights_inherited (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rights_id INT UNSIGNED NOT NULL,
    information_object_id INT NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    applied_by INT UNSIGNED NULL,
    tenant_id INT UNSIGNED NULL,
    INDEX idx_rights (rights_id),
    INDEX idx_io (information_object_id)
);

-- 14. accession_numbering_sequence: per-repo accession number sequences
CREATE TABLE IF NOT EXISTS accession_numbering_sequence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id INT UNSIGNED NULL,
    mask VARCHAR(255) NOT NULL DEFAULT '{YEAR}-{SEQ:5}'
        COMMENT 'Token pattern: {YEAR}, {MONTH}, {DAY}, {REPO}, {SEQ:n}',
    last_sequence INT UNSIGNED NOT NULL DEFAULT 0,
    last_year INT UNSIGNED NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_repo_tenant (repository_id, tenant_id)
);

-- 15. accession_config: per-tenant accession settings
CREATE TABLE IF NOT EXISTS accession_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT NULL,
    tenant_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_key_tenant (config_key, tenant_id)
);

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Default intake checklist template
INSERT IGNORE INTO accession_intake_template (name, description, items, is_default) VALUES
('Standard Intake', 'Default checklist for archival accessions', '[
    {"label": "Donor agreement signed", "sort_order": 1},
    {"label": "Preliminary inventory completed", "sort_order": 2},
    {"label": "Physical condition assessed", "sort_order": 3},
    {"label": "Restrictions identified", "sort_order": 4},
    {"label": "Storage location assigned", "sort_order": 5},
    {"label": "Accession number assigned", "sort_order": 6}
]', 1);

-- Default appraisal template (archival)
INSERT IGNORE INTO accession_appraisal_template (name, description, criteria, sector, is_default) VALUES
('Archival Appraisal', 'Standard archival appraisal criteria', '[
    {"criterion_name": "Evidential value", "weight": 1.0, "description": "Value as evidence of organization functions and activities"},
    {"criterion_name": "Informational value", "weight": 1.0, "description": "Value of information content about persons, places, subjects"},
    {"criterion_name": "Intrinsic value", "weight": 0.8, "description": "Inherent worth based on age, uniqueness, physical form"},
    {"criterion_name": "Research potential", "weight": 0.8, "description": "Anticipated use by researchers"},
    {"criterion_name": "Institutional relevance", "weight": 1.0, "description": "Alignment with collection development policy"},
    {"criterion_name": "Physical condition", "weight": 0.6, "description": "State of preservation and conservation needs"},
    {"criterion_name": "Completeness", "weight": 0.7, "description": "Degree to which records are complete and comprehensive"}
]', 'archive', 1);

-- Default accession config
INSERT IGNORE INTO accession_config (config_key, config_value) VALUES
('numbering_mask', '{YEAR}-{SEQ:5}'),
('auto_assign_enabled', '0'),
('require_donor_agreement', '0'),
('require_appraisal', '0'),
('default_priority', 'normal'),
('intake_checklist_template_id', '1'),
('appraisal_template_id', '1'),
('allow_container_barcodes', '1'),
('rights_inheritance_enabled', '1');

-- =============================================================================
-- CAAIS 1.0 profile (#199, #203, #217): side tables and dropdown vocabularies.
-- The same as testing/fixtures/install_caais.sql and caais_seed_dropdowns.sql
-- (copies of Heratio's database/install_caais.sql and seed); keep all three
-- in step. Without these an installed site had no accession_caais, so an
-- accession could not name its repository. The profile stays off until
-- ahg_settings accession_caais_enabled is switched on.
-- =============================================================================

-- ============================================================================
-- ahg-accession-manage - CAAIS profile schema (heratio#1514)
-- ============================================================================
-- Canadian Archival Accession Information Standard (CAAIS) Final 1.0, Canadian
-- Council of Archives, 15 May 2019. A pluggable profile over the core
-- accession model: every table here is a side table keyed on accession.id, so
-- the core `accession` / `accession_i18n` tables are not altered and an
-- institution that never enables the profile carries nothing but empty tables.
--
-- Enumerated values (extent type, units, content and carrier type, source
-- confidentiality, language, preservation requirement type, event type,
-- revision type) are codes from ahg_dropdown - see seed_dropdowns.sql. They are
-- VARCHAR, never ENUM, so an institution extends them in the Dropdown Manager.
--
-- Swept by heratio:install-bootstrap (install*.sql) and self-installed on
-- first boot by AhgAccessionManageServiceProvider. Safe to re-run.
-- ============================================================================

-- 1:1 - elements with one value per accession.
--   1.1 Repository (also the 2020 "accession to repository" request: a
--       multi-repository instance can now say which repository accepted it)
--   7.1 Rules or conventions
CREATE TABLE IF NOT EXISTS accession_caais (
    accession_id INT NOT NULL PRIMARY KEY,
    repository_id INT NULL COMMENT 'CAAIS 1.1 - repository.id that accepts legal responsibility',
    rules_or_conventions VARCHAR(1024) NULL COMMENT 'CAAIS 7.1',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_accession_caais_repository (repository_id),
    CONSTRAINT fk_accession_caais_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE,
    CONSTRAINT fk_accession_caais_repository FOREIGN KEY (repository_id) REFERENCES repository (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1.6 Source confidentiality, per source (donor) linked to the accession.
-- The source itself stays in the core donor relation; this only records the
-- handling instruction for that source on this accession.
CREATE TABLE IF NOT EXISTS accession_caais_source (
    accession_id INT NOT NULL,
    actor_id INT NOT NULL COMMENT 'donor / source actor.id',
    confidentiality VARCHAR(100) NULL COMMENT 'CAAIS 2.1.6 - ahg_dropdown caais_source_confidentiality',
    PRIMARY KEY (accession_id, actor_id),
    CONSTRAINT fk_accession_caais_source_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE,
    CONSTRAINT fk_accession_caais_source_actor FOREIGN KEY (actor_id) REFERENCES actor (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.2 Extent statement (repeatable): 3.2.1 type, 3.2.2 quantity and unit,
-- 3.2.3 content type, 3.2.4 carrier type, digital file formats, extent note.
-- The free-text accession_i18n.received_extent_units is left alone and is
-- exported as an extent note when no structured statement exists.
CREATE TABLE IF NOT EXISTS accession_caais_extent (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    extent_type VARCHAR(100) NOT NULL COMMENT 'CAAIS 3.2.1 - caais_extent_type',
    quantity DECIMAL(14,3) NULL COMMENT 'CAAIS 3.2.2',
    is_estimate TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'CAAIS 3.2.2 "ca." convention',
    unit VARCHAR(100) NULL COMMENT 'CAAIS 3.2.2 - caais_extent_unit',
    content_type VARCHAR(100) NULL COMMENT 'CAAIS 3.2.3 - caais_content_type',
    carrier_type VARCHAR(100) NULL COMMENT 'CAAIS 3.2.4 - caais_carrier_type',
    digital_file_formats VARCHAR(1024) NULL COMMENT 'CAAIS 3.2.5 digital file formats',
    note TEXT NULL COMMENT 'CAAIS extent note',
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_accession_caais_extent_accession (accession_id),
    CONSTRAINT fk_accession_caais_extent_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.4 Language of material (repeatable). A code from caais_language plus an
-- optional statement for what a code cannot say ("In Dakota, with partial
-- English translation").
CREATE TABLE IF NOT EXISTS accession_caais_language (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    language VARCHAR(100) NULL COMMENT 'CAAIS 3.4 - caais_language',
    note VARCHAR(1024) NULL COMMENT 'CAAIS 3.4 free statement',
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_accession_caais_language_accession (accession_id),
    CONSTRAINT fk_accession_caais_language_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.3 Preservation requirements (repeatable): 4.3.1 type, 4.3.2 value,
-- 4.3.3 note. accession_i18n.physical_characteristics stays as the core
-- free-text field and is exported as a fallback when nothing is structured.
CREATE TABLE IF NOT EXISTS accession_caais_preservation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    requirement_type VARCHAR(100) NOT NULL COMMENT 'CAAIS 4.3.1 - caais_preservation_type',
    requirement_value TEXT NOT NULL COMMENT 'CAAIS 4.3.2',
    note TEXT NULL COMMENT 'CAAIS 4.3.3',
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_accession_caais_preservation_accession (accession_id),
    CONSTRAINT fk_accession_caais_preservation_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.1 Events (repeatable): 5.1.1 type, 5.1.2 date, 5.1.3 agent, 5.1.4 note.
-- CAAIS requires at least the physical transfer and (if different) the legal
-- transfer. Core accession_event rows are exported alongside these.
CREATE TABLE IF NOT EXISTS accession_caais_event (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL COMMENT 'CAAIS 5.1.1 - caais_event_type',
    event_date DATE NULL COMMENT 'CAAIS 5.1.2',
    agent VARCHAR(255) NULL COMMENT 'CAAIS 5.1.3',
    note TEXT NULL COMMENT 'CAAIS 5.1.4',
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_accession_caais_event_accession (accession_id),
    INDEX idx_accession_caais_event_type (event_type),
    CONSTRAINT fk_accession_caais_event_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.2 Date of creation or revision (repeatable, append-only): 7.2.1 type,
-- 7.2.2 date, 7.2.3 agent, 7.2.4 note. Written by the application on every
-- create and save. user_id has no foreign key and the agent name is a
-- snapshot, so the record outlives a renamed or deleted account.
CREATE TABLE IF NOT EXISTS accession_caais_revision (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accession_id INT NOT NULL,
    revision_type VARCHAR(100) NOT NULL COMMENT 'CAAIS 7.2.1 - caais_revision_type',
    revision_date DATETIME NOT NULL COMMENT 'CAAIS 7.2.2',
    agent VARCHAR(255) NULL COMMENT 'CAAIS 7.2.3 - name snapshot',
    user_id INT NULL,
    note TEXT NULL COMMENT 'CAAIS 7.2.4',
    INDEX idx_accession_caais_revision_accession (accession_id, revision_date),
    CONSTRAINT fk_accession_caais_revision_accession FOREIGN KEY (accession_id) REFERENCES accession (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ahg-accession-manage dropdown seed: CAAIS profile vocabularies (heratio#1514).
-- CAAIS asks each repository to maintain its own controlled vocabulary for
-- these elements; the values below are the examples CAAIS 1.0 gives, managed
-- in the Dropdown Manager (/admin/dropdowns) so an institution adds, renames or
-- retires terms without a code change. INSERT IGNORE on (taxonomy, code).
INSERT IGNORE INTO `ahg_dropdown`
  (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
  -- 3.2.1 Extent type
  ('caais_extent_type', 'CAAIS Extent Type', 'accession', 'extent_received', 'Extent received', 10, 1, 1, NOW(), NOW()),
  ('caais_extent_type', 'CAAIS Extent Type', 'accession', 'extent_retained', 'Extent retained', 20, 0, 1, NOW(), NOW()),
  ('caais_extent_type', 'CAAIS Extent Type', 'accession', 'extent_removed',  'Extent removed',  30, 0, 1, NOW(), NOW()),
  -- 3.2.2 Unit of measure
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'linear_metres', 'Linear metres', 10, 1, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'centimetres',   'Centimetres',   20, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'boxes',         'Boxes',         30, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'folders',       'Folders',       40, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'items',         'Items',         50, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'volumes',       'Volumes',       60, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'carriers',      'Carriers',      70, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'files',         'Digital files', 80, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'megabytes',     'Megabytes',     90, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'gigabytes',     'Gigabytes',    100, 0, 1, NOW(), NOW()),
  ('caais_extent_unit', 'CAAIS Extent Unit', 'accession', 'terabytes',     'Terabytes',    110, 0, 1, NOW(), NOW()),
  -- 3.2.3 Content type
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'architectural_technical_drawings', 'Architectural and technical drawings', 10, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'artefacts',            'Artefacts',            20, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'cartographic',         'Cartographic materials', 30, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'datasets',             'Datasets',             40, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'graphic',              'Graphic materials',    50, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'interactive',          'Interactive resources', 60, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'moving_images',        'Moving images',        70, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'natural_objects',      'Natural objects',      80, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'philatelic',           'Philatelic records',   90, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'photographs',          'Photographs',         100, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'sound_recordings',     'Sound recordings',    110, 0, 1, NOW(), NOW()),
  ('caais_content_type', 'CAAIS Content Type', 'accession', 'textual',              'Textual records',     120, 1, 1, NOW(), NOW()),
  -- 3.2.4 Carrier type
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'paper',          'Paper',          10, 1, 1, NOW(), NOW()),
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'audio_cassette', 'Audio cassette', 20, 0, 1, NOW(), NOW()),
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'videotape',      'Videotape',      30, 0, 1, NOW(), NOW()),
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'film_reel',      'Film reel',      40, 0, 1, NOW(), NOW()),
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'disc',           'Disc',           50, 0, 1, NOW(), NOW()),
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'external_drive', 'External drive', 60, 0, 1, NOW(), NOW()),
  ('caais_carrier_type', 'CAAIS Carrier Type', 'accession', 'online_transfer','Online transfer (no carrier)', 70, 0, 1, NOW(), NOW()),
  -- 2.1.6 Source confidentiality
  ('caais_source_confidentiality', 'CAAIS Source Confidentiality', 'accession', 'internal_use_only', 'Internal use only',                10, 0, 1, NOW(), NOW()),
  ('caais_source_confidentiality', 'CAAIS Source Confidentiality', 'accession', 'not_public',        'Not for public access',            20, 0, 1, NOW(), NOW()),
  ('caais_source_confidentiality', 'CAAIS Source Confidentiality', 'accession', 'anonymous',         'Donor wishes to remain anonymous', 30, 0, 1, NOW(), NOW()),
  -- 3.4 Language of material (ISO 639 codes; add more in the Dropdown Manager)
  ('caais_language', 'CAAIS Language of Material', 'accession', 'en',  'English',              10, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'fr',  'French',               20, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'es',  'Spanish',              30, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'pt',  'Portuguese',           40, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'de',  'German',               50, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'nl',  'Dutch',                60, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'it',  'Italian',              70, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'ar',  'Arabic',               80, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'zh',  'Chinese',              90, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'ja',  'Japanese',            100, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'hi',  'Hindi',               110, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'sw',  'Swahili',             120, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'af',  'Afrikaans',           130, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'zu',  'Zulu',                140, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'cr',  'Cree',                150, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'iu',  'Inuktitut',           160, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'la',  'Latin',               170, 0, 1, NOW(), NOW()),
  ('caais_language', 'CAAIS Language of Material', 'accession', 'zxx', 'No linguistic content', 900, 0, 1, NOW(), NOW()),
  -- 4.3.1 Preservation requirement type
  ('caais_preservation_type', 'CAAIS Preservation Requirement Type', 'accession', 'physical_condition',  'Physical condition',  10, 0, 1, NOW(), NOW()),
  ('caais_preservation_type', 'CAAIS Preservation Requirement Type', 'accession', 'conservation',        'Conservation',        20, 0, 1, NOW(), NOW()),
  ('caais_preservation_type', 'CAAIS Preservation Requirement Type', 'accession', 'supplies',            'Supplies',            30, 0, 1, NOW(), NOW()),
  ('caais_preservation_type', 'CAAIS Preservation Requirement Type', 'accession', 'labour',              'Labour',              40, 0, 1, NOW(), NOW()),
  ('caais_preservation_type', 'CAAIS Preservation Requirement Type', 'accession', 'software_dependency', 'Software dependency', 50, 0, 1, NOW(), NOW()),
  ('caais_preservation_type', 'CAAIS Preservation Requirement Type', 'accession', 'technical_access',    'Technical access',    60, 0, 1, NOW(), NOW()),
  -- 5.1.1 Event type. physical_transfer and legal_transfer are the two CAAIS
  -- makes mandatory; the profile's completeness check looks for these codes.
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'physical_transfer',          'Physical transfer',                        10, 1, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'legal_transfer',             'Legal transfer',                           20, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'deed_of_gift_signed',        'Deed of gift signed',                      30, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'transfer_agreement_signed',  'Transfer agreement signed',                40, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'ceremonial_protocol',        'Ceremonial protocol acknowledging value',  50, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'reboxing_started',           'Reboxing started',                         60, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'reboxing_completed',         'Reboxing completed',                       70, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'archival_appraisal',         'Archival appraisal',                       80, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'disk_image_created',         'Disk image created',                       90, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'checksums_created',          'Checksums created',                       100, 0, 1, NOW(), NOW()),
  ('caais_event_type', 'CAAIS Event Type', 'accession', 'deaccessioned',              'Deaccessioned',                           110, 0, 1, NOW(), NOW()),
  -- 7.2.1 Creation or revision type
  ('caais_revision_type', 'CAAIS Creation or Revision Type', 'accession', 'created', 'Record created', 10, 0, 1, NOW(), NOW()),
  ('caais_revision_type', 'CAAIS Creation or Revision Type', 'accession', 'revised', 'Record revised', 20, 1, 1, NOW(), NOW());
