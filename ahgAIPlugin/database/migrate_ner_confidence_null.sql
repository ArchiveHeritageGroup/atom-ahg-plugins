-- ============================================================================
-- NER Confidence NULL Migration
-- Date: 2026-05-27
-- Issue: PSIS #19 — DB migration clean existing confidence = 0.95 rows
--
-- PROBLEM:
-- Before v3.35.0 (commit 7003d2a), the NER pipeline assigned confidence = 0.95
-- as a hardcoded fallback whenever the /ai/v1/ner/extract API returned no
-- per-entity score. This wrote FABRICATED confidence values to:
--   - ahg_ner_entity.confidence
--   - ahg_ner_entity_link.confidence
--
-- FIX:
-- v3.35.0 changed all five PHP entry points to write confidence = NULL when
-- no real model score is available (not 0.95). This migration cleans the
-- pre-v3.35.0 fabricated values from existing rows.
--
-- SCOPE:
--   - ahg_ner_entity     WHERE confidence = 0.9500  (fabricated fallback)
--   - ahg_ner_entity_link WHERE confidence = 0.9500 (fabricated fallback)
-- NOT changed (legitimate real scores):
--   - confidence < 0.95  (real score below fallback threshold)
--   - confidence > 0.95  (real score above fallback threshold)
--   - confidence = 1.0000 (explicit default, not fabricated)
-- ============================================================================

-- Pre-check: show current fabricated row counts
SELECT 'ahg_ner_entity'     AS tbl, COUNT(*) AS fabricated_confidence_095 FROM ahg_ner_entity     WHERE confidence = 0.9500
UNION ALL
SELECT 'ahg_ner_entity_link',                COUNT(*)                 FROM ahg_ner_entity_link WHERE confidence = 0.9500;

-- ============================================================================
-- CLEAN: ahg_ner_entity — reset fabricated 0.95 to NULL
-- ============================================================================
UPDATE ahg_ner_entity
SET    confidence = NULL
WHERE  confidence = 0.9500;

-- ============================================================================
-- CLEAN: ahg_ner_entity_link — reset fabricated 0.95 to NULL
-- ============================================================================
UPDATE ahg_ner_entity_link
SET    confidence = NULL
WHERE  confidence = 0.9500;

-- Post-check: confirm zero fabricated rows remain
SELECT 'ahg_ner_entity'     AS tbl, COUNT(*) AS remaining_095 FROM ahg_ner_entity     WHERE confidence = 0.9500
UNION ALL
SELECT 'ahg_ner_entity_link',                COUNT(*)                 FROM ahg_ner_entity_link WHERE confidence = 0.9500;
