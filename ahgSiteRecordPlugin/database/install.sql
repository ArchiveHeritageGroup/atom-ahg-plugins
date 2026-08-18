-- ahgSiteRecordPlugin - schema
--
-- Replaces RARI's rock_forms `site_records` table (issue #299). That table was
-- never used in production, so this is a redesign rather than a migration.
--
-- Three changes of substance against the legacy shape:
--
--   1. A site record EXTENDS an authority record (UNIQUE actor_id) instead of
--      restating it. The legacy table carried site_name and alternative_name,
--      which the actor already holds as authorized_form_of_name and its other
--      names - two copies of a name drift apart, and then neither is trusted.
--
--   2. Checkbox groups become rows in ahg_site_attribute, not JSON blobs in a
--      text column. The legacy encoding was unqueryable, and it silently dropped
--      three values ("Overhang", "Crayon", "Silcrete") because the form and the
--      processing map disagreed - a bug shape that rows make impossible.
--
--   3. Every table carries created_at/updated_at/created_by/updated_by. The
--      legacy tables had no audit columns at all, so nothing recorded who
--      changed a site's coordinates.
--
-- Collation follows AtoM core (utf8mb4_0900_ai_ci) rather than the older AHG
-- plugin default, so a future string join against actor/actor_i18n cannot fail
-- on a collation mismatch.

CREATE TABLE IF NOT EXISTS `ahg_site_record` (
    `id` INT NOT NULL AUTO_INCREMENT,

    -- The authority record IS the site. One site record per actor.
    `actor_id` INT NOT NULL,

    `site_number` VARCHAR(100) DEFAULT NULL,
    `date_visited` DATE DEFAULT NULL,

    -- Sector-neutral, dropdown-backed. The legacy form hardcoded the nine South
    -- African provinces, which is exactly what stops it serving anyone else.
    `region_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_region',
    `sub_region_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_sub_region',

    -- LOCALITY. Read only through LocalityVisibilityService - never directly.
    `latitude` DECIMAL(10, 7) DEFAULT NULL,
    `longitude` DECIMAL(10, 7) DEFAULT NULL,
    `coordinate_datum` VARCHAR(31) DEFAULT 'WGS84',
    `altitude_m` INT DEFAULT NULL,
    `map_sheet` VARCHAR(63) DEFAULT NULL COMMENT 'e.g. a 1:50,000 sheet reference such as 2328BD',

    -- Locality as it was originally written down: legacy "S 29 12 30" notation,
    -- map sheet strings, site codes. Kept verbatim so structuring the data never
    -- destroys the source. Sensitive - gated with the coordinates.
    `locality_original` TEXT,

    -- Sensitive unless someone has deliberately said otherwise. A new record,
    -- and any record where this was never set, is protected.
    `locality_sensitive` TINYINT(1) NOT NULL DEFAULT 1,

    `aspect_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_aspect',

    -- Replaces the legacy "H x W x D" string packed into a JSON column.
    `height_m` DECIMAL(8, 2) DEFAULT NULL,
    `width_m` DECIMAL(8, 2) DEFAULT NULL,
    `depth_m` DECIMAL(8, 2) DEFAULT NULL,

    `site_description` TEXT,
    `photograph_numbers` VARCHAR(255) DEFAULT NULL,
    `contact_name` VARCHAR(255) DEFAULT NULL,
    `contact_email` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_record_actor` (`actor_id`),
    KEY `idx_site_record_number` (`site_number`),
    KEY `idx_site_record_map_sheet` (`map_sheet`),
    KEY `idx_site_record_region` (`region_code`),
    CONSTRAINT `fk_site_record_actor` FOREIGN KEY (`actor_id`)
        REFERENCES `actor` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Recorded attributes: tradition, site type, damage, surface contents,
-- excavation potential, mineral contents, deposit depth, deposit contents.
--
-- One row per selected value, so a value can be counted, filtered and reported.
-- `taxonomy` names an ahg_dropdown taxonomy and `code` one of its codes; `note`
-- carries the free text behind an "Other" option.
CREATE TABLE IF NOT EXISTS `ahg_site_attribute` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `site_record_id` INT NOT NULL,
    `taxonomy` VARCHAR(63) NOT NULL COMMENT 'ahg_dropdown taxonomy, e.g. site_tradition, site_type, site_damage',
    `code` VARCHAR(63) NOT NULL COMMENT 'ahg_dropdown code within that taxonomy',
    `note` VARCHAR(255) DEFAULT NULL COMMENT 'free text accompanying an "other" selection',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_attribute` (`site_record_id`, `taxonomy`, `code`),
    KEY `idx_site_attribute_taxonomy` (`taxonomy`, `code`),
    CONSTRAINT `fk_site_attribute_record` FOREIGN KEY (`site_record_id`)
        REFERENCES `ahg_site_record` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Field recorders. Linked to an authority record where the person is known to the
-- system, free text where they are not - the legacy field was a single
-- comma-separated string, so it could never answer "what has this person recorded".
CREATE TABLE IF NOT EXISTS `ahg_site_recorder` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `site_record_id` INT NOT NULL,
    `actor_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'as written on the form; kept even when actor_id is set',
    `role_code` VARCHAR(63) DEFAULT NULL COMMENT 'ahg_dropdown taxonomy site_recorder_role',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_site_recorder_record` (`site_record_id`),
    KEY `idx_site_recorder_actor` (`actor_id`),
    CONSTRAINT `fk_site_recorder_record` FOREIGN KEY (`site_record_id`)
        REFERENCES `ahg_site_record` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_site_recorder_actor` FOREIGN KEY (`actor_id`)
        REFERENCES `actor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Seed data merged in from database/seeds/vocabularies.sql on 2026-08-18.
--
-- Seeds sat in database/seeds/ and were never run by the installer, so a clean
-- install created the tables and left them empty. The rock art panel condition
-- template is a worked example of the cost: the plugin installed fine and the
-- template it exists to provide simply was not there.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so install.sql stays safe to
-- re-run; on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ahgSiteRecordPlugin - controlled vocabularies
--
-- Every list the legacy rock_forms application hardcoded in its HTML becomes a
-- row here, so it can be edited in AHG Settings > Dropdown Manager instead of in
-- a template. Values are taken from the RARI form; they are configuration from
-- here on, and an institution recording something other than rock art is expected
-- to change them.
--
-- Filed under the heritage_monuments section, which is where the Dropdown Manager
-- already groups site and monument vocabularies.
--
-- INSERT IGNORE throughout: the unique key is (taxonomy, code), so re-running the
-- seed never disturbs a value an institution has since edited.

-- Region and sub-region replace the legacy Province and District fields. Seeded
-- with the South African provinces because that is what RARI records, but the
-- field names carry no country, so another deployment reseeds its own.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_region', 'Site region', 'heritage_monuments', 'eastern_cape',  'Eastern Cape',  10),
('site_region', 'Site region', 'heritage_monuments', 'free_state',    'Free State',    20),
('site_region', 'Site region', 'heritage_monuments', 'gauteng',       'Gauteng',       30),
('site_region', 'Site region', 'heritage_monuments', 'kwazulu_natal', 'KwaZulu-Natal', 40),
('site_region', 'Site region', 'heritage_monuments', 'limpopo',       'Limpopo',       50),
('site_region', 'Site region', 'heritage_monuments', 'mpumalanga',    'Mpumalanga',    60),
('site_region', 'Site region', 'heritage_monuments', 'northern_cape', 'Northern Cape', 70),
('site_region', 'Site region', 'heritage_monuments', 'north_west',    'North West',    80),
('site_region', 'Site region', 'heritage_monuments', 'western_cape',  'Western Cape',  90);

-- Cultural tradition.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_tradition', 'Site tradition', 'heritage_monuments', 'san',       'San',        10),
('site_tradition', 'Site tradition', 'heritage_monuments', 'khoekhoen', 'Khoekhoen',  20),
('site_tradition', 'Site tradition', 'heritage_monuments', 'bantu',     'Bantu',      30),
('site_tradition', 'Site tradition', 'heritage_monuments', 'other',     'Other',      90);

-- Site type. "overhang" is here deliberately: it existed in the legacy form but
-- was missing from the processing map, so selecting it silently discarded the
-- value. As a row it cannot go missing.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_type', 'Site type', 'heritage_monuments', 'cave',      'Cave',      10),
('site_type', 'Site type', 'heritage_monuments', 'shelter',   'Shelter',   20),
('site_type', 'Site type', 'heritage_monuments', 'rock_face', 'Rock face', 30),
('site_type', 'Site type', 'heritage_monuments', 'boulder',   'Boulder',   40),
('site_type', 'Site type', 'heritage_monuments', 'overhang',  'Overhang',  50),
('site_type', 'Site type', 'heritage_monuments', 'open',      'Open',      60);

-- Observed damage.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_damage', 'Site damage', 'heritage_monuments', 'water',      'Water',      10),
('site_damage', 'Site damage', 'heritage_monuments', 'lichen',     'Lichen',     20),
('site_damage', 'Site damage', 'heritage_monuments', 'salts',      'Salts',      30),
('site_damage', 'Site damage', 'heritage_monuments', 'dust',       'Dust',       40),
('site_damage', 'Site damage', 'heritage_monuments', 'animals',    'Animals',    50),
('site_damage', 'Site damage', 'heritage_monuments', 'flaking',    'Flaking',    60),
('site_damage', 'Site damage', 'heritage_monuments', 'klipsweet',  'Klipsweet',  70),
('site_damage', 'Site damage', 'heritage_monuments', 'graffiti',   'Graffiti',   80),
('site_damage', 'Site damage', 'heritage_monuments', 'vegetation', 'Vegetation', 90);

-- Surface contents.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'stone_tools',       'Stone tools',        10),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'pottery',           'Pottery',            20),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'marine_shells',     'Marine shells',      30),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'glass',             'Glass',              40),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'ochre',             'Ochre',              50),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'stone_walling',     'Stone walling',      60),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'bones',             'Bones',              70),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'ostrich_egg_shell', 'Ostrich egg shells', 80),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'ash',               'Ash',                90),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'metals',            'Metals',            100),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'grindstone',        'Grindstone',        110),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'beads',             'Beads',             120),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'bedding',           'Bedding',           130),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'dung',              'Dung',              140),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'other',             'Other',             900);

-- Excavation potential.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_excavation_potential', 'Site excavation potential', 'heritage_monuments', 'high',   'High',   10),
('site_excavation_potential', 'Site excavation potential', 'heritage_monuments', 'medium', 'Medium', 20),
('site_excavation_potential', 'Site excavation potential', 'heritage_monuments', 'low',    'Low',    30);

-- Mineral and rock contents. "silcrete" is seeded with a real label: the legacy
-- processing map gave it an empty string, so it stored a blank.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'quartz',    'Quartz',    10),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'quartzite', 'Quartzite', 20),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'chert',     'Chert',     30),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'hornfels',  'Hornfels',  40),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'ccs',       'CCS',       50),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'silcrete',  'Silcrete',  60);

-- Deposit depth and contents.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_deposit_depth', 'Site deposit depth', 'heritage_monuments', '0_10cm',   '0-10 cm',   10),
('site_deposit_depth', 'Site deposit depth', 'heritage_monuments', '10_20cm',  '10-20 cm',  20),
('site_deposit_depth', 'Site deposit depth', 'heritage_monuments', '20_50cm',  '20-50 cm+', 30);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'esa',        'ESA',        10),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'msa',        'MSA',        20),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'lsa',        'LSA',        30),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'burial',     'Burial',     40),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'historical', 'Historical', 50),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'other',      'Other',      900);

-- Aspect. Free text in the legacy form, which made it unreportable.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_aspect', 'Site aspect', 'heritage_monuments', 'n',  'North',      10),
('site_aspect', 'Site aspect', 'heritage_monuments', 'ne', 'North-east', 20),
('site_aspect', 'Site aspect', 'heritage_monuments', 'e',  'East',       30),
('site_aspect', 'Site aspect', 'heritage_monuments', 'se', 'South-east', 40),
('site_aspect', 'Site aspect', 'heritage_monuments', 's',  'South',      50),
('site_aspect', 'Site aspect', 'heritage_monuments', 'sw', 'South-west', 60),
('site_aspect', 'Site aspect', 'heritage_monuments', 'w',  'West',       70),
('site_aspect', 'Site aspect', 'heritage_monuments', 'nw', 'North-west', 80),
('site_aspect', 'Site aspect', 'heritage_monuments', 'var','Variable',   90);

-- Recorder role.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'recorder',     'Recorder',     10),
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'photographer', 'Photographer', 20),
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'surveyor',     'Surveyor',     30),
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'assistant',    'Assistant',    40);

-- ---------------------------------------------------------------------------
-- Seed data merged in from database/seeds/rock_art_panel_template.sql on 2026-08-18.
--
-- Seeds sat in database/seeds/ and were never run by the installer, so a clean
-- install created the tables and left them empty. The rock art panel condition
-- template is a worked example of the cost: the plugin installed fine and the
-- template it exists to provide simply was not there.
--
-- Unguarded INSERTs are rewritten to INSERT IGNORE so install.sql stays safe to
-- re-run; on a fresh database the result is identical.
-- ---------------------------------------------------------------------------

-- ahgSiteRecordPlugin - rock art panel condition assessment
--
-- The legacy rock_forms "Rock Image Condition Assessment" form, expressed as a
-- template in ahgConditionPlugin's existing condition system rather than as a
-- third set of condition tables.
--
-- ahgConditionPlugin already carries a data-driven template builder
-- (spectrum_condition_template -> _section -> _field, answered into
-- spectrum_condition_check_data) with photos, export and Spectrum reporting. The
-- codebase also already carries TWO overlapping condition schemas
-- (spectrum_condition_* and condition_report). Adding a third for rock art would
-- compound that, so this is seed data only: nothing in ahgConditionPlugin is
-- modified, and an assessment repeats over time as an ordinary condition check.
--
-- An assessment attaches to an information_object, so a panel is a description
-- under its site's authority record.
--
-- Re-runnable: guarded on the template code, and the sections and fields resolve
-- their parents by name rather than by a hardcoded id.

INSERT IGNORE INTO `spectrum_condition_template` (`name`, `code`, `material_type`, `description`, `is_active`, `is_default`, `sort_order`)
SELECT 'Rock art panel condition assessment', 'rock_art_panel', 'rock_art_panel',
       'Condition assessment for a rock art panel or engraved surface. Replaces the RARI rock_forms assessment (issue #299).',
       1, 0, 10
WHERE NOT EXISTS (SELECT 1 FROM `spectrum_condition_template` WHERE `code` = 'rock_art_panel');

-- Sections -------------------------------------------------------------------

INSERT IGNORE INTO `spectrum_condition_template_section` (`template_id`, `name`, `description`, `sort_order`, `is_required`)
SELECT t.id, s.name, s.description, s.sort_order, s.is_required
FROM `spectrum_condition_template` t
JOIN (
    SELECT 'Assessment'              AS name, 'Level, conditions and documentation at the time of assessment' AS description, 10 AS sort_order, 1 AS is_required
    UNION ALL SELECT 'Panel',        'Physical description of the panel and its imagery',                     20, 0
    UNION ALL SELECT 'Deterioration', 'Observed natural and human deterioration',                             30, 0
    UNION ALL SELECT 'Outcome',      'Overall condition, treatment history and recommendations',              40, 1
) s
WHERE t.code = 'rock_art_panel'
  AND NOT EXISTS (
      SELECT 1 FROM `spectrum_condition_template_section` x
      WHERE x.template_id = t.id AND x.name = s.name
  );

-- Fields ---------------------------------------------------------------------
--
-- Vocabularies that were hardcoded <select> options in the legacy form are
-- carried here as the field's own options. The open-ended lists (weather,
-- substrate, method, colour) stay free text as they were, because they were never
-- constrained and constraining them now would reject the terms recorders use.

INSERT IGNORE INTO `spectrum_condition_template_field`
    (`section_id`, `field_name`, `field_label`, `field_type`, `options`, `help_text`, `is_required`, `sort_order`)
SELECT sec.id, f.field_name, f.field_label, f.field_type, f.options, f.help_text, f.is_required, f.sort_order
FROM `spectrum_condition_template` t
JOIN `spectrum_condition_template_section` sec ON sec.template_id = t.id
JOIN (
    SELECT 'Assessment' AS section, 'assessment_level' AS field_name, 'Assessment level' AS field_label, 'select' AS field_type,
           CAST('["Basic","Intermediate","Detailed"]' AS JSON) AS options, NULL AS help_text, 1 AS is_required, 10 AS sort_order
    UNION ALL SELECT 'Assessment', 'time_on_site',    'Time on site',            'text',     NULL, NULL, 0, 20
    UNION ALL SELECT 'Assessment', 'weather',         'Weather',                 'text',     NULL, 'Conditions during the assessment', 0, 30
    UNION ALL SELECT 'Assessment', 'existing_doc',    'Existing documentation',  'textarea', NULL, 'Earlier records for this panel, e.g. ARAL', 0, 40
    UNION ALL SELECT 'Assessment', 'managing_agency', 'Managing agency',         'text',     NULL, NULL, 0, 50

    UNION ALL SELECT 'Panel', 'panel_number', 'Panel number', 'text', NULL, NULL, 1, 10
    UNION ALL SELECT 'Panel', 'image_type',   'Type',         'select',
           CAST('["Petroglyph","Pictograph"]' AS JSON), NULL, 0, 20
    UNION ALL SELECT 'Panel', 'method',    'Method',    'text',     NULL, 'Technique used to make the images', 0, 30
    UNION ALL SELECT 'Panel', 'colour',    'Colour(s)', 'text',     NULL, NULL, 0, 40
    UNION ALL SELECT 'Panel', 'aspect',    'Aspect',    'text',     NULL, NULL, 0, 50
    UNION ALL SELECT 'Panel', 'angle',     'Angle',     'text',     NULL, NULL, 0, 60
    UNION ALL SELECT 'Panel', 'substrate', 'Substrate', 'text',     NULL, NULL, 0, 70
    UNION ALL SELECT 'Panel', 'overlays',  'Overlays',  'text',     NULL, NULL, 0, 80
    UNION ALL SELECT 'Panel', 'topography','Topography and general site description', 'textarea', NULL, NULL, 0, 90
    UNION ALL SELECT 'Panel', 'general_description', 'General description of the images and their condition', 'textarea', NULL, NULL, 0, 100

    -- Both deterioration checklists are multiselect, so every value is stored
    -- discretely. The legacy form packed them into a JSON blob and lost "Crayon"
    -- entirely, because a stray tab in the processing key never matched the input.
    UNION ALL SELECT 'Deterioration', 'natural_deterioration', 'Natural deterioration', 'multiselect',
           CAST('["Wash zones","Seeps","Damp areas","Soluble salts","Insoluble salts","Cleaving","Exfoliation","Granulation","Abrasion","Wind erosion","Dust","Vegetation","Lichen","Fungi","Mould","Algae","Bacteria","Animals","Birds","Bats","Insects"]' AS JSON),
           NULL, 0, 10
    UNION ALL SELECT 'Deterioration', 'natural_other', 'Other natural deterioration', 'text', NULL, NULL, 0, 20
    UNION ALL SELECT 'Deterioration', 'cultural_deterioration', 'Artificial and cultural deterioration', 'multiselect',
           CAST('["Graffiti","Incised or carved","Scratched","Abraded","Spray painted","Other paint","Pencil","Marker pen","Crayon","Charcoal","Chalk","Ball point","Other material","Gun shot","Climbing chalk","Theft","Abrasion","Litter","Camp fires","Staining"]' AS JSON),
           NULL, 0, 30
    UNION ALL SELECT 'Deterioration', 'cultural_other', 'Other cultural deterioration', 'text', NULL, NULL, 0, 40
    UNION ALL SELECT 'Deterioration', 'samples_taken', 'Samples taken', 'radio',
           CAST('["Yes","No"]' AS JSON), NULL, 0, 50

    UNION ALL SELECT 'Outcome', 'site_condition_value', 'Site condition assessment value', 'select',
           CAST('["Good","Fair","Poor","Destroyed","Unknown"]' AS JSON), 'ASMIS condition scale', 1, 10
    UNION ALL SELECT 'Outcome', 'past_treatment',  'Past treatments',  'radio',
           CAST('["Yes","No"]' AS JSON), NULL, 0, 20
    UNION ALL SELECT 'Outcome', 'recommendations', 'Recommendations',  'textarea', NULL, NULL, 0, 30
    UNION ALL SELECT 'Outcome', 'other_observation', 'Other observations', 'textarea', NULL, NULL, 0, 40
    UNION ALL SELECT 'Outcome', 'general_comments', 'General comments', 'textarea', NULL, NULL, 0, 50
    UNION ALL SELECT 'Outcome', 'assessor_name',   'Assessor',         'text',     NULL, NULL, 1, 60
    UNION ALL SELECT 'Outcome', 'assessor_affiliation', 'Affiliation',  'text',    NULL, NULL, 1, 70
    UNION ALL SELECT 'Outcome', 'assessor_contact', 'Assessor contact', 'text',    NULL, NULL, 0, 80
) f ON f.section = sec.name
WHERE t.code = 'rock_art_panel'
  AND NOT EXISTS (
      SELECT 1 FROM `spectrum_condition_template_field` x
      WHERE x.section_id = sec.id AND x.field_name = f.field_name
  );
