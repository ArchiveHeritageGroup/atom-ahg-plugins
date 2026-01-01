-- Library Level of Description terms (taxonomy_id = 34 is Level of Description)
INSERT IGNORE INTO term (id, taxonomy_id, source_culture) VALUES
(1700, 34, 'en'),
(1701, 34, 'en'),
(1702, 34, 'en'),
(1703, 34, 'en'),
(1704, 34, 'en'),
(1759, 34, 'en');

INSERT INTO term_i18n (id, culture, name) VALUES
(1700, 'en', 'Book'),
(1701, 'en', 'Monograph'),
(1702, 'en', 'Periodical'),
(1703, 'en', 'Journal'),
(1704, 'en', 'Manuscript'),
(1759, 'en', 'Article')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Level of description sector mappings for library
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order, created_at) VALUES
(1700, 'library', 10, NOW()),
(1701, 'library', 20, NOW()),
(1702, 'library', 30, NOW()),
(1703, 'library', 40, NOW()),
(1759, 'library', 45, NOW()),
(1704, 'library', 50, NOW()),
(1161, 'library', 60, NOW());
