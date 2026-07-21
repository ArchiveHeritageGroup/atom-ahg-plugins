-- ============================================================================
-- Actor name-access-point resolver - mappings for existing installs
-- ahgFormsPlugin migration 003
--
-- Adds the actor_relation target for the two fields that name people without
-- being creators: Dublin Core "contributor" and Photo "personsDepicted".
-- FormSubmitService writes relation rows (subject_id = description,
-- object_id = actor, type_id = 161 QubitTerm::NAME_ACCESS_POINT_ID) and skips
-- any actor already attached to the description through an event.
--
-- Idempotent: each INSERT is guarded by a NOT EXISTS on the same mapping.
-- ============================================================================

INSERT INTO ahg_form_field_mapping (field_id, target_table, target_column, target_type_id, transformation, transformation_config, is_i18n)
SELECT f.id, 'actor_relation', 'actor', 161, NULL, NULL, 0
FROM ahg_form_field f
JOIN ahg_form_template t ON t.id = f.template_id
WHERE t.name = 'Dublin Core Simple'
  AND f.field_name = 'contributor'
  AND NOT EXISTS (
      SELECT 1 FROM ahg_form_field_mapping m
      WHERE m.field_id = f.id AND m.target_table = 'actor_relation'
  );

INSERT INTO ahg_form_field_mapping (field_id, target_table, target_column, target_type_id, transformation, transformation_config, is_i18n)
SELECT f.id, 'actor_relation', 'actor', 161, NULL, NULL, 0
FROM ahg_form_field f
JOIN ahg_form_template t ON t.id = f.template_id
WHERE t.name = 'Photo Collection Item'
  AND f.field_name = 'personsDepicted'
  AND NOT EXISTS (
      SELECT 1 FROM ahg_form_field_mapping m
      WHERE m.field_id = f.id AND m.target_table = 'actor_relation'
  );
