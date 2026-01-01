-- ============================================================
-- Migration: Create Library Level of Description Terms
-- Idempotent - safe to run multiple times
-- ============================================================

-- Clean up any bad sector mappings first
DELETE FROM level_of_description_sector WHERE term_id = 0 OR term_id IS NULL;
DELETE FROM level_of_description_sector WHERE sector = 'library';

-- Create Book term only if it doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM term t JOIN term_i18n ti ON t.id = ti.id 
    WHERE ti.name = 'Book' AND t.taxonomy_id = 34 AND ti.culture = 'en'
);
SET @book_id = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Book' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1);
SET @book_id = COALESCE(@book_id, LAST_INSERT_ID());
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES (@book_id, 34, 'en');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@book_id, 'en', 'Book');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@book_id, 'book');

-- Create Monograph term only if it doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM term t JOIN term_i18n ti ON t.id = ti.id 
    WHERE ti.name = 'Monograph' AND t.taxonomy_id = 34 AND ti.culture = 'en'
);
SET @mono_id = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Monograph' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1);
SET @mono_id = COALESCE(@mono_id, LAST_INSERT_ID());
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES (@mono_id, 34, 'en');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@mono_id, 'en', 'Monograph');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@mono_id, 'monograph');

-- Create Periodical term only if it doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM term t JOIN term_i18n ti ON t.id = ti.id 
    WHERE ti.name = 'Periodical' AND t.taxonomy_id = 34 AND ti.culture = 'en'
);
SET @period_id = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Periodical' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1);
SET @period_id = COALESCE(@period_id, LAST_INSERT_ID());
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES (@period_id, 34, 'en');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@period_id, 'en', 'Periodical');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@period_id, 'periodical');

-- Create Journal term only if it doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM term t JOIN term_i18n ti ON t.id = ti.id 
    WHERE ti.name = 'Journal' AND t.taxonomy_id = 34 AND ti.culture = 'en'
);
SET @journal_id = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Journal' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1);
SET @journal_id = COALESCE(@journal_id, LAST_INSERT_ID());
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES (@journal_id, 34, 'en');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@journal_id, 'en', 'Journal');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@journal_id, 'journal');

-- Create Article term only if it doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM term t JOIN term_i18n ti ON t.id = ti.id 
    WHERE ti.name = 'Article' AND t.taxonomy_id = 34 AND ti.culture = 'en'
);
SET @article_id = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Article' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1);
SET @article_id = COALESCE(@article_id, LAST_INSERT_ID());
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES (@article_id, 34, 'en');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@article_id, 'en', 'Article');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@article_id, 'article');

-- Create Manuscript term only if it doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM term t JOIN term_i18n ti ON t.id = ti.id 
    WHERE ti.name = 'Manuscript' AND t.taxonomy_id = 34 AND ti.culture = 'en'
);
SET @manuscript_id = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Manuscript' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1);
SET @manuscript_id = COALESCE(@manuscript_id, LAST_INSERT_ID());
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES (@manuscript_id, 34, 'en');
INSERT IGNORE INTO term_i18n (id, culture, name) VALUES (@manuscript_id, 'en', 'Manuscript');
INSERT IGNORE INTO slug (object_id, slug) VALUES (@manuscript_id, 'manuscript');

-- Now insert sector mappings using fresh lookups
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 10, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Book' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;

INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 20, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Monograph' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;

INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 30, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Periodical' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;

INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 40, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Journal' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;

INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 45, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Article' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;

INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 50, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Manuscript' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;

-- Add Document term if exists
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at)
SELECT t.id, 'library', 60, NOW() FROM term t JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Document' AND t.taxonomy_id = 34 AND ti.culture = 'en' LIMIT 1;
