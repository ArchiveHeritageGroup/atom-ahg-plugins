# Issue #220 — IIIF AI Extract — Implementation Plan

**Date:** 2026-07-04
**Issue:** [ArchiveHeritageGroup/atom-extensions-catalog#220](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/220) — [PARKED] IIIF AI extract (P3, `ai`, `category:integration`)
**Plugin:** `ahgIiifPlugin` (stable — user-directed unlock required to modify)
**Reference:** https://github.com/ai-and-history-collaboratory/tiny-iiif — "IIIF MCP server" (AI navigates a manifest deterministically via tools rather than scraping)

---

## 1. Goal

Let AI operate **over a digital object's IIIF manifest at the canvas/region level**: pick a canvas, crop a region via the IIIF Image API, send that region image to the **AHG AI gateway vision model**, and run a chosen extraction task (caption / describe / transcribe / entities / tags). Store region-level results, let a curator review and approve, and write approved output back into the catalogue.

This is distinct from the existing plaintext OCR path (`/iiif/ocr/object/:id`, `iiif_ocr_text`): that produces whole-page OCR text; this produces **region-scoped, VLM-driven structured extractions** and exposes them through an **MCP-tool-shaped surface** so an external AI client (Workbench chat, Claude) can drive the manifest deterministically (the tiny-iiif idea).

## 2. What already exists (reuse — do not rebuild)

| Capability | Where |
|---|---|
| IIIF v3 manifest + canvas list | `ahgIiifPlugin/lib/Services/IiifManifestV3Service.php` → `generateV3Manifest()`, `buildCanvas()`; Cantaloupe id = `str_replace('/', '_SL_', $path).$name`; image base `{baseUrl}/iiif/2/{id}` |
| Region URL builder | `lib/helper/IiifViewerHelper.php::render_iiif_image($id, ['region'=>'x,y,w,h'])` → `{cantaloupe}/{id}/{region}/{size}/{rotation}/{quality}.{fmt}` |
| Region/xywh model | `lib/Services/ContentStateService.php::buildFromRegion(...)` (Content State, `canvasId#x,y,w,h`) |
| **Gateway vision model** | `atom-framework/src/Services/AI/AiGatewayClient.php::visionGenerate(string $prompt, array $base64Images, ?string $model, array $options)` → POST `/ollama/api/generate`. Auth `X-API-Key`, `fromSettings()` reads `ahg_ai_settings` (feature=`gateway`). |
| NER / summarize (text) | `ahgAIPlugin/lib/Services/NerService.php` (`ahgNerService::extract()`, `summarize()`) |
| Stored-OCR read | `IiifDiscoveryService::ocrForObject()`, tables `iiif_ocr_text` / `iiif_ocr_block` (block_type already includes `'region'` with x/y/w/h/confidence) |
| Master-image path resolution | `ahgAIPlugin/lib/task/aiHtrExtractTask.class.php::getImagePath()` — `digital_object` table, master `parent_id IS NULL`, reference derivative `usage_id=142` |
| Route registration | `config/ahgIiifPluginConfiguration.class.php::addRoutes()` via `RouteLoader`; action `'aiExtract'` → `executeAiExtract` |

**Gateway rule (hard constraint):** all AI inference MUST route through `AiGatewayClient` → `https://ai.theahg.co.za/ai/v1`. Never call a GPU node port directly. The image-region *fetch* from local Cantaloupe (`127.0.0.1:8182`) is a plain local fetch (not the SSRF-guarded gateway client); only the VLM call goes through the gateway.

## 3. Design

### 3.1 New service — `IiifAiExtractService` (`ahgIiifPlugin/lib/Services/`, ns `AhgIiif\Services`)

```
listCanvases(int $objectId, string $culture='en'): array
    → reuse IiifManifestV3Service to return [{index,label,cantaloupeId,width,height,imageBase}]

extractRegion(int $objectId, int $canvasIndex, string $region, string $task, ?int $userId): array
    1. resolve cantaloupeId for the canvas (from manifest service)
    2. build region JPEG URL: {cantaloupeInternalUrl}/iiif/2/{id}/{region}/{size}/0/default.jpg
       region = 'full' or 'x,y,w,h'; size capped (e.g. !1024,1024) to bound VLM payload
    3. fetch bytes locally (plain curl, NOT gateway client) → base64
    4. prompt = TASK_PROMPTS[$task] (caption|describe|transcribe|entities|tags)
    5. $client = AiGatewayClient::fromSettings(); $client->visionGenerate($prompt, [$b64])
    6. store row in iiif_ai_extract (status 'draft')

approve(int $extractId, ?string $targetField='scope_and_content'): bool
    → mirror aiSummarizeTask::processObject: QubitInformationObject::getById,
      dynamic setter, save(); mark row 'approved'
```

Use Laravel QB (`Illuminate\Database\Capsule\Manager as DB`) for all reads/writes. `date()` not `now()`.

### 3.2 New table — `iiif_ai_extract` (`database/migration_iiif_ai_extract.sql` + append to `install.sql`)

```sql
CREATE TABLE iiif_ai_extract (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  object_id INT NOT NULL,
  digital_object_id INT NULL,
  canvas_index INT NOT NULL DEFAULT 0,
  region VARCHAR(64) NOT NULL DEFAULT 'full'  COMMENT 'full or x,y,w,h',
  task VARCHAR(20) NOT NULL                    COMMENT 'caption, describe, transcribe, entities, tags',
  model VARCHAR(120) NULL,
  prompt TEXT NULL,
  output_text LONGTEXT NULL,
  output_json JSON NULL,
  confidence DECIMAL(5,4) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft'  COMMENT 'draft, approved, rejected',
  created_by INT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_object (object_id),
  KEY idx_status (status)
);
```
No ENUM (VARCHAR + COMMENT per house rule). Region-level OCR-style output can *also* be written to the existing `iiif_ocr_block` (block_type `'region'`) when task=`transcribe`, for search parity — optional Phase 2.

### 3.3 Routes + actions (`modules/iiif/actions/actions.class.php`, registered in `addRoutes()`)

| Method | Route | Action | Auth |
|---|---|---|---|
| GET | `/iiif/ai/canvases/object/:id` | `executeAiCanvases` | auth |
| POST | `/iiif/ai/extract` | `executeAiExtract` | auth |
| GET | `/iiif/ai/extract/object/:id` | `executeAiExtractList` | auth |
| POST | `/iiif/ai/extract/approve` | `executeAiExtractApprove` | admin |
| GET | `/iiif/ai/extract/review/:id` | `executeAiExtractReview` | admin |

`executeAiCanvases` + `executeAiExtract` return `application/json` — these two are the **MCP-tool surface** (`iiif_manifest_canvases`, `iiif_region_extract`). This is what a future thin MCP wrapper (tiny-iiif style) calls; no separate server process required — the routes *are* the tool endpoints.

### 3.4 Review UI (`modules/iiif/templates/aiExtractReviewSuccess.php`)

Full-parity curator panel: canvas thumbnail + drawn region box (OpenSeadragon already vendored), the extracted text/JSON, Approve → target-field picker, Reject. Any inline `<script>`/`<style>` **must carry the CSP nonce** (see CLAUDE.md snippet). Whitelist no new CDNs (OpenSeadragon is local).

### 3.5 CLI task (`lib/task/iiifAiExtractTask.class.php`, `iiif:ai-extract`)

`--object-id=`, `--task=caption`, `--region=full`, `--approve` (auto-approve). For batch/background runs; mirrors the existing `ai:*` task pattern. Registered like other `ahgIiifPlugin` tasks.

## 4. Phasing

- **Phase 1 (core, ~1 session):** service + table + `executeAiCanvases`/`executeAiExtract`/`executeAiExtractList` JSON endpoints + `visionGenerate` wiring + CLI task. Verifiable via curl end-to-end.
- **Phase 2:** review UI + approve/reject + write-back to `scope_and_content`; optional `iiif_ocr_block` region rows for search.
- **Phase 3 (optional):** thin MCP wrapper documenting the two JSON routes as MCP tools (tiny-iiif parity) so Workbench/Claude can drive it. No new inference path — reuses the gateway.

## 5. Constraints / gotchas

- `ahgIiifPlugin` is **stable/locked** — needs explicit user go-ahead to modify (this plan is that request).
- Gateway-only AI; `AiGatewayClient::fromSettings()` requires a valid `ahg_ai_settings` gateway key on PSIS (already seeded this session — same key that powered `ai:index-catalogue`).
- `visionGenerate` uses the gateway chat/vision model (`qwen3:14b` default; confirm a vision-capable model id is routed by the gateway, else set `model` explicitly). **Open item: confirm the gateway advertises a VLM route** — if not, that's a gateway-side add (fix the gateway, don't bypass it).
- DDL: user runs the `CREATE TABLE` (DB-write rule). I supply the `.sql`.
- Mirror all code to `/usr/share/nginx/archeology` after changes (standing sync rule).
- No base-AtoM edits; Capsule QB only; namespaced `\` prefixes; CSP nonces on all inline script/style.
- Release via `./bin/release patch` — gated on explicit Y/N.

## 6. Open questions for Johan

1. **Scope for now** — build Phase 1 only (JSON extract endpoints + CLI), or Phases 1–2 (incl. review UI + write-back)?
2. **Tasks to support** — is caption / describe / transcribe / entities / tags the right set, or narrower?
3. **Vision model** — does the gateway currently route a vision-capable model? If unknown, I'll probe `AiGatewayClient::isAvailable()` + a test `visionGenerate` before building.
