-- =============================================================================
-- arDisplayPlugin Schema
-- LAYER ON TOP of AtoM - does not modify core tables
-- Uses existing: information_object, term, taxonomy, property, event, relation
-- =============================================================================

-- -----------------------------------------------------------------------------
-- OBJECT TYPE EXTENSION
-- Links information_object to display configuration without modifying core
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS display_object_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL UNIQUE,
    object_type VARCHAR(30) DEFAULT 'archive',
    primary_profile_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_object (object_id),
    INDEX idx_type (object_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- EXTENDED LEVELS OF DESCRIPTION
-- Supplements existing level_of_description taxonomy
-- Uses term table pattern for future taxonomy integration
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS display_level (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    parent_code VARCHAR(30),
    domain VARCHAR(20) DEFAULT 'universal',
    valid_parent_codes JSON,
    valid_child_codes JSON,
    icon VARCHAR(50),
    color VARCHAR(20),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    
    -- Link to existing AtoM taxonomy term (if exists)
    atom_term_id INT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS display_level_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    PRIMARY KEY (id, culture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- DISPLAY PROFILES
-- Defines how objects render in different contexts
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS display_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    domain VARCHAR(20),
    layout_mode ENUM('detail', 'hierarchy', 'grid', 'gallery', 'list', 'card', 'masonry', 'catalog') DEFAULT 'detail',
    
    -- Thumbnail/image display
    thumbnail_size ENUM('none', 'small', 'medium', 'large', 'hero', 'full') DEFAULT 'medium',
    thumbnail_position ENUM('left', 'right', 'top', 'background', 'inline') DEFAULT 'left',
    
    -- Field configuration (JSON arrays of field codes)
    identity_fields JSON,
    description_fields JSON,
    context_fields JSON,
    access_fields JSON,
    technical_fields JSON,
    hidden_fields JSON,
    
    -- Label overrides (JSON object)
    field_labels JSON,
    
    -- Available actions
    available_actions JSON,
    
    -- Styling
    css_class VARCHAR(100),
    
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS display_profile_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    PRIMARY KEY (id, culture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Object-to-profile assignments
CREATE TABLE IF NOT EXISTS display_object_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    profile_id INT NOT NULL,
    context VARCHAR(30) DEFAULT 'default',
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (object_id, profile_id, context),
    INDEX idx_object (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- FIELD MAPPING
-- Maps canonical display fields to AtoM database columns/tables
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS display_field (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    field_group ENUM('identity', 'description', 'context', 'access', 'technical', 'admin') DEFAULT 'description',
    data_type ENUM('text', 'textarea', 'date', 'daterange', 'number', 'select', 'multiselect', 'relation', 'file', 'actor', 'term') DEFAULT 'text',
    
    -- AtoM database mapping
    source_table VARCHAR(100),
    source_column VARCHAR(100),
    source_i18n TINYINT(1) DEFAULT 0,
    
    -- For property-based fields
    property_type_id INT,
    
    -- For term-based fields (dropdowns from taxonomy)
    taxonomy_id INT,
    
    -- For relation-based fields
    relation_type_id INT,
    
    -- For event-based fields
    event_type_id INT,
    
    -- Standard mappings (for export/crosswalk)
    isad_element VARCHAR(50),
    spectrum_unit VARCHAR(50),
    dc_element VARCHAR(50),
    
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS display_field_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(100) NOT NULL,
    help_text TEXT,
    PRIMARY KEY (id, culture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- COLLECTION TYPES (for book collections, etc.)
-- Uses existing taxonomy pattern
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS display_collection_type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    parent_id INT,
    icon VARCHAR(50),
    color VARCHAR(20),
    default_profile_id INT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS display_collection_type_i18n (
    id INT NOT NULL,
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    PRIMARY KEY (id, culture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- DEFAULT DATA
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Extended Levels of Description
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO display_level (code, domain, valid_parent_codes, valid_child_codes, icon, sort_order) VALUES
-- Universal
('repository', 'universal', NULL, '["fonds","collection","holding"]', 'fa-building', 1),
('collection', 'universal', '["repository"]', '["fonds","series","album","object","item","book"]', 'fa-folder-tree', 5),

-- Archive (ISAD)
('fonds', 'archive', '["repository"]', '["subfonds","series"]', 'fa-archive', 10),
('subfonds', 'archive', '["fonds"]', '["series"]', 'fa-folder', 11),
('series', 'archive', '["fonds","subfonds","collection"]', '["subseries","file"]', 'fa-folder-open', 12),
('subseries', 'archive', '["series"]', '["file"]', 'fa-folder-open', 13),
('file', 'archive', '["series","subseries"]', '["item","piece"]', 'fa-file-alt', 14),
('item', 'archive', '["file","series","collection"]', '["piece","component"]', 'fa-file', 15),
('piece', 'archive', '["item","file"]', NULL, 'fa-puzzle-piece', 16),

-- Museum (Spectrum)
('holding', 'museum', '["repository"]', '["object_group","object"]', 'fa-landmark', 20),
('object_group', 'museum', '["holding","collection"]', '["object"]', 'fa-cubes', 21),
('object', 'museum', '["object_group","holding","collection"]', '["component","fragment"]', 'fa-cube', 22),
('component', 'museum', '["object"]', NULL, 'fa-puzzle-piece', 23),
('specimen', 'museum', '["collection","holding"]', '["sample"]', 'fa-leaf', 25),

-- Gallery/Art
('artist_archive', 'gallery', '["repository"]', '["artwork_series","artwork"]', 'fa-palette', 30),
('artwork_series', 'gallery', '["artist_archive","collection"]', '["artwork"]', 'fa-layer-group', 31),
('artwork', 'gallery', '["artwork_series","collection"]', '["study","edition"]', 'fa-image', 32),
('study', 'gallery', '["artwork"]', NULL, 'fa-pencil-alt', 33),
('edition', 'gallery', '["artwork"]', '["impression"]', 'fa-clone', 34),
('impression', 'gallery', '["edition"]', NULL, 'fa-stamp', 35),

-- Book Collection (simplified library)
('book_collection', 'library', '["repository"]', '["book","periodical","volume"]', 'fa-books', 40),
('book', 'library', '["book_collection","collection"]', '["chapter"]', 'fa-book', 41),
('periodical', 'library', '["book_collection"]', '["issue"]', 'fa-newspaper', 42),
('volume', 'library', '["periodical","book"]', '["issue","chapter"]', 'fa-book-open', 43),
('issue', 'library', '["periodical","volume"]', '["article"]', 'fa-file-alt', 44),
('chapter', 'library', '["book","volume"]', NULL, 'fa-bookmark', 45),
('pamphlet', 'library', '["book_collection","collection"]', NULL, 'fa-scroll', 46),
('map', 'library', '["collection"]', NULL, 'fa-map', 47),

-- DAM/Photographs
('photo_collection', 'dam', '["repository"]', '["album","shoot","photograph"]', 'fa-images', 50),
('album', 'dam', '["photo_collection","collection"]', '["photograph"]', 'fa-book-open', 51),
('shoot', 'dam', '["photo_collection"]', '["photograph"]', 'fa-camera', 52),
('photograph', 'dam', '["album","shoot","photo_collection","collection"]', '["derivative"]', 'fa-image', 53),
('negative', 'dam', '["album","collection"]', NULL, 'fa-film', 54),
('slide', 'dam', '["album","collection"]', NULL, 'fa-square', 55),
('digital_asset', 'dam', '["collection","album"]', NULL, 'fa-file-image', 56),

-- Audiovisual
('av_collection', 'archive', '["repository"]', '["recording","film"]', 'fa-film', 60),
('film', 'archive', '["av_collection","collection"]', '["reel","clip"]', 'fa-video', 61),
('recording', 'archive', '["av_collection","collection"]', '["segment"]', 'fa-record-vinyl', 62),
('reel', 'archive', '["film"]', '["clip"]', 'fa-circle', 63),
('segment', 'archive', '["recording"]', NULL, 'fa-cut', 64);

-- Level i18n
INSERT IGNORE INTO display_level_i18n (id, culture, name, description) 
SELECT id, 'en', 
    CONCAT(UPPER(SUBSTRING(REPLACE(code, '_', ' '), 1, 1)), LOWER(SUBSTRING(REPLACE(code, '_', ' '), 2))),
    NULL
FROM display_level;

-- -----------------------------------------------------------------------------
-- Display Profiles
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO display_profile (code, domain, layout_mode, thumbnail_size, thumbnail_position, identity_fields, description_fields, context_fields, access_fields, available_actions, is_default, sort_order) VALUES
-- Archive profiles
('isad_full', 'archive', 'detail', 'small', 'left',
    '["identifier","title","dates","level","extent","creator"]',
    '["scope_content","arrangement","appraisal"]',
    '["provenance","custodial_history","acquisition"]',
    '["access_conditions","reproduction","language","finding_aids"]',
    '["view","download","request","cite","print"]',
    1, 1),

('isad_hierarchy', 'archive', 'hierarchy', 'none', 'left',
    '["identifier","title","dates","level"]',
    '["scope_content"]',
    '[]',
    '[]',
    '["view","expand"]',
    0, 2),

('isad_list', 'archive', 'list', 'small', 'left',
    '["identifier","title","dates","level"]',
    '[]',
    '[]',
    '[]',
    '["view"]',
    0, 3),

-- Museum profiles
('spectrum_full', 'museum', 'detail', 'large', 'left',
    '["object_number","object_name","classification","materials","dimensions","technique"]',
    '["description","inscription","condition","completeness"]',
    '["production","provenance","acquisition","associations"]',
    '["access_conditions","reproduction"]',
    '["view","condition_report","movement","loan_request","print"]',
    1, 10),

('spectrum_card', 'museum', 'card', 'medium', 'top',
    '["object_number","object_name","materials"]',
    '["description"]',
    '[]',
    '[]',
    '["view","add_to_exhibition"]',
    0, 11),

('spectrum_catalog', 'museum', 'catalog', 'medium', 'left',
    '["object_number","object_name","materials","dimensions","date"]',
    '["description"]',
    '["provenance"]',
    '[]',
    '["view","print"]',
    0, 12),

-- Gallery profiles
('gallery_full', 'gallery', 'gallery', 'hero', 'top',
    '["artist","title","date","medium","dimensions","edition_info"]',
    '["description","artist_statement"]',
    '["provenance","exhibition_history","bibliography"]',
    '["rights","reproduction"]',
    '["view","zoom","add_to_exhibition","license","print"]',
    1, 20),

('gallery_wall', 'gallery', 'gallery', 'full', 'background',
    '["artist","title","date","medium"]',
    '[]',
    '[]',
    '[]',
    '["view","zoom","info"]',
    0, 21),

('gallery_catalog', 'gallery', 'catalog', 'medium', 'left',
    '["artist","title","date","medium","dimensions"]',
    '["description"]',
    '["provenance","literature"]',
    '[]',
    '["view","print"]',
    0, 22),

-- Book Collection profiles
('book_full', 'library', 'detail', 'small', 'left',
    '["call_number","title","author","publisher","date","isbn","edition"]',
    '["abstract","subjects","table_of_contents"]',
    '["provenance","notes"]',
    '["access_conditions","location"]',
    '["view","request","cite","print"]',
    1, 30),

('book_list', 'library', 'list', 'none', 'left',
    '["call_number","author","title","date"]',
    '[]',
    '[]',
    '[]',
    '["view","request"]',
    0, 31),

('book_card', 'library', 'card', 'small', 'left',
    '["title","author","date"]',
    '["abstract"]',
    '[]',
    '[]',
    '["view"]',
    0, 32),

-- DAM/Photo profiles
('photo_full', 'dam', 'detail', 'large', 'top',
    '["asset_id","title","photographer","date_taken","location"]',
    '["caption","keywords"]',
    '["provenance","collection"]',
    '["rights","usage_terms","restrictions"]',
    '["view","zoom","download","add_to_lightbox","license","derivatives"]',
    1, 40),

('photo_grid', 'dam', 'grid', 'medium', 'top',
    '["title","date"]',
    '[]',
    '[]',
    '[]',
    '["view","select","add_to_lightbox"]',
    0, 41),

('photo_lightbox', 'dam', 'masonry', 'large', 'top',
    '["title"]',
    '[]',
    '[]',
    '[]',
    '["view","zoom","select","compare","download"]',
    0, 42),

-- Universal/Search
('search_result', 'universal', 'card', 'small', 'left',
    '["identifier","title","creator","date","level"]',
    '["description"]',
    '[]',
    '[]',
    '["view"]',
    0, 100),

('print_record', 'universal', 'detail', 'medium', 'right',
    '["identifier","title","creator","date","level","extent"]',
    '["scope_content","description"]',
    '["provenance","acquisition"]',
    '["access_conditions","rights"]',
    '[]',
    0, 101);

-- Profile i18n
INSERT IGNORE INTO display_profile_i18n (id, culture, name)
SELECT id, 'en', CONCAT(UPPER(SUBSTRING(REPLACE(code, '_', ' '), 1, 1)), SUBSTRING(REPLACE(code, '_', ' '), 2))
FROM display_profile;

-- -----------------------------------------------------------------------------
-- Field Mappings (to existing AtoM tables)
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO display_field (code, field_group, data_type, source_table, source_column, source_i18n, isad_element, spectrum_unit, dc_element, sort_order) VALUES
-- Identity fields (from information_object)
('identifier', 'identity', 'text', 'information_object', 'identifier', 0, '3.1.1', 'Object number', 'identifier', 1),
('title', 'identity', 'text', 'information_object_i18n', 'title', 1, '3.1.2', 'Object name', 'title', 2),
('level', 'identity', 'term', 'term_i18n', 'name', 1, '3.1.4', NULL, NULL, 3),
('extent', 'identity', 'textarea', 'information_object_i18n', 'extent_and_medium', 1, '3.1.5', 'Dimensions', 'format', 4),

-- Creator/Author (from event/relation)
('creator', 'identity', 'actor', 'event', 'actor_id', 0, '3.2.1', 'Production person', 'creator', 5),
('author', 'identity', 'actor', 'event', 'actor_id', 0, NULL, NULL, 'creator', 6),
('artist', 'identity', 'actor', 'event', 'actor_id', 0, NULL, 'Production person', 'creator', 7),
('photographer', 'identity', 'actor', 'event', 'actor_id', 0, NULL, NULL, 'creator', 8),

-- Dates (from event)
('dates', 'identity', 'daterange', 'event', 'date', 0, '3.1.3', 'Production date', 'date', 10),
('date', 'identity', 'date', 'event', 'start_date', 0, '3.1.3', 'Production date', 'date', 11),
('date_taken', 'identity', 'date', 'event', 'start_date', 0, NULL, NULL, 'date', 12),

-- Description fields
('scope_content', 'description', 'textarea', 'information_object_i18n', 'scope_and_content', 1, '3.3.1', 'Description', 'description', 20),
('description', 'description', 'textarea', 'information_object_i18n', 'scope_and_content', 1, '3.3.1', 'Description', 'description', 21),
('abstract', 'description', 'textarea', 'information_object_i18n', 'scope_and_content', 1, '3.3.1', NULL, 'description', 22),
('caption', 'description', 'textarea', 'information_object_i18n', 'scope_and_content', 1, NULL, NULL, 'description', 23),
('arrangement', 'description', 'textarea', 'information_object_i18n', 'arrangement', 1, '3.3.4', NULL, NULL, 24),
('appraisal', 'description', 'textarea', 'information_object_i18n', 'appraisal', 1, '3.3.2', NULL, NULL, 25),

-- Physical description (from property or information_object)
('materials', 'description', 'text', 'information_object_i18n', 'extent_and_medium', 1, NULL, 'Material', 'format', 30),
('dimensions', 'description', 'text', 'property', 'dimensions', 0, NULL, 'Dimension', 'format', 31),
('technique', 'description', 'text', 'property', 'technique', 0, NULL, 'Technique', NULL, 32),
('medium', 'description', 'text', 'property', 'medium', 0, NULL, 'Technique', 'format', 33),
('condition', 'description', 'textarea', 'property', 'condition', 0, NULL, 'Condition', NULL, 34),
('inscription', 'description', 'textarea', 'property', 'inscription', 0, NULL, 'Inscription', NULL, 35),
('edition_info', 'description', 'text', 'property', 'edition', 0, NULL, NULL, NULL, 36),

-- Subjects/Keywords
('subjects', 'description', 'multiselect', 'object_term_relation', 'term_id', 0, NULL, NULL, 'subject', 40),
('keywords', 'description', 'multiselect', 'object_term_relation', 'term_id', 0, NULL, NULL, 'subject', 41),

-- Context fields
('provenance', 'context', 'textarea', 'information_object_i18n', 'archival_history', 1, '3.2.3', 'Acquisition history', 'provenance', 50),
('custodial_history', 'context', 'textarea', 'information_object_i18n', 'archival_history', 1, '3.2.3', 'Ownership history', NULL, 51),
('acquisition', 'context', 'textarea', 'information_object_i18n', 'acquisition', 1, '3.2.4', 'Acquisition', NULL, 52),
('production', 'context', 'text', 'event', 'place_id', 0, NULL, 'Production place', 'coverage', 53),
('exhibition_history', 'context', 'textarea', 'property', 'exhibition_history', 0, NULL, 'Exhibition', NULL, 54),
('bibliography', 'context', 'textarea', 'property', 'bibliography', 0, NULL, 'Publication', NULL, 55),
('associations', 'context', 'textarea', 'property', 'associations', 0, NULL, 'Associated object', NULL, 56),

-- Library specific
('call_number', 'identity', 'text', 'property', 'call_number', 0, NULL, NULL, 'identifier', 60),
('isbn', 'identity', 'text', 'property', 'isbn', 0, NULL, NULL, 'identifier', 61),
('publisher', 'identity', 'text', 'property', 'publisher', 0, NULL, NULL, 'publisher', 62),
('edition', 'identity', 'text', 'property', 'edition', 0, NULL, NULL, NULL, 63),
('table_of_contents', 'description', 'textarea', 'property', 'toc', 0, NULL, NULL, NULL, 64),

-- Museum specific
('object_number', 'identity', 'text', 'information_object', 'identifier', 0, NULL, 'Object number', 'identifier', 70),
('object_name', 'identity', 'text', 'information_object_i18n', 'title', 1, NULL, 'Object name', 'title', 71),
('classification', 'identity', 'term', 'object_term_relation', 'term_id', 0, NULL, 'Object type', 'type', 72),
('completeness', 'description', 'text', 'property', 'completeness', 0, NULL, 'Completeness', NULL, 73),

-- DAM specific
('asset_id', 'identity', 'text', 'digital_object', 'name', 0, NULL, NULL, 'identifier', 80),
('location', 'identity', 'text', 'property', 'location_taken', 0, NULL, NULL, 'coverage', 81),

-- Access fields
('access_conditions', 'access', 'textarea', 'information_object_i18n', 'access_conditions', 1, '3.4.1', 'Access', 'rights', 90),
('reproduction', 'access', 'textarea', 'information_object_i18n', 'reproduction_conditions', 1, '3.4.2', 'Reproduction', 'rights', 91),
('rights', 'access', 'text', 'information_object', 'copyright_status_id', 0, NULL, 'Rights', 'rights', 92),
('usage_terms', 'access', 'textarea', 'property', 'usage_terms', 0, NULL, NULL, 'rights', 93),
('restrictions', 'access', 'textarea', 'property', 'restrictions', 0, NULL, NULL, 'rights', 94),
('language', 'access', 'term', 'information_object_i18n', 'language_of_material', 1, '3.4.3', NULL, 'language', 95),
('finding_aids', 'access', 'textarea', 'information_object_i18n', 'finding_aids', 1, '3.4.5', NULL, NULL, 96),

-- Technical (from digital_object)
('filename', 'technical', 'text', 'digital_object', 'name', 0, NULL, NULL, NULL, 100),
('mime_type', 'technical', 'text', 'digital_object', 'mime_type', 0, NULL, NULL, 'format', 101),
('file_size', 'technical', 'number', 'digital_object', 'byte_size', 0, NULL, NULL, 'format', 102),

-- Admin
('current_location', 'admin', 'text', 'physical_object', 'location_id', 0, NULL, 'Current location', NULL, 110),
('accession_number', 'admin', 'text', 'accession', 'identifier', 0, NULL, 'Accession number', NULL, 111);

-- Field i18n
INSERT IGNORE INTO display_field_i18n (id, culture, name)
SELECT id, 'en', CONCAT(UPPER(SUBSTRING(REPLACE(code, '_', ' '), 1, 1)), SUBSTRING(REPLACE(code, '_', ' '), 2))
FROM display_field;

-- Update specific field names
UPDATE display_field_i18n SET name = 'Reference Code' WHERE id = (SELECT id FROM display_field WHERE code = 'identifier');
UPDATE display_field_i18n SET name = 'Scope and Content' WHERE id = (SELECT id FROM display_field WHERE code = 'scope_content');
UPDATE display_field_i18n SET name = 'Custodial History' WHERE id = (SELECT id FROM display_field WHERE code = 'custodial_history');
UPDATE display_field_i18n SET name = 'ISBN/ISSN' WHERE id = (SELECT id FROM display_field WHERE code = 'isbn');
UPDATE display_field_i18n SET name = 'Call Number' WHERE id = (SELECT id FROM display_field WHERE code = 'call_number');
UPDATE display_field_i18n SET name = 'Access Conditions' WHERE id = (SELECT id FROM display_field WHERE code = 'access_conditions');
UPDATE display_field_i18n SET name = 'Reproduction Conditions' WHERE id = (SELECT id FROM display_field WHERE code = 'reproduction');
UPDATE display_field_i18n SET name = 'Finding Aids' WHERE id = (SELECT id FROM display_field WHERE code = 'finding_aids');

-- -----------------------------------------------------------------------------
-- Collection Types
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO display_collection_type (code, icon, sort_order) VALUES
('archive', 'fa-archive', 1),
('museum', 'fa-landmark', 2),
('gallery', 'fa-palette', 3),
('library', 'fa-book', 4),
('photo_archive', 'fa-images', 5),
('audiovisual', 'fa-film', 6),
('mixed', 'fa-layer-group', 10);

INSERT IGNORE INTO display_collection_type_i18n (id, culture, name, description) VALUES
(1, 'en', 'Archive', 'Archival fonds and collections following ISAD(G)'),
(2, 'en', 'Museum', 'Museum objects following Spectrum'),
(3, 'en', 'Gallery', 'Artworks and artist archives'),
(4, 'en', 'Book Collection', 'Books, periodicals, and printed materials'),
(5, 'en', 'Photo Archive', 'Photographs and digital assets'),
(6, 'en', 'Audiovisual', 'Film, video, and audio recordings'),
(7, 'en', 'Mixed Collection', 'Collections with mixed material types');

-- -----------------------------------------------------------------------------
-- DISPLAY MODE GLOBAL SETTINGS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `display_mode_global` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Module: informationobject, actor, repository, etc.',
  `display_mode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'list',
  `items_per_page` int DEFAULT '30',
  `sort_field` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'updated_at',
  `sort_direction` enum('asc','desc') COLLATE utf8mb4_unicode_ci DEFAULT 'desc',
  `show_thumbnails` tinyint(1) DEFAULT '1',
  `show_descriptions` tinyint(1) DEFAULT '1',
  `card_size` enum('small','medium','large') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `available_modes` json DEFAULT NULL COMMENT 'JSON array of enabled modes for this module',
  `allow_user_override` tinyint(1) DEFAULT '1' COMMENT 'Allow users to change from default',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_module` (`module`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO display_mode_global (module, display_mode, available_modes) VALUES
('informationobject', 'list', '["list","card","grid","table"]'),
('actor', 'list', '["list","card","grid"]'),
('repository', 'card', '["list","card","grid"]'),
('accession', 'table', '["list","table"]'),
('function', 'list', '["list","card"]'),
('term', 'list', '["list","tree"]');

-- -----------------------------------------------------------------------------
-- USER DISPLAY PREFERENCES
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_display_preference` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Module context: informationobject, actor, repository, etc.',
  `display_mode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'list' COMMENT 'tree, grid, gallery, list, timeline',
  `items_per_page` int DEFAULT '30',
  `sort_field` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'updated_at',
  `sort_direction` enum('asc','desc') COLLATE utf8mb4_unicode_ci DEFAULT 'desc',
  `show_thumbnails` tinyint(1) DEFAULT '1',
  `show_descriptions` tinyint(1) DEFAULT '1',
  `card_size` enum('small','medium','large') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_custom` tinyint(1) DEFAULT '1' COMMENT 'True if user explicitly set, false if inherited from global',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_module` (`user_id`, `module`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
