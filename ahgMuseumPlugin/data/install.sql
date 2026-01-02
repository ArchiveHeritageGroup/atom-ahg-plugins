-- =====================================================
-- Museum Plugin Install
-- =====================================================

-- Add Museum display standard term (taxonomy_id = 70)
-- Only insert if not exists
INSERT INTO object (class_name, created_at, updated_at)
SELECT 'QubitTerm', NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM term WHERE code = 'museum' AND taxonomy_id = 70);

INSERT INTO term (id, taxonomy_id, code, source_culture)
SELECT LAST_INSERT_ID(), 70, 'museum', 'en' FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM term WHERE code = 'museum' AND taxonomy_id = 70)
AND LAST_INSERT_ID() > 0;

INSERT INTO term_i18n (id, culture, name)
SELECT t.id, 'en', 'Museum (CCO), Cataloging Cultural Objects'
FROM term t WHERE t.code = 'museum' AND t.taxonomy_id = 70
AND NOT EXISTS (SELECT 1 FROM term_i18n ti WHERE ti.id = t.id AND ti.culture = 'en');
