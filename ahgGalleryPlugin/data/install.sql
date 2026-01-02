-- =====================================================
-- Gallery Plugin Install
-- =====================================================

SET @gallery_exists = (SELECT COUNT(*) FROM term WHERE code = 'gallery' AND taxonomy_id = 70);

INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL WHERE @gallery_exists = 0;

SET @gallery_id = LAST_INSERT_ID();

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT @gallery_id, 70, 'gallery', 'en' FROM DUAL WHERE @gallery_exists = 0 AND @gallery_id > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT @gallery_id, 'en', 'Gallery (Spectrum 5.0)' FROM DUAL WHERE @gallery_exists = 0 AND @gallery_id > 0;
