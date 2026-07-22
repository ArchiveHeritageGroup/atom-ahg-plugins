-- ============================================================================
-- Archaeology cataloguing profile
--
-- Controlled vocabularies and typed custom fields for archaeological finds.
--
-- Design notes:
--   * Field groups are PROPERTIES (Measurements / Description / Material
--     detail), not object types. custom_field_definition has no visibility
--     condition, so every group renders on every record - grouping by object
--     type would show 'Number of beads' on a grindstone, and would leave
--     glass, clay, bone and metal finds with no fields at all.
--   * Exact measurements ALSO live in ISAD(G) 3.1.5 Extent and medium as
--     readable prose. These typed fields carry what is filtered and sorted
--     on. The duplication is deliberate: prose to read, fields to query.
--
-- Requires ahgCustomFieldsPlugin enabled. Idempotent - safe to re-run.
-- ============================================================================

-- Controlled vocabularies (ahg_dropdown)
INSERT IGNORE INTO `ahg_dropdown`
  (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`, `is_active`) VALUES
  ('arch_bead_series', 'Bead series', 'series_a', 'Series A - Indo-Pacific drawn', 10, 0, 1),
  ('arch_bead_series', 'Bead series', 'series_b', 'Series B - wound', 20, 0, 1),
  ('arch_bead_series', 'Bead series', 'series_c', 'Series C - moulded', 30, 0, 1),
  ('arch_bead_series', 'Bead series', 'oes', 'Ostrich eggshell', 40, 0, 1),
  ('arch_bead_series', 'Bead series', 'unclassified', 'Unclassified', 50, 0, 1),
  ('arch_ceramic_fabric', 'Ceramic fabric', 'sandy', 'Sandy', 10, 0, 1),
  ('arch_ceramic_fabric', 'Ceramic fabric', 'quartz_tempered', 'Quartz-tempered', 20, 0, 1),
  ('arch_ceramic_fabric', 'Ceramic fabric', 'grog_tempered', 'Grog-tempered', 30, 0, 1),
  ('arch_ceramic_fabric', 'Ceramic fabric', 'fine_untempered', 'Fine, untempered', 40, 0, 1),
  ('arch_ceramic_fabric', 'Ceramic fabric', 'coarse', 'Coarse', 50, 0, 1),
  ('arch_ceramic_fabric', 'Ceramic fabric', 'indeterminate', 'Indeterminate', 60, 0, 1),
  ('arch_completeness', 'Completeness', 'complete', 'Complete', 100, 0, 1),
  ('arch_completeness', 'Completeness', 'near_complete', 'Near complete', 110, 0, 1),
  ('arch_completeness', 'Completeness', 'fragment', 'Fragment', 120, 0, 1),
  ('arch_completeness', 'Completeness', 'indeterminate', 'Indeterminate', 130, 0, 1),
  ('arch_cortex', 'Cortex cover', 'none', 'None', 10, 0, 1),
  ('arch_cortex', 'Cortex cover', '1_25', '1-25%', 20, 0, 1),
  ('arch_cortex', 'Cortex cover', '26_50', '26-50%', 30, 0, 1),
  ('arch_cortex', 'Cortex cover', '51_75', '51-75%', 40, 0, 1),
  ('arch_cortex', 'Cortex cover', '76_100', '76-100%', 50, 0, 1),
  ('arch_firing', 'Firing atmosphere', 'oxidised', 'Oxidised', 10, 0, 1),
  ('arch_firing', 'Firing atmosphere', 'reduced', 'Reduced', 20, 0, 1),
  ('arch_firing', 'Firing atmosphere', 'oxidised_reduced_core', 'Oxidised exterior, reduced core', 30, 0, 1),
  ('arch_firing', 'Firing atmosphere', 'indeterminate', 'Indeterminate', 40, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'flake', 'Flake', 10, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'blade', 'Blade', 20, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'core', 'Core', 30, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'scraper', 'Scraper', 40, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'point', 'Point', 50, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'grindstone', 'Grindstone', 60, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'hammerstone', 'Hammerstone', 70, 0, 1),
  ('arch_lithic_type', 'Lithic type', 'debitage', 'Debitage', 80, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'drawn', 'Drawn', 10, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'wound', 'Wound', 20, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'moulded', 'Moulded', 30, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'ground', 'Ground', 40, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'folded', 'Folded', 50, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'indeterminate', 'Indeterminate', 60, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'knapped', 'Knapped', 100, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'hand_built', 'Hand-built', 110, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'wheel_thrown', 'Wheel-thrown', 120, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'cast', 'Cast', 130, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'forged', 'Forged', 140, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'carved', 'Carved', 150, 0, 1),
  ('arch_manufacture', 'Manufacture technique', 'pecked', 'Pecked', 160, 0, 1),
  ('arch_opacity', 'Opacity', 'opaque', 'Opaque', 10, 0, 1),
  ('arch_opacity', 'Opacity', 'translucent', 'Translucent', 20, 0, 1),
  ('arch_opacity', 'Opacity', 'transparent', 'Transparent', 30, 0, 1),
  ('arch_raw_material', 'Raw material', 'quartz', 'Quartz', 10, 0, 1),
  ('arch_raw_material', 'Raw material', 'quartzite', 'Quartzite', 20, 0, 1),
  ('arch_raw_material', 'Raw material', 'chert', 'Chert', 30, 0, 1),
  ('arch_raw_material', 'Raw material', 'chalcedony', 'Chalcedony', 40, 0, 1),
  ('arch_raw_material', 'Raw material', 'dolerite', 'Dolerite', 50, 0, 1),
  ('arch_raw_material', 'Raw material', 'hornfels', 'Hornfels', 60, 0, 1),
  ('arch_raw_material', 'Raw material', 'other', 'Other', 70, 0, 1),
  ('arch_surface_treatment', 'Surface treatment', 'burnished', 'Burnished', 10, 0, 1),
  ('arch_surface_treatment', 'Surface treatment', 'smoothed', 'Smoothed', 20, 0, 1),
  ('arch_surface_treatment', 'Surface treatment', 'slipped', 'Slipped', 30, 0, 1),
  ('arch_surface_treatment', 'Surface treatment', 'rusticated', 'Rusticated', 40, 0, 1),
  ('arch_surface_treatment', 'Surface treatment', 'none', 'None', 50, 0, 1);

-- Typed field definitions, grouped by property
INSERT IGNORE INTO `custom_field_definition`
  (`field_key`, `field_label`, `field_type`, `entity_type`, `field_group`,
   `dropdown_taxonomy`, `is_required`, `is_searchable`, `is_visible_public`,
   `is_visible_edit`, `is_repeatable`, `help_text`, `sort_order`, `is_active`) VALUES
  ('count', 'Count', 'number', 'informationobject', 'Measurements', NULL, 0, 1, 1, 1, 0, 'Number of items or fragments in this find.', 10, 1),
  ('length_mm', 'Length (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 0, 1, 1, 0, NULL, 20, 1),
  ('width_mm', 'Width (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 0, 1, 1, 0, NULL, 30, 1),
  ('thickness_mm', 'Thickness (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 0, 1, 1, 0, NULL, 40, 1),
  ('diameter_min_mm', 'Diameter, minimum (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 1, 1, 1, 0, 'For assemblages, the smallest in the group.', 50, 1),
  ('diameter_max_mm', 'Diameter, maximum (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 1, 1, 1, 0, 'For assemblages, the largest in the group.', 60, 1),
  ('perforation_mm', 'Perforation diameter (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 1, 1, 1, 0, 'Beads, pendants and other pierced objects.', 70, 1),
  ('rim_diameter_mm', 'Rim diameter (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 0, 1, 1, 0, 'Vessels, where a rim allows estimation.', 80, 1),
  ('wall_thickness_mm', 'Wall thickness (mm)', 'number', 'informationobject', 'Measurements', NULL, 0, 0, 1, 1, 0, 'Vessels and vessel fragments.', 90, 1),
  ('weight_g', 'Weight (g)', 'number', 'informationobject', 'Measurements', NULL, 0, 0, 1, 1, 0, 'Total, or mean per item for assemblages.', 100, 1),
  ('colour', 'Colour', 'text', 'informationobject', 'Description', NULL, 0, 1, 1, 1, 0, 'Separate multiple colours with a semicolon.', 110, 1),
  ('opacity', 'Opacity', 'dropdown', 'informationobject', 'Description', 'arch_opacity', 0, 1, 1, 1, 0, 'Glass, glaze and translucent stone.', 120, 1),
  ('manufacture_technique', 'Manufacture technique', 'dropdown', 'informationobject', 'Description', 'arch_manufacture', 0, 1, 1, 1, 0, NULL, 130, 1),
  ('surface_treatment', 'Surface treatment', 'dropdown', 'informationobject', 'Description', 'arch_surface_treatment', 0, 1, 1, 1, 0, NULL, 140, 1),
  ('decoration', 'Decoration', 'text', 'informationobject', 'Description', NULL, 0, 1, 1, 1, 0, 'Technique and motif, briefly.', 150, 1),
  ('completeness', 'Completeness', 'dropdown', 'informationobject', 'Description', 'arch_completeness', 0, 1, 1, 1, 0, NULL, 160, 1),
  ('fabric', 'Fabric / temper', 'dropdown', 'informationobject', 'Material detail', 'arch_ceramic_fabric', 0, 1, 1, 1, 0, 'Ceramics.', 170, 1),
  ('firing_atmosphere', 'Firing atmosphere', 'dropdown', 'informationobject', 'Material detail', 'arch_firing', 0, 1, 1, 1, 0, 'Ceramics.', 180, 1),
  ('raw_material', 'Raw material', 'dropdown', 'informationobject', 'Material detail', 'arch_raw_material', 0, 1, 1, 1, 0, 'Stone and lithics.', 190, 1),
  ('cortex', 'Cortex cover', 'dropdown', 'informationobject', 'Material detail', 'arch_cortex', 0, 0, 1, 1, 0, 'Lithics.', 200, 1),
  ('lithic_type', 'Lithic type', 'dropdown', 'informationobject', 'Material detail', 'arch_lithic_type', 0, 1, 1, 1, 0, 'Lithics.', 210, 1),
  ('bead_series', 'Bead series', 'dropdown', 'informationobject', 'Material detail', 'arch_bead_series', 0, 1, 1, 1, 0, 'Beads.', 220, 1);

-- Site location (GPS). is_visible_public = 0 on EVERY field: precise coordinates
-- of archaeological sites are restricted (looting risk), so the raw numbers are
-- never shown to guests through the custom-field panel. The Site Location map
-- panel (ahgCustomFieldsPlugin/templates/display/_site_location_map.php) renders
-- exact coordinates to authenticated staff and a generalised location to the
-- public, honouring the `location_sensitive` flag (treated as ON unless a record
-- explicitly sets it off). Belongs on Site-level records; empty elsewhere.
INSERT IGNORE INTO `custom_field_definition`
  (`field_key`, `field_label`, `field_type`, `entity_type`, `field_group`,
   `dropdown_taxonomy`, `is_required`, `is_searchable`, `is_visible_public`,
   `is_visible_edit`, `is_repeatable`, `help_text`, `sort_order`, `is_active`) VALUES
  ('site_latitude', 'Latitude', 'number', 'informationobject', 'Location', NULL, 0, 0, 0, 1, 0, 'Decimal degrees, e.g. -29.0123 (south is negative). Precise coordinates are shown to staff only.', 300, 1),
  ('site_longitude', 'Longitude', 'number', 'informationobject', 'Location', NULL, 0, 0, 0, 1, 0, 'Decimal degrees, e.g. 30.8456 (east is positive).', 310, 1),
  ('coordinate_datum', 'Datum / CRS', 'text', 'informationobject', 'Location', NULL, 0, 0, 0, 1, 0, 'Geodetic datum, e.g. WGS84 (default), Cape (Clarke 1880) or Hartebeesthoek94. Legacy SA data is often Cape datum - recording it avoids ~200 m shifts.', 320, 1),
  ('location_sensitive', 'Restrict precise location', 'boolean', 'informationobject', 'Location', NULL, 0, 0, 0, 1, 0, 'When on (the default), the public sees only a generalised location; staff see exact coordinates. Turn off only for sites whose exact position is already public (e.g. a gazetted monument).', 330, 1);

-- Archaeology UI labels. Two separate layers: `setting` scope ui_label holds
-- the singular noun, `menu_i18n` holds the plural used in the Browse menu.
-- Changing only the settings leaves 'Archival descriptions' in the menu.
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Record'
  WHERE s.`name` = 'informationobject' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Record count:&nbsp;'
  WHERE s.`name` = 'descriptioncount' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Institution'
  WHERE s.`name` = 'repository' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Institution count:&nbsp;'
  WHERE s.`name` = 'repositorycount' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Collection'
  WHERE s.`name` = 'collection' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'People and organisations'
  WHERE s.`name` = 'actor' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'People and organisations count:&nbsp;'
  WHERE s.`name` = 'authorityrecordcount' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Relationships'
  WHERE s.`name` = 'authority_record_relationships' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Storage location'
  WHERE s.`name` = 'physicalobject' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Creator / excavator'
  WHERE s.`name` = 'creator' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';
UPDATE `setting_i18n` si JOIN `setting` s ON s.id = si.id
  SET si.`value` = 'Search this collection'
  WHERE s.`name` = 'institutionSearchHoldings' AND s.`scope` = 'ui_label' AND si.`culture` = 'en';

UPDATE `menu_i18n` mi JOIN `menu` m ON m.id = mi.id
  SET mi.`label` = 'Records'
  WHERE m.`name` = 'browseInformationObjects' AND mi.`culture` = 'en';
UPDATE `menu_i18n` mi JOIN `menu` m ON m.id = mi.id
  SET mi.`label` = 'People and organisations'
  WHERE m.`name` = 'browseActors' AND mi.`culture` = 'en';
UPDATE `menu_i18n` mi JOIN `menu` m ON m.id = mi.id
  SET mi.`label` = 'Institutions'
  WHERE m.`name` = 'browseRepositories' AND mi.`culture` = 'en';
UPDATE `menu_i18n` mi JOIN `menu` m ON m.id = mi.id
  SET mi.`label` = 'Images and media'
  WHERE m.`name` = 'browseDigitalObjects' AND mi.`culture` = 'en';

-- siteBaseUrl reads back per culture with no source_culture fallback, so a
-- URL set in English is empty in every other language and the header nags
-- admins to set one that is already set. Framework equivalent:
--   php bin/atom tools:settings --sync-cultures
INSERT IGNORE INTO `setting_i18n` (`id`, `culture`, `value`)
SELECT s.id, l.name, v.value FROM `setting` s
  JOIN `setting_i18n` v ON v.id = s.id AND v.culture = 'en'
  JOIN `setting` l ON l.scope = 'i18n_languages'
  WHERE s.name = 'siteBaseUrl';

-- ----------------------------------------------------------------------------
-- Browse menu entry for the find search.
--
-- `menu` is a nested set, so a slot must be opened under Browse before the row
-- is inserted - writing arbitrary lft/rgt corrupts site-wide navigation.
-- ----------------------------------------------------------------------------
SET @browse := (SELECT id FROM `menu` WHERE `name` = 'browse' LIMIT 1);
SET @exists := (SELECT COUNT(*) FROM `menu` WHERE `name` = 'browseFindSearch');
SET @rgt := (SELECT rgt FROM `menu` WHERE id = @browse);

UPDATE `menu` SET rgt = rgt + 2 WHERE @exists = 0 AND rgt >= @rgt;
UPDATE `menu` SET lft = lft + 2 WHERE @exists = 0 AND lft > @rgt;

INSERT INTO `menu` (`parent_id`, `name`, `path`, `lft`, `rgt`, `source_culture`, `created_at`, `updated_at`)
SELECT @browse, 'browseFindSearch', 'customFields/search', @rgt, @rgt + 1, 'en', NOW(), NOW()
  FROM DUAL WHERE @exists = 0;

INSERT INTO `menu_i18n` (`id`, `culture`, `label`)
SELECT LAST_INSERT_ID(), 'en', 'Find search' FROM DUAL WHERE @exists = 0;

-- ----------------------------------------------------------------------------
-- ISAD element labels, registered in the ui_label scope.
--
-- Values are the standard ISAD wording, so nothing changes visually until an
-- administrator edits them at Admin > Interface labels. The point is that the
-- element names become configurable at all - ahg_label() in the theme reads
-- app_ui_label_<key> and falls back to the ISAD term when unset.
--
-- ISAD names the documentary record; archaeology names the material. An
-- archaeologist reads "Extent and medium" and expects material and dimensions.
-- Rename presentation only - do NOT point a renamed label at a field that means
-- something else. ISAD's Date is when the record was written, not the age of
-- the layer; stratigraphic date belongs in the Phase vocabulary.
-- ----------------------------------------------------------------------------
INSERT INTO `setting` (`name`, `scope`, `editable`, `deleteable`, `source_culture`)
SELECT * FROM (
  SELECT 'isad_scope_and_content' AS n, 'ui_label' AS s, 1 AS e, 0 AS d, 'en' AS c UNION ALL
  SELECT 'isad_extent_and_medium', 'ui_label', 1, 0, 'en' UNION ALL
  SELECT 'isad_archival_history',  'ui_label', 1, 0, 'en'
) x WHERE NOT EXISTS (
  SELECT 1 FROM `setting` s2 WHERE s2.`name` = x.n AND s2.`scope` = 'ui_label'
);

INSERT IGNORE INTO `setting_i18n` (`id`, `culture`, `value`)
SELECT s.id, l.name,
  CASE s.name
    WHEN 'isad_scope_and_content' THEN 'Scope and content'
    WHEN 'isad_extent_and_medium' THEN 'Extent and medium'
    WHEN 'isad_archival_history'  THEN 'Archival history'
  END
FROM `setting` s
  JOIN `setting` l ON l.scope = 'i18n_languages'
WHERE s.scope = 'ui_label'
  AND s.name IN ('isad_scope_and_content','isad_extent_and_medium','isad_archival_history');
