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
