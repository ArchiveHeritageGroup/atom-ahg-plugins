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

INSERT INTO `spectrum_condition_template` (`name`, `code`, `material_type`, `description`, `is_active`, `is_default`, `sort_order`)
SELECT 'Rock art panel condition assessment', 'rock_art_panel', 'rock_art_panel',
       'Condition assessment for a rock art panel or engraved surface. Replaces the RARI rock_forms assessment (issue #299).',
       1, 0, 10
WHERE NOT EXISTS (SELECT 1 FROM `spectrum_condition_template` WHERE `code` = 'rock_art_panel');

-- Sections -------------------------------------------------------------------

INSERT INTO `spectrum_condition_template_section` (`template_id`, `name`, `description`, `sort_order`, `is_required`)
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

INSERT INTO `spectrum_condition_template_field`
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
