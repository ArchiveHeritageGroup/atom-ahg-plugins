# Wave 6 — 3D / Media / Immersive (2026-06-14)

**Repo:** atom-ahg-plugins · **Status:** in progress; unit 1 built + live, unreleased.

## Verify-first results (read-only vs live PSIS)
Most of Wave 6 already exists (the audit over-counts). AI/GPU-blocked gaps (need the gateway key / a GPU model) are parked with the AI-wave. Buildable gaps remain.

| Plugin | Verdict | Buildable gaps | AI/GPU-blocked |
|--------|---------|----------------|----------------|
| ahgExhibitionPlugin | PARTIAL | visitor annotation persistence, furniture library CRUD, reconstruction stages | AI object-describe, native TTS |
| ahg3DModelPlugin | PRESENT | camera bookmarks UI | TripoSR (present, needs GPU) |
| ahgImageArPlugin | PRESENT (2D-AR) | — | image→video (SVD/CogVideoX) |
| media-processing | PARTIAL | derivative/processing admin dashboard | — |
| media-streaming | PARTIAL | audio-description tracks | — |
| ahgIiifPlugin | PARTIAL | Mirador workspace UI, Change Discovery, OCR export formats | NER on canvases |
| ahgLabelPlugin | PARTIAL → **built unit 1** | (templates + batch printing) | — |

## Unit 1 — Label templates + batch printing (ahgLabelPlugin) — built + live
Was single-record only (`/label/:slug`, external barcodeapi.org + qrserver.com). Added:
- **`label_template` table** (page size, grid cols/rows, label mm dims, margins, font, show identifier/title/repository/barcode/QR, barcode source, QR target, default).
- **`lib/Services/LabelService.php`** (ns AtomExtensions\Label): template CRUD + `resolveByIds()` / `resolveByRepository()` (IO + i18n + slug + repository + accession/call_number/isbn) + `repositoryOptions()`.
- **3 actions** on `labelActions`: `templates` (admin list/delete), `templateEdit` (admin form), `batch` (auth; picker by IDs or repository → CSS-grid print sheet with barcode/QR per template, `window.print()`).
- **3 templates** + routes added after the generic `:slug` (loader prepends → specific matched first). Print CSS is an inline `<style>` with the **CSP nonce**.
- **Live-verified:** all `php -l` clean; `label_template` created; `/label/templates` + `/label/template/edit` → 403 (admin gate), `/label/batch` → 200 (picker) — none swallowed by `/label/:slug`, no 500. fpm restarted. Authed render + an actual print run not visually confirmed.

## Unit 2 — IIIF Change Discovery + OCR export (ahgIiifPlugin) — built + live-verified
- New `lib/Services/IiifDiscoveryService.php` (ns AhgIiif\Services): Change Discovery 1.0 `collection()` (OrderedCollection) + `page(n)` (OrderedCollectionPage, chronological Create/Update activities over published v3 manifests, 100/page) + `ocrForObject()` + `toAlto()`.
- 3 actions on `iiifActions`: `activity` (`/iiif/activity`), `activityPage` (`/iiif/activity/page/:n`), `ocrExport` (`/iiif/ocr/object/:id?format=txt|json|alto`). Public, `application/ld+json`.
- Routes added to the iiif RouteLoader block.
- ⚠️ **Gotcha:** `information_object` has NO created_at/updated_at — those live on the base `object` table (shared id). page() joins `object as o` for timestamps. (collection() count() didn't hit it, so it masked the bug until page/0.)
- **Live-verified:** `/iiif/activity` → 200 JSON (totalItems 692, 7 pages); `/iiif/activity/page/0` → 200 JSON with Activity Streams orderedItems (Create → manifest URLs); `/iiif/ocr/object/:id` → 404 graceful (iiif_ocr_text empty — output formats not exercised). No DDL; fpm restarted.

## Unit 3 — 3D camera bookmarks (ahg3DModelPlugin) — built + live-verified
- New `object_3d_camera_bookmark` table (model_id, name, camera_orbit, field_of_view, display_order).
- 3 actions on model3d (mirroring the hotspot CRUD: `$this->db`, JSON echo + sfView::NONE, auth + logAction): `addBookmark` (auth POST), `deleteBookmark` (auth POST), `apiBookmarks` (public GET). Routes `/ahg3DModel/{add,delete}Bookmark/:id` + `/api/3d/bookmarks/:model_id` (`/api/3d/*` not nginx-intercepted — existing /api/3d/hotspots works).
- Embed viewer integration (`embedSuccess.php`): CSP-nonce'd bookmarks bar — loads saved viewpoints from the API, click applies `mv.cameraOrbit`/`fieldOfView`; "Save view" reads `getCameraOrbit()` and POSTs (401-aware).
- **Live-verified:** all `php -l` clean; table created; `/api/3d/bookmarks/1` → 200 `{"bookmarks":[]}`; unauth `addBookmark` → 401 JSON. fpm restarted. Authed save + 3D render not visually confirmed (needs login + a model).

## Unit 4 — Media derivative-coverage dashboard (ahgIiifPlugin) — built + live-verified
- New `lib/Services/MediaCoverageService.php` (ns AhgIiif\Services): coverage of reference(141)/thumbnail(142) derivatives over "primary" digital objects (object_id NOT NULL, parent_id NULL = 666), whereExists child-usage subqueries, by-media-type rollup, `missing('thumbnail'|'reference')` lists with slug/title. (DerivativeWatermarkService is in LOCKED ahgCorePlugin → dashboard lives in ahgIiifPlugin.)
- `executeCoverage` action on `mediaSettingsActions` (admin-gated by existing `boot()`) + route `/mediaSettings/coverage` + dashboard template.
- **Live-verified:** all `php -l` clean; `/mediaSettings/coverage` → 403 (admin gate, no 500, no error-log); service SQL validated directly → 666 primaries, 68% thumbnail / 24% reference coverage, by-type breakdown. No DDL (read-only existing tables). fpm restarted. Authed render not visually confirmed.

## Unit 5 — Media audio-description tracks (ahgIiifPlugin) — built + live-verified
- New `media_audio_description` table (digital_object_id UNIQUE, language, label, vtt_content). Accessibility: WebVTT `kind="descriptions"`.
- 2 actions on `mediaActions`: `audioDescription` (public, serves the VTT at `/media/audio-description/:id`) + `audioDescriptionEdit` (admin author/save, `/media/audio-description/:id/edit`). Mirrors the caption/transcription pattern.
- `MediaHelper` now injects a `<track kind="descriptions">` next to the existing subtitles track, conditional on a row.
- Editor template (textarea VTT + language/label + preview link).
- ⚠️ Re-hit the **AtoM 4xx-themed-error gotcha**: serving with `setStatusCode(404)` made AtoM render its themed HTML error page (swallowed the body). Fixed: serve **HTTP 200** with a minimal `WEBVTT` when empty (a valid empty track) — never 4xx for the track endpoint.
- **Live-verified:** all `php -l` clean; table created; `/media/audio-description/1` → 200 `text/vtt` (`WEBVTT`); `/edit` → 403 admin gate. fpm restarted. Player injection + authed editor render not visually confirmed (needs a video + data).

## Remaining buildable Wave-6 units (not yet built)
- Exhibition: persisted visitor wall annotations (fills `walkthroughSuccess.php` TODO) + furniture library CRUD.
- media-streaming: audio-description (WebVTT kind="descriptions") tracks.
- ahgIiifPlugin: OCR export formats (hOCR/ALTO), Change Discovery feed, Mirador workspace persistence UI.
- ahg3DModelPlugin: camera bookmarks UI.
- media-processing: derivative/processing admin dashboard.
