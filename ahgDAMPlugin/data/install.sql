-- =====================================================
-- DAM Plugin Install
-- =====================================================

SET @dam_exists = (SELECT COUNT(*) FROM term WHERE code = 'dam' AND taxonomy_id = 70);

INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @dam_exists = 0;

SET @dam_id = LAST_INSERT_ID();

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT @dam_id, 70, 'dam', 'en' FROM DUAL WHERE @dam_exists = 0 AND @dam_id > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT @dam_id, 'en', 'Photo/DAM (IPTC/XMP)' FROM DUAL WHERE @dam_exists = 0 AND @dam_id > 0;

-- =====================================================
-- DAM Level of Description Terms (taxonomy_id = 34)
-- =====================================================

-- Photograph (shared with Gallery)
SET @photo_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Photograph' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @photo_exists IS NULL;
SET @photo_id = IF(@photo_exists IS NULL, LAST_INSERT_ID(), @photo_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @photo_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @photo_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @photo_id, 'en', 'Photograph' FROM DUAL WHERE @photo_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@photo_id, 'photograph');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@photo_id, 'dam', 10);
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@photo_id, 'gallery', 20);

-- Audio
SET @audio_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Audio' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @audio_exists IS NULL;
SET @audio_id = IF(@audio_exists IS NULL, LAST_INSERT_ID(), @audio_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @audio_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @audio_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @audio_id, 'en', 'Audio' FROM DUAL WHERE @audio_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@audio_id, 'audio');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@audio_id, 'dam', 20);

-- Video
SET @video_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Video' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @video_exists IS NULL;
SET @video_id = IF(@video_exists IS NULL, LAST_INSERT_ID(), @video_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @video_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @video_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @video_id, 'en', 'Video' FROM DUAL WHERE @video_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@video_id, 'video');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@video_id, 'dam', 30);

-- Image
SET @image_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Image' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @image_exists IS NULL;
SET @image_id = IF(@image_exists IS NULL, LAST_INSERT_ID(), @image_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @image_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @image_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @image_id, 'en', 'Image' FROM DUAL WHERE @image_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@image_id, 'image');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@image_id, 'dam', 40);

-- Dataset
SET @data_exists = (SELECT id FROM term t JOIN term_i18n ti ON t.id=ti.id WHERE t.taxonomy_id=34 AND ti.name='Dataset' LIMIT 1);
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @data_exists IS NULL;
SET @data_id = IF(@data_exists IS NULL, LAST_INSERT_ID(), @data_exists);
INSERT INTO term (id, taxonomy_id, source_culture, class_name)
SELECT @data_id, 34, 'en', 'QubitTerm' FROM DUAL WHERE @data_exists IS NULL;
INSERT INTO term_i18n (id, culture, name)
SELECT @data_id, 'en', 'Dataset' FROM DUAL WHERE @data_exists IS NULL;
INSERT IGNORE INTO slug (object_id, slug) VALUES (@data_id, 'dataset');
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order) VALUES (@data_id, 'dam', 70);
