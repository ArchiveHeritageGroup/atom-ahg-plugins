-- ahgLibraryPlugin - Data Only
-- Version: 1.0.0
-- Tables are created by atom-framework/database/install.sql

-- Register plugin
INSERT IGNORE INTO atom_plugin (name, is_enabled, version, category) VALUES
('ahgLibraryPlugin', 1, '1.0.0', 'sector');

-- Create Library-specific Level of Description terms
-- Book
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @book_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@book_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@book_id, 'en', 'Book');
INSERT INTO slug (object_id, slug) VALUES (@book_id, CONCAT('book-', @book_id));

-- Monograph
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @mono_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@mono_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@mono_id, 'en', 'Monograph');
INSERT INTO slug (object_id, slug) VALUES (@mono_id, CONCAT('monograph-', @mono_id));

-- Periodical
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @peri_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@peri_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@peri_id, 'en', 'Periodical');
INSERT INTO slug (object_id, slug) VALUES (@peri_id, CONCAT('periodical-', @peri_id));

-- Journal
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @jour_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@jour_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@jour_id, 'en', 'Journal');
INSERT INTO slug (object_id, slug) VALUES (@jour_id, CONCAT('journal-', @jour_id));

-- Article
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @arti_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@arti_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@arti_id, 'en', 'Article');
INSERT INTO slug (object_id, slug) VALUES (@arti_id, CONCAT('article-', @arti_id));

-- Manuscript
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @manu_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@manu_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@manu_id, 'en', 'Manuscript');
INSERT INTO slug (object_id, slug) VALUES (@manu_id, CONCAT('manuscript-', @manu_id));

-- Now insert into level_of_description_sector
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES
(@book_id, 'library', 10),
(@mono_id, 'library', 20),
(@peri_id, 'library', 30),
(@jour_id, 'library', 40),
(@arti_id, 'library', 45),
(@manu_id, 'library', 50);

-- Add Document (existing term) to library sector
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order)
SELECT t.id, 'library', 60 FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Document' AND ti.culture = 'en' AND t.taxonomy_id = 34 LIMIT 1;

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
