-- ahgLibraryPlugin Installation Schema
-- Version: 1.0.0
-- Library & Bibliographic Cataloging with MARC-inspired fields

-- Main library item table (extends information_object)
CREATE TABLE IF NOT EXISTS library_item (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    information_object_id INT UNSIGNED NOT NULL,
    material_type VARCHAR(50) NOT NULL DEFAULT 'monograph' COMMENT 'monograph, serial, volume, issue, chapter, article, manuscript, map, pamphlet',
    subtitle VARCHAR(500) NULL,
    responsibility_statement VARCHAR(500) NULL,
    
    -- Classification & Location
    call_number VARCHAR(100) NULL,
    classification_scheme VARCHAR(50) NULL COMMENT 'dewey, lcc, udc, bliss, colon, custom',
    classification_number VARCHAR(100) NULL,
    dewey_decimal VARCHAR(50) NULL,
    cutter_number VARCHAR(50) NULL,
    shelf_location VARCHAR(100) NULL,
    copy_number VARCHAR(20) NULL,
    volume_designation VARCHAR(100) NULL,
    
    -- Standard Identifiers
    isbn VARCHAR(17) NULL,
    issn VARCHAR(9) NULL,
    lccn VARCHAR(50) NULL,
    oclc_number VARCHAR(50) NULL,
    openlibrary_id VARCHAR(50) NULL,
    goodreads_id VARCHAR(50) NULL,
    librarything_id VARCHAR(50) NULL,
    openlibrary_url VARCHAR(500) NULL,
    ebook_preview_url VARCHAR(500) NULL,
    cover_url VARCHAR(500) NULL,
    cover_url_original VARCHAR(500) NULL,
    doi VARCHAR(255) NULL,
    barcode VARCHAR(50) NULL,
    
    -- Publication Info
    edition VARCHAR(255) NULL,
    edition_statement VARCHAR(500) NULL,
    publisher VARCHAR(255) NULL,
    publication_place VARCHAR(255) NULL,
    publication_date VARCHAR(100) NULL,
    copyright_date VARCHAR(50) NULL,
    printing VARCHAR(100) NULL,
    
    -- Physical Description
    pagination VARCHAR(100) NULL,
    dimensions VARCHAR(100) NULL,
    physical_details TEXT NULL,
    language VARCHAR(100) NULL,
    accompanying_material TEXT NULL,
    
    -- Series Info
    series_title VARCHAR(500) NULL,
    series_number VARCHAR(50) NULL,
    series_issn VARCHAR(9) NULL,
    subseries_title VARCHAR(500) NULL,
    
    -- Notes
    general_note TEXT NULL,
    bibliography_note TEXT NULL,
    contents_note TEXT NULL,
    summary TEXT NULL,
    target_audience TEXT NULL,
    system_requirements TEXT NULL,
    binding_note TEXT NULL,
    
    -- Serial-specific fields
    frequency VARCHAR(50) NULL,
    former_frequency VARCHAR(100) NULL,
    numbering_peculiarities VARCHAR(255) NULL,
    publication_start_date DATE NULL,
    publication_end_date DATE NULL,
    publication_status VARCHAR(20) NULL COMMENT 'current, ceased, suspended',
    
    -- Circulation
    total_copies SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    available_copies SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    circulation_status VARCHAR(30) NOT NULL DEFAULT 'available' COMMENT 'available, on_loan, processing, lost, withdrawn, reference',
    
    -- Cataloging
    cataloging_source VARCHAR(100) NULL,
    cataloging_rules VARCHAR(20) NULL COMMENT 'aacr2, rda, isbd',
    encoding_level VARCHAR(20) NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_information_object (information_object_id),
    INDEX idx_isbn (isbn),
    INDEX idx_issn (issn),
    INDEX idx_call_number (call_number),
    INDEX idx_barcode (barcode),
    INDEX idx_material_type (material_type),
    INDEX idx_circulation (circulation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Library item creators (authors, editors, illustrators, etc.)
CREATE TABLE IF NOT EXISTS library_item_creator (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(500) NOT NULL,
    role VARCHAR(50) DEFAULT 'author' COMMENT 'author, editor, illustrator, translator, compiler, contributor',
    sort_order INT DEFAULT 0,
    authority_uri VARCHAR(500) NULL COMMENT 'VIAF, ISNI, or local authority link',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_library_item_id (library_item_id),
    INDEX idx_name (name(100)),
    CONSTRAINT fk_creator_library_item FOREIGN KEY (library_item_id) 
        REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Library item subjects (LCSH, MeSH, etc.)
CREATE TABLE IF NOT EXISTS library_item_subject (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    library_item_id BIGINT UNSIGNED NOT NULL,
    heading VARCHAR(500) NOT NULL,
    subject_type VARCHAR(50) DEFAULT 'topic' COMMENT 'topic, geographic, temporal, genre, personal, corporate',
    source VARCHAR(100) NULL COMMENT 'lcsh, mesh, fast, aat, local',
    uri VARCHAR(500) NULL COMMENT 'Linked data URI',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_library_item_id (library_item_id),
    INDEX idx_heading (heading(100)),
    INDEX idx_source (source),
    CONSTRAINT fk_subject_library_item FOREIGN KEY (library_item_id) 
        REFERENCES library_item(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ISBN lookup cache (avoid repeated API calls)
CREATE TABLE IF NOT EXISTS atom_isbn_cache (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(13) NOT NULL,
    isbn_10 VARCHAR(10) NULL,
    isbn_13 VARCHAR(13) NULL,
    metadata JSON NOT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'worldcat',
    oclc_number VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    UNIQUE KEY uk_isbn (isbn),
    INDEX idx_isbn_10 (isbn_10),
    INDEX idx_isbn_13 (isbn_13),
    INDEX idx_oclc (oclc_number),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ISBN lookup audit trail
CREATE TABLE IF NOT EXISTS atom_isbn_lookup_audit (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(13) NOT NULL,
    user_id INT NULL,
    information_object_id INT NULL,
    source VARCHAR(50) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    fields_populated JSON NULL,
    error_message TEXT NULL,
    lookup_time_ms INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_isbn (isbn),
    INDEX idx_user (user_id),
    INDEX idx_io (information_object_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ISBN providers configuration
CREATE TABLE IF NOT EXISTS atom_isbn_provider (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    api_endpoint VARCHAR(500) NOT NULL,
    api_key_setting VARCHAR(100) NULL COMMENT 'Reference to atom_setting key',
    priority INT NOT NULL DEFAULT 100,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    rate_limit_per_minute INT UNSIGNED NULL,
    response_format ENUM('json', 'xml', 'marcxml') NOT NULL DEFAULT 'json',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_slug (slug),
    INDEX idx_enabled_priority (enabled, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default ISBN providers
INSERT IGNORE INTO atom_isbn_provider (name, slug, api_endpoint, priority, enabled, response_format) VALUES
('Open Library', 'openlibrary', 'https://openlibrary.org/api/books', 10, 1, 'json'),
('WorldCat xISBN', 'worldcat', 'http://xisbn.worldcat.org/webservices/xid/isbn/', 20, 1, 'json'),
('Google Books', 'google', 'https://www.googleapis.com/books/v1/volumes', 30, 1, 'json');

-- Library settings
CREATE TABLE IF NOT EXISTS library_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default library settings
INSERT IGNORE INTO library_settings (setting_key, setting_value, setting_type, description) VALUES
('default_classification_scheme', 'dewey', 'string', 'Default classification scheme (dewey, lcc, udc)'),
('isbn_auto_lookup', '1', 'boolean', 'Automatically lookup ISBN on entry'),
('isbn_cache_days', '30', 'integer', 'Days to cache ISBN lookup results'),
('show_cover_images', '1', 'boolean', 'Display cover images from ISBN lookup'),
('default_cataloging_rules', 'rda', 'string', 'Default cataloging rules (aacr2, rda, isbd)');
