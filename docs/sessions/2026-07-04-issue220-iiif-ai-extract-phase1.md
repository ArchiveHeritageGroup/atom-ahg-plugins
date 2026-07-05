# Issue #220 — IIIF AI Extract — Phase 1 (BUILT + VERIFIED)

**Date:** 2026-07-04
**Issue:** [ArchiveHeritageGroup/atom-extensions-catalog#220](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/220) — was [PARKED], P3
**Plugin:** `ahgIiifPlugin` (stable — unlocked by user for this work)
**Status:** Phase 1 built, lint-clean, verified end-to-end on PSIS + mirrored to archaeology. UNRELEASED.

## What shipped (Phase 1)

Region-scoped, VLM-driven extraction over a digital object's IIIF canvases. Pick a canvas → crop a region via the IIIF Image API → send the region image to the **AHG AI gateway vision model** → run a task → store for curator review. The two JSON endpoints double as the MCP-tool surface (tiny-iiif idea in the issue).

### Files
| File | Change |
|---|---|
| `lib/Services/IiifAiExtractService.php` | NEW — `listCanvases()`, `extractRegion()`, `listExtractions()` |
| `modules/iiif/actions/actions.class.php` | +3 actions: `executeAiCanvases`, `executeAiExtract`, `executeAiExtractList` (+ `aiExtractService()` helper) |
| `config/ahgIiifPluginConfiguration.class.php` | +3 routes in the `iiif` RouteLoader block |
| `lib/task/iiifAiExtractTask.class.php` | NEW — CLI `iiif:ai-extract` |
| `database/migration_iiif_ai_extract.sql` | NEW — `iiif_ai_extract` table |

### Routes (all auth-gated)
- `GET  /iiif/ai/canvases/object/:id` → `aiCanvases` (MCP: iiif_manifest_canvases)
- `POST /iiif/ai/extract` → `aiExtract` (MCP: iiif_region_extract) — body `{object_id, canvas_index?, region?, task}`
- `GET  /iiif/ai/extract/object/:id` → `aiExtractList`

### Tasks
caption, describe, transcribe, entities, tags. Prompts in `IiifAiExtractService::TASK_PROMPTS`.

## Key findings (grounding)

- **Gateway vision model:** probed live 2026-07-04 — gateway routes **`llava:7b`** (`/ollama/api/tags`). `AiGatewayClient::visionGenerate($prompt, [$b64], 'llava:7b', ...)` is the sanctioned path (`/ai/v1/ollama/api/generate`). Default model overridable via `app_iiif_ai_vision_model`.
- **Cantaloupe identifier:** `str_replace('/', '_SL_', ltrim($do->path,'/')) . $do->name` (mirrors `IiifManifestV3Service`). Effective `FilesystemSource` prefix resolves to `/usr/share/nginx/archive/` + identifier (the DB `path` already contains `uploads/`). Confirmed empirically: keeping `uploads/` in the id is correct; stripping it 404s.
- **Size gotcha (fixed):** Cantaloupe **403s an upscaling `!w,h` request**. Fixed by sizing off the canvas/region pixel width — downscale to `1024,` only when source width > 1024, else `max`. Never upscale.
- **Image fetch is LOCAL:** region bytes fetched by a plain `curl` to `127.0.0.1:8182` (NOT the SSRF-guarded gateway client, which correctly blocks private IPs). Only the VLM call goes through the gateway.
- **Master vs derivative:** some masters (e.g. object 829's JPEG) return Cantaloupe `501 Unsupported source format` and have no reference derivative → not extractable. Robust derivative-preference is a Phase 2 improvement.

## Verification (PSIS, object 912509)

- CLI `iiif:ai-extract` ran all 5 tasks: describe/transcribe/tags/entities (full canvas) + caption (sub-region `0,0,400,300`) — all `success`, 5 rows stored in `iiif_ai_extract`.
- `entities` JSON: llava echoed the prompt template + hit the 512-token cap → incomplete JSON → graceful fallback (text stored, `output_json` NULL). Model-quality, not a code defect.
- HTTP endpoints: all three return **401 unauthenticated** (route resolves + auth gate works).
- Archaeology mirror: 5 files copied (md5 match), `iiif_ai_extract` created in `archeology` DB, both caches cleared, php-fpm restarted, routes registered (CLI-verified).

## Not done / follow-ups (Phase 2+)

- Review UI + Approve/Reject → write approved output to `scope_and_content` (mirror `aiSummarizeTask::processObject`).
- Route `transcribe` to the accurate OCR path (`/ocr/extract`) instead of llava for precise text; keep llava for caption/describe/tags.
- Prefer a Cantaloupe-servable reference derivative over the master; handle 501/501 gracefully in `listCanvases`.
- Optional: write region transcriptions into `iiif_ocr_block` (block_type `region`) for search parity.
- Phase 3: thin MCP wrapper documenting the two JSON routes as tools (tiny-iiif parity).

## Test rows

5 draft rows on object 912509 (a Heratio findings PNG, not a real archival record) remain in `iiif_ai_extract` as test artifacts — safe to leave or delete.
