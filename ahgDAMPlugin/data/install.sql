-- ahgDAMPlugin - Data Only
-- Version: 1.0.0
-- Tables are created by atom-framework/database/install.sql

-- Register plugin
INSERT IGNORE INTO atom_plugin (name, is_enabled, version, category) VALUES
('ahgDAMPlugin', 1, '1.0.0', 'sector');
<<<<<<< Updated upstream
=======

-- DAM sector levels of description
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order)
SELECT t.id, 'dam', 10 FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Collection' AND ti.culture = 'en' AND t.taxonomy_id = 34 LIMIT 1;
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order)
SELECT t.id, 'dam', 20 FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Series' AND ti.culture = 'en' AND t.taxonomy_id = 34 LIMIT 1;
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order)
SELECT t.id, 'dam', 30 FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'File' AND ti.culture = 'en' AND t.taxonomy_id = 34 LIMIT 1;
INSERT IGNORE INTO level_of_description_sector (term_id, sector, display_order)
SELECT t.id, 'dam', 40 FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Item' AND ti.culture = 'en' AND t.taxonomy_id = 34 LIMIT 1;
>>>>>>> Stashed changes
