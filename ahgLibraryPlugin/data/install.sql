-- =====================================================
-- Library Plugin Install
-- =====================================================

SET @library_exists = (SELECT COUNT(*) FROM term WHERE code = 'library' AND taxonomy_id = 70);

INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @library_exists = 0;

SET @library_id = LAST_INSERT_ID();

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT @library_id, 70, 'library', 'en' FROM DUAL WHERE @library_exists = 0 AND @library_id > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT @library_id, 'en', 'Library (MARC-inspired)' FROM DUAL WHERE @library_exists = 0 AND @library_id > 0;

-- =====================================================
-- Library Level of Description Terms (taxonomy_id = 34)
-- =====================================================

-- Book
SET @book_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Book' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @book_exists IS NULL;
SET @book_id = IF(@book_exists IS NULL, LAST_INSERT_ID(), @book_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @book_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @book_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @book_id, 'en', 'Book' FROM DUAL WHERE @book_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@book_id, 'book');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@book_id, 'library', 10);

-- Monograph
SET @mono_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Monograph' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @mono_exists IS NULL;
SET @mono_id = IF(@mono_exists IS NULL, LAST_INSERT_ID(), @mono_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @mono_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @mono_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @mono_id, 'en', 'Monograph' FROM DUAL WHERE @mono_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@mono_id, 'monograph');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@mono_id, 'library', 20);

-- Periodical
SET @peri_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Periodical' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @peri_exists IS NULL;
SET @peri_id = IF(@peri_exists IS NULL, LAST_INSERT_ID(), @peri_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @peri_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @peri_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @peri_id, 'en', 'Periodical' FROM DUAL WHERE @peri_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@peri_id, 'periodical');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@peri_id, 'library', 30);

-- Journal
SET @jour_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Journal' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @jour_exists IS NULL;
SET @jour_id = IF(@jour_exists IS NULL, LAST_INSERT_ID(), @jour_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @jour_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @jour_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @jour_id, 'en', 'Journal' FROM DUAL WHERE @jour_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@jour_id, 'journal');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@jour_id, 'library', 40);

-- Article
SET @arti_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Article' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @arti_exists IS NULL;
SET @arti_id = IF(@arti_exists IS NULL, LAST_INSERT_ID(), @arti_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @arti_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @arti_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @arti_id, 'en', 'Article' FROM DUAL WHERE @arti_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@arti_id, 'article');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@arti_id, 'library', 45);

-- Manuscript
SET @manu_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Manuscript' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @manu_exists IS NULL;
SET @manu_id = IF(@manu_exists IS NULL, LAST_INSERT_ID(), @manu_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @manu_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @manu_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @manu_id, 'en', 'Manuscript' FROM DUAL WHERE @manu_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@manu_id, 'manuscript');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@manu_id, 'library', 50);

-- Document (shared with DAM)
SET @doc_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Document' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @doc_exists IS NULL;
SET @doc_id = IF(@doc_exists IS NULL, LAST_INSERT_ID(), @doc_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @doc_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @doc_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @doc_id, 'en', 'Document' FROM DUAL WHERE @doc_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@doc_id, 'document');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@doc_id, 'library', 60);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@doc_id, 'dam', 50);
