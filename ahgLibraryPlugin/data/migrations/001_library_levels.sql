-- Library Level of Description terms
-- Creates proper AtoM objects before terms

-- Book
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @book_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@book_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@book_id, 'en', 'Book');

-- Monograph
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @mono_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@mono_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@mono_id, 'en', 'Monograph');

-- Periodical
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @period_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@period_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@period_id, 'en', 'Periodical');

-- Journal
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @journal_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@journal_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@journal_id, 'en', 'Journal');

-- Article
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @article_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@article_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@article_id, 'en', 'Article');

-- Manuscript
INSERT INTO object (class_name, created_at, updated_at) VALUES ('QubitTerm', NOW(), NOW());
SET @manuscript_id = LAST_INSERT_ID();
INSERT INTO term (id, taxonomy_id, source_culture) VALUES (@manuscript_id, 34, 'en');
INSERT INTO term_i18n (id, culture, name) VALUES (@manuscript_id, 'en', 'Manuscript');

-- Level of description sector mappings for library
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at) VALUES
(@book_id, 'library', 10, NOW()),
(@mono_id, 'library', 20, NOW()),
(@period_id, 'library', 30, NOW()),
(@journal_id, 'library', 40, NOW()),
(@article_id, 'library', 45, NOW()),
(@manuscript_id, 'library', 50, NOW());

-- Add existing Document term if exists
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 60, NOW() FROM term t 
JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Document' AND t.taxonomy_id = 34 AND ti.culture = 'en'
LIMIT 1;
