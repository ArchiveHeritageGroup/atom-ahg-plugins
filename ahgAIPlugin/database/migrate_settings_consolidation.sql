-- ============================================================================
-- AI Settings Consolidation Migration
-- Date: 2026-03-08
--
-- GOAL: Consolidate duplicate AI settings into single source of truth.
--
-- Current state (DUPLICATION):
--   1. ahg_ai_settings (feature+setting_key) — ahgAIPlugin's own table
--   2. ahg_ner_settings (setting_key) — legacy table used by ahgSettingsPlugin UI
--
-- Target state:
--   ahg_ai_settings is the SINGLE source of truth.
--   ahg_ner_settings retained read-only for backward compatibility.
--   NerService reads from ahg_ai_settings first, ahg_ner_settings fallback.
--
-- API KEY REMOVAL:
--   API keys and tokens are managed by a SEPARATE external token system.
--   The api_key fields below are kept for internal service-to-service auth only
--   (e.g., AtoM → AI server on same network). Client-facing API keys are
--   NOT generated or managed by AtoM Heratio.
-- ============================================================================

-- Migrate any ahg_ner_settings values not yet in ahg_ai_settings
INSERT IGNORE INTO ahg_ai_settings (feature, setting_key, setting_value)
SELECT
    CASE
        WHEN setting_key LIKE 'summarizer_%' THEN 'summarize'
        WHEN setting_key LIKE 'translation_%' THEN 'translate'
        WHEN setting_key LIKE 'spellcheck_%' THEN 'spellcheck'
        WHEN setting_key LIKE 'mt_%' THEN 'translate'
        WHEN setting_key LIKE 'qdrant_%' THEN 'qdrant'
        WHEN setting_key IN ('api_url', 'api_key', 'api_timeout', 'processing_mode') THEN 'general'
        WHEN setting_key IN ('ner_enabled', 'ner_entity_types', 'auto_extract_on_upload', 'auto_extract', 'extract_from_pdf') THEN 'ner'
        ELSE 'general'
    END AS feature,
    setting_key,
    setting_value
FROM ahg_ner_settings
WHERE setting_key COLLATE utf8mb4_unicode_ci NOT IN (
    SELECT setting_key FROM ahg_ai_settings WHERE feature = 'general'
);
