# 2026-06-10 — AI Cataloguer (issue #149 strand)

## Summary
Built an **AI Cataloguer** into `ahgAIPlugin` — the implementable archive-side strand of tracking issue **#149** (Heratio digital-twin / memory-layer roadmap). The 3D twin itself is Laravel-native and not portable to AtoM (Symfony 1.4), but #149 flags AI-assisted cataloguing as where PSIS parity genuinely matters. Live + service-verified on PSIS; **not yet released**.

## What it does
Given an information object, it drafts a *full* ISAD(G) record in one LLM pass — strictly from the record's own sources (existing fields, OCR/transcribed text, #113 embedded technical metadata, and extracted NER entities) — for an archivist to review field-by-field and apply. Unlike the existing `DescriptionService` (single scope-and-content field), this produces a structured multi-field draft and does not invent facts (unsupported fields come back empty).

## Components (all in ahgAIPlugin)
- `lib/Services/CatalogerService.php` — `generateDraft()` / `getLatestDraft()` / `applyDraft()`. Reuses `DescriptionService::gatherContext()` (fields + OCR), `AhgEmbeddedMetadataContextService` (#113), `ahg_ner_entity`, and `LlmService::complete()`. Strict-JSON prompt; parser strips code fences + filters placeholder strings.
- Table `ahg_catalog_draft` (`database/install.sql` + `database/migrate_catalog_draft.sql`).
- Routes `/ai/catalog/:id` and `/ai/catalog/:id/apply`; single-action classes `aiCatalogAction` + `aiCatalogApplyAction`.
- `modules/ai/templates/catalogSuccess.php` — side-by-side current vs AI draft, per-field accept, suggested dating/level/access-points.
- "Catalogue with AI" button added to the per-record AI-tools widget (`_aiTools.php`).
- LLM via the existing `ahg_llm_config` (Ollama mistral:7b).

## Verification
Service-level (debug=false CLI): generate produced a grounded draft (title/level/scope/extent/dates/creator/subjects/places/language) and left unsupported fields empty — no hallucination. Apply wrote a field to a disposable test record (matched the draft) and was restored. Route resolves; site healthy.

Caveats: the **authenticated UI render** was not visually confirmed (no test password to hand; the `ai` module is `is_secure: true`). Template lints clean with all variables provided — recommend a click-through before release.

## Notable finding (framework)
`atom-framework` `StandaloneInformationObjectWriteService::I18N_FIELDS` omits `extent_and_medium` (and `edition`, `institution_responsible_identifier`), so `updateInformationObject()` misroutes them to the core table. The cataloguer writes i18n text directly to avoid this; a clean follow-up is to add the missing columns to `I18N_FIELDS`.

## Status
Built + service-verified on PSIS. Pending: authenticated UI click-through, release (`./bin/release minor`), per-instance DDL, optional framework `I18N_FIELDS` fix.
