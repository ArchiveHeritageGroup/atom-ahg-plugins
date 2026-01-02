-- =====================================================
-- Gallery Plugin Install
-- =====================================================

-- Add Gallery display standard term (taxonomy_id = 70)
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM term WHERE code = 'gallery' AND taxonomy_id = 70);

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT LAST_INSERT_ID(), 70, 'gallery', 'en' FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM term WHERE code = 'gallery' AND taxonomy_id = 70)
AND LAST_INSERT_ID() > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT t.id, 'en', 'Gallery (Spectrum 5.0)'
FROM term t WHERE t.code = 'gallery' AND t.taxonomy_id = 70
AND NOT EXISTS (SELECT 1 FROM term_i18n ti WHERE ti.id = t.id AND ti.culture = 'en');
