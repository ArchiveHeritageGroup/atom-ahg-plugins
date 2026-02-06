-- =====================================================
-- Museum Plugin Install
-- =====================================================

-- Add Museum display standard term (taxonomy_id = 70)
-- Check if already exists first
SET @museum_exists = (SELECT COUNT(*) FROM term WHERE code = 'museum' AND taxonomy_id = 70);

-- Create object only if term doesn't exist
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @museum_exists = 0;

SET @museum_id = LAST_INSERT_ID();

-- Create term only if we just created an object
INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT @museum_id, 70, 'museum', 'en' FROM DUAL WHERE @museum_exists = 0 AND @museum_id > 0;

-- Create term_i18n only if we just created a term
INSERT INTO term_i18n (id, culture, name)
SELECT @museum_id, 'en', 'Museum (CCO), Cataloging Cultural Objects' FROM DUAL WHERE @museum_exists = 0 AND @museum_id > 0;

-- =====================================================
-- Museum Level of Description Terms (taxonomy_id = 34)
-- =====================================================

-- Object
SET @obj_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Object' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @obj_exists IS NULL;
SET @obj_id = IF(@obj_exists IS NULL, LAST_INSERT_ID(), @obj_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @obj_id, 34, 'en' FROM DUAL WHERE @obj_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @obj_id, 'en', 'Object' FROM DUAL WHERE @obj_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@obj_id, 'level-object');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@obj_id, 'museum', 50);

-- Artwork
SET @art_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Artwork' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @art_exists IS NULL;
SET @art_id = IF(@art_exists IS NULL, LAST_INSERT_ID(), @art_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @art_id, 34, 'en' FROM DUAL WHERE @art_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @art_id, 'en', 'Artwork' FROM DUAL WHERE @art_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@art_id, 'level-artwork');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@art_id, 'museum', 30);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@art_id, 'gallery', 10);

-- Artifact
SET @artf_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Artifact' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @artf_exists IS NULL;
SET @artf_id = IF(@artf_exists IS NULL, LAST_INSERT_ID(), @artf_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @artf_id, 34, 'en' FROM DUAL WHERE @artf_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @artf_id, 'en', 'Artifact' FROM DUAL WHERE @artf_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@artf_id, 'level-artifact');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@artf_id, 'museum', 20);

-- Specimen
SET @spec_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Specimen' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @spec_exists IS NULL;
SET @spec_id = IF(@spec_exists IS NULL, LAST_INSERT_ID(), @spec_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @spec_id, 34, 'en' FROM DUAL WHERE @spec_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @spec_id, 'en', 'Specimen' FROM DUAL WHERE @spec_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@spec_id, 'level-specimen');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@spec_id, 'museum', 60);

-- Installation
SET @inst_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Installation' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @inst_exists IS NULL;
SET @inst_id = IF(@inst_exists IS NULL, LAST_INSERT_ID(), @inst_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @inst_id, 34, 'en' FROM DUAL WHERE @inst_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @inst_id, 'en', 'Installation' FROM DUAL WHERE @inst_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@inst_id, 'level-installation');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@inst_id, 'museum', 40);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@inst_id, 'gallery', 40);

-- 3D Model
SET @model_exists = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='3D Model' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @model_exists IS NULL;
SET @model_id = IF(@model_exists IS NULL, LAST_INSERT_ID(), @model_exists);
INSERT INTO term (id, taxonomy_id, source_culture)
SELECT @model_id, 34, 'en' FROM DUAL WHERE @model_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @model_id, 'en', '3D Model' FROM DUAL WHERE @model_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@model_id, 'level-3d-model');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@model_id, 'museum', 10);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@model_id, 'dam', 60);
