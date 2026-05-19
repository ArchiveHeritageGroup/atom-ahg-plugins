-- ==========================================================================
-- Seed candidate-generation config for the authority-resolution engine.
-- Inserted into ahg_settings(setting_group='authority_resolution',
-- setting_key='authority_resolution.candidate_top_n').
--
-- The CandidateGeneratorService reads this key to decide how many ranked
-- candidates to persist per ahg_mention. Falls back to 5 when missing.
--
-- Idempotent (INSERT IGNORE on UNIQUE setting_key). Run manually:
--   MYSQL_PWD="<pw>" mysql --defaults-file=/dev/null -u root archive \
--     < seed_candidate_config.sql
-- ==========================================================================

INSERT IGNORE INTO ahg_settings (
    setting_key,
    setting_group,
    setting_type,
    setting_value,
    description,
    is_sensitive,
    created_at,
    updated_at
) VALUES (
    'authority_resolution.candidate_top_n',
    'authority_resolution',
    'integer',
    '5',
    'Top N candidates surfaced per mention.',
    0,
    NOW(),
    NOW()
);
