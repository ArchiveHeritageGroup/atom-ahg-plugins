-- ahgLibraryPlugin - Data Only
-- Version: 1.0.0
-- Tables are created by atom-framework/database/install.sql

-- Register plugin
INSERT IGNORE INTO atom_plugin (name, is_enabled, version, category) VALUES
('ahgLibraryPlugin', 1, '1.0.0', 'sector');

-- Library sector levels of description
-- First create the terms if they don't exist
INSERT IGNORE INTO object (class_name, created_at, updated_at) SELECT 'QubitTerm', NOW(), NOW() FROM dual WHERE NOT EXISTS (SELECT 1 FROM term_i18n WHERE name = 'Book' AND culture = 'en');
INSERT IGNORE INTO object (class_name, created_at, updated_at) SELECT 'QubitTerm', NOW(), NOW() FROM dual WHERE NOT EXISTS (SELECT 1 FROM term_i18n WHERE name = 'Monograph' AND culture = 'en');
INSERT IGNORE INTO object (class_name, created_at, updated_at) SELECT 'QubitTerm', NOW(), NOW() FROM dual WHERE NOT EXISTS (SELECT 1 FROM term_i18n WHERE name = 'Periodical' AND culture = 'en');
INSERT IGNORE INTO object (class_name, created_at, updated_at) SELECT 'QubitTerm', NOW(), NOW() FROM dual WHERE NOT EXISTS (SELECT 1 FROM term_i18n WHERE name = 'Journal' AND culture = 'en');
INSERT IGNORE INTO object (class_name, created_at, updated_at) SELECT 'QubitTerm', NOW(), NOW() FROM dual WHERE NOT EXISTS (SELECT 1 FROM term_i18n WHERE name = 'Article' AND culture = 'en');
INSERT IGNORE INTO object (class_name, created_at, updated_at) SELECT 'QubitTerm', NOW(), NOW() FROM dual WHERE NOT EXISTS (SELECT 1 FROM term_i18n WHERE name = 'Manuscript' AND culture = 'en');

-- Default ISBN providers
INSERT IGNORE INTO atom_isbn_provider (name, slug, api_endpoint, priority, enabled, response_format) VALUES
('Open Library', 'openlibrary', 'https://openlibrary.org/api/books', 10, 1, 'json'),
('WorldCat xISBN', 'worldcat', 'http://xisbn.worldcat.org/webservices/xid/isbn/', 20, 1, 'json'),
('Google Books', 'google', 'https://www.googleapis.com/books/v1/volumes', 30, 1, 'json');

-- Library settings
INSERT IGNORE INTO library_settings (setting_key, setting_value, setting_type, description) VALUES
('default_classification_scheme', 'dewey', 'string', 'Default classification scheme (dewey, lcc, udc)'),
('isbn_auto_lookup', '1', 'boolean', 'Automatically lookup ISBN on entry'),
('isbn_cache_days', '30', 'integer', 'Days to cache ISBN lookup results'),
('show_cover_images', '1', 'boolean', 'Display cover images from ISBN lookup'),
('default_cataloging_rules', 'rda', 'string', 'Default cataloging rules (aacr2, rda, isbd)');
