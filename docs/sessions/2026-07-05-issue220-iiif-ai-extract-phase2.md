# Issue #220 — IIIF AI Extract — Phase 2 (BUILT + VERIFIED)

**Date:** 2026-07-05
**Issue:** [ArchiveHeritageGroup/atom-extensions-catalog#220](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/220)
**Plugin:** `ahgIiifPlugin` · **Builds on:** Phase 1 (v3.79.65)
**Status:** built, lint-clean, verified on PSIS + mirrored to archaeology. UNRELEASED. **No DDL** (reuses `iiif_ai_extract`).

## What shipped (Phase 2)

Curator review + approve/reject with write-back, and an accurate transcribe path.

| File | Change |
|---|---|
| `lib/Services/IiifAiExtractService.php` | `approve()` (write-back to IO i18n field + status), `reject()`, `previewUrl()`, `ocrRegion()` (Tesseract); `extractRegion()` now routes `transcribe`→Tesseract, other tasks→gateway VLM |
| `modules/iiif/actions/actions.class.php` | +3 actions: `executeAiExtractReview` (admin UI), `executeAiExtractApprove`, `executeAiExtractReject` |
| `config/ahgIiifPluginConfiguration.class.php` | +3 routes |
| `modules/iiif/templates/aiExtractReviewSuccess.php` | NEW admin review UI (layout_2col, region previews, target-field picker, CSP-nonced approve/reject JS) |
| `lib/task/iiifAiExtractTask.class.php` | +`--approve-id`/`--reject-id`/`--apply-field` (automation + CLI approve) |

### Routes (admin-gated)
- `GET  /iiif/ai/extract/review/object/:id` → `aiExtractReview`
- `POST /iiif/ai/extract/approve` → `aiExtractApprove` (body `{extract_id, target_field?}`)
- `POST /iiif/ai/extract/reject` → `aiExtractReject` (body `{extract_id}`)

Approve write-back mirrors `aiSummarizeTask::processObject` — `QubitInformationObject::getById` → dynamic setter → `save()`. Allowed target fields: scope_and_content, arrangement, physical_characteristics, archival_history, title.

## Key findings / fixes

- **transcribe accuracy:** llava:7b hallucinated text; the gateway does **not** route `/ocr/extract` (404), and the AI-server OCR wants an `image_url` the *remote* server can't reach (Cantaloupe is 112-local). Fix: local **Tesseract 5.3.4** on the already-fetched region bytes. Verified far more accurate: object 912509 → clean "Heratio Hybrid Retrieval Findings" (`model=tesseract`) vs llava's garble.
- **`method_exists` trap:** Qubit/Propel i18n setters are magic (`__call`) so `method_exists($io,'setScopeAndContent')` is FALSE — the guard wrongly rejected every approve. Removed it (call directly, like the proven aiSummarizeTask).
- **`save()` throws a TypeError in CLI:** arOpenSearch post-save indexing throws `in_array(null)` (a `TypeError`, i.e. `Error` not `Exception`) when ES/config isn't available in CLI. The i18n field write persists *before* the index hook, so approve now `catch (\Throwable)`, still marks approved, and returns a `warning` — an index hiccup never loses the approval (`search:populate` reconciles). Web path indexes normally.

## Verification (PSIS, object 912509)

- `iiif:ai-extract --task=transcribe` → `model=tesseract`, clean text ✓
- `--approve-id=6 --apply-field=scope_and_content` → `success:true`, row status `approved`, OCR text written to `information_object_i18n.scope_and_content` (+ reindex warning). Object then **restored to original NULL** (test cleanup, quoted).
- `--reject-id=1` → row status `rejected` ✓
- HTTP: approve/reject/review all return **403 unauthenticated** ✓
- Routes registered on archive + archaeology; 5 files md5-identical across both; both caches cleared; php-fpm restarted.

## Not verified / follow-ups

- **Authenticated visual render** of `aiExtractReviewSuccess.php` NOT confirmed (no admin plaintext password to hand). Template is lint-clean and pattern-identical to the working `settingsSuccess.php`; action supplies every var. Recommend a click-through by Johan.
- Phase 3 (optional): thin MCP wrapper documenting the JSON routes as tools (tiny-iiif parity).
- Optional: `tags`/`entities` write-back into subject access points / terms (Phase 2 write-back covers text fields only).

## Test rows

Draft/approved/rejected rows remain on synthetic object 912509 in `iiif_ai_extract` — safe to leave.
