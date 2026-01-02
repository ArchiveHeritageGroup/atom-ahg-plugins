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
