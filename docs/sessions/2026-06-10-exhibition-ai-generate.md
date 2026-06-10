# 2026-06-10 — Exhibition AI Designer (generative exhibitions)

## Summary
Ported Heratio's **AI Exhibition Designer** (generative exhibitions, heratio#1186) to PSIS `ahgExhibitionPlugin`. A curator types a theme; the AI retrieves catalogue candidates, an LLM curates them into a themed draft (rooms + objects + labels), and "Build" turns the reviewed draft into real exhibition spaces + placements. Completes the exhibition page set.

## Components
- `lib/Services/GenerativeExhibitionService.php` (new):
  - `suggest(theme, maxObjects, publishedOnly)` — `candidateObjects()` builds a theme-ranked pool from `information_object` + `information_object_i18n` (FULLTEXT MATCH…AGAINST over title+scope, LIKE fallback; published filter via status type 158 / status 160; prefers already-placed objects), then `curate()` calls the LLM via `\LlmService::complete()` (same gateway/config as the cataloguer + chatbot) and **maps the model's small 1-based numbers back to real `information_object` ids** (so invented/long ids can't leak). Returns `{ok, theme, rooms:[{title, label, objects:[{id,title,year,thumb_url}]}], candidate_count}`.
  - `buildExhibition(draft)` — in a DB transaction, creates one `ahg_exhibition_space` per room via the existing `ExhibitionSpaceService->create()` (shared `building_id`, incrementing `building_seq`) and places each object via `createPlacementAt()`. Returns `{ok, spaces, placed, builder_url}`.
- Actions `executeGenerate` (page, auth), `executeGenerateSuggest`, `executeGenerateBuild` (auth AJAX) + routes `/exhibition-space/generate/suggest|build`.
- `generateSuccess.php` — theme input + sample chips + "Published only" toggle + "Design it" → renders room cards with object thumbnails/labels → "Build this exhibition" → redirects into the new space's builder. AJAX, session-auth (no CSRF header), nonce'd.
- Browse "AI Tools → AI Exhibition Designer" dropdown item flipped from "soon" to a live link.

## Verified (service-level, end-to-end)
`suggest('history')` → 4 candidates curated into **3 themed rooms** (Ancient Civilizations / Middle Ages / Modern Era); `buildExhibition` created 3 spaces + 4 placements (then cleaned up). Page renders (54 KB, login-gated, verified via reverted auth-bypass). Uses the existing Ollama LLM config.

## Status
Released. The exhibition page set is now complete: browse/show/edit/builder/walkthrough/plan/analytics/forecast/**generate**. Deferred under #149: AI docent/TTS, multi-user presence, IoT sensors, WebGPU. Per-instance deploy: migrations 003+004, cache, fpm restart.
