# AiGatewayClient refactor — flagged direct-node callers — PLAN — 2026-06-15

## ✅ IMPLEMENTED 2026-06-15 (unreleased): helper + A + D
- **Helper:** added `AiGatewayClient::translate($text,$src,$tgt,$maxLen=null)` (POST /ai/v1/translate) + `translateLanguages()` (GET /ai/v1/translate/languages) — reuse the existing keyed `postJson()`/`authHeaders()` + GET pattern. Lint clean; ReflectionClass confirms methods + signature.
- **A:** ahgAIPlugin `executeTranslateLanguages` + `callTranslationApi` now call `AiGatewayClient::fromSettings()->translateLanguages()/translate()` — removes the raw curl + the `app_ai_api_key` (config.php) key source; key now comes from `ahg_ai_settings` via fromSettings(), URL is the gateway, SSRF-guarded. Return shapes preserved ({success,translated}/{success:false,error}).
- **D:** ahgDiscoveryPlugin `extension.json:37` endpoint `192.168.0.112:11434` → `https://ai.theahg.co.za/ai/v1/ollama` (vestigial config default; valid JSON).
## ✅ B IMPLEMENTED 2026-06-15 (unreleased)
`atom-framework/src/Services/OllamaPageIndexClient.php` now routes through `AiGatewayClient`:
- Added a lazy `gateway()` (→ `AiGatewayClient::fromSettings()`); dropped the `endpoint` property + `:11434` default; **removed the raw `request()`** curl method entirely.
- Private `chat()` → `gateway()->chat($messages, $gwOptions)` with `num_predict`→`max_tokens` mapping; **return shape preserved** (`success/text/model/tokens_used/generation_time_ms/error`), token counts read from `result['raw']` (prompt_eval_count+eval_count) → `buildTree()`/`retrieveNodes()` untouched.
- `isAvailable()`/`getHealth()` → gateway liveness (`/api/tags` model-list not exposed via gateway; health is now up/down). Docblock updated.
- Verified: lint clean, no dangling `$this->endpoint`/`request()`, no direct `:11434` in code (comments updated). Consumers use only preserved keys.

## ✅ C IMPLEMENTED 2026-06-15 (unreleased) — OCR route found in the gateway
**Gateway OCR route confirmed by reading the gateway itself** (`/opt/ahg-ai/gateway/app/routes/ai_proxy.py`): there is no `/ai/v1/ocr/*`, but a transparent legacy-HTR catch-all `@router.api_route("/htr/legacy/{subpath}")` forwards to `HTR_LEGACY_URL` (= `http://192.168.0.115:5006`, the same node PageIndexService used directly). So `:5006/ocr/extract` → **`https://ai.theahg.co.za/ai/v1/htr/legacy/ocr/extract`** (keyed, `gateway` scope).
- ⚠️ **Correction:** ai-demo does NOT OCR through the gateway — its `ocrExtract` runs **local Tesseract** (`exec("tesseract")`) and its NER/HTR point at direct nodes (`:5004`/`:5006`). The route above came from the gateway code, not ai-demo. (ai-demo's own direct-node use is a separate app's gap, out of PSIS scope.)
- **Fix:** `PageIndexService` ocrEndpoint default → `https://ai.theahg.co.za/ai/v1/htr/legacy` (callOcrService appends `/ocr/extract`); added an `ocrApiKey` loaded from `ahg_ai_settings` (feature gateway→general) and sent as `X-API-Key`. Multipart `CURLFile` body unchanged (the gateway catch-all forwards it). No `AiGatewayClient::ocr()` needed (OCR is multipart; postJson is JSON-only). Lint clean; no direct node IPs left in framework src.
- ⚠️ **Needs a live OCR smoke test post-deploy** (multipart through the gateway catch-all couldn't be e2e-verified offline).

## ✅ REFACTOR COMPLETE — A, B, C, D all implemented (unreleased).

---


Follow-up to the direct-node→gateway cleanup: 4 callers point at GPU-node ports but **send no gateway API key**, so a plain URL swap → 401. They must route through the keyed/SSRF-guarded gateway. None are in locked plugins.

## What the sanctioned client gives us
`AtomFramework\Services\AI\AiGatewayClient` (`fromSettings()` builds from `ahg_ai_settings`; sends `X-API-Key`; SSRF-guarded; base `https://ai.theahg.co.za/ai/v1`):
- `embed()/embedBatch()`, `chat(messages, options)`, `generate(prompt, system, options)`, `isAvailable()`.
- **Ollama passthrough ONLY** (`/ai/v1/ollama/api/*`). **No** `translate()`, **no** OCR, **no** `/api/tags`.

So the 4 callers split by whether they're Ollama (fits AiGatewayClient) or worker-routes (need a small extension).

## Proposed prerequisite — extend AiGatewayClient with keyed worker-route helpers
Add two thin methods so EVERY gateway call shares one keyed+guarded client (rather than hand-rolled curl):
- `translate(string $text, string $from, string $to, ?int $maxLen): ?array` → POST `/ai/v1/translate` (+ a `languages()` GET `/ai/v1/translate/languages`).
- `ocr(string $imagePathOrData, array $opts = []): ?array` → POST `/ai/v1/ocr/extract`.
Both reuse the existing private keyed-POST + SSRF guard. (Keeps AiGatewayClient as the single door; worker routes are still gateway `/ai/v1/*`.)
⚠️ **Gateway-route confirmation needed first** (operator): does the gateway proxy `/ai/v1/translate*` and `/ai/v1/ocr/extract`? The translate route is in use by the now-gateway-pointed ahgTranslationService (so likely yes); **OCR `/ai/v1/ocr/extract` is UNVERIFIED** — if the gateway doesn't expose it, the correct fix is to ADD the route to the gateway (per the standing rule), NOT an app workaround.

## Per-caller plan

### A. ahgAIPlugin `modules/ai/actions/actions.class.php:1529,1555` (translate + translate/languages)
Worker routes, currently raw curl with no key.
- **Fix:** replace the two curl blocks with `AiGatewayClient::fromSettings()->translate(...)` / `->languages()`. (Or, minimal interim: add `X-API-Key` header read from `ahg_ai_settings` + keep gateway URL.)
- Effort: small. Risk: low (key-bearing; gateway already proxies translate).

### B. atom-framework `OllamaPageIndexClient.php` (Ollama `/api/chat` + `/api/tags`)
Genuinely Ollama → AiGatewayClient fits.
- **Fix:** hold an `AiGatewayClient` (via `fromSettings()`); private `chat(system,user,opts)` → build `messages` + `$gw->chat($messages,$opts)`; `isAvailable()/getHealth()` → `$gw->isAvailable()`. Drop the direct `:11434` endpoint + `request()`.
- ⚠️ **Map response shape:** `AiGatewayClient::chat()` returns a normalized array; `buildTree()/retrieveNodes()` currently parse the raw Ollama `/api/chat` JSON → adapt the parsing to the client's return shape (verify `chat()` output during impl).
- Effort: medium. Risk: medium (response-shape mapping — unit-test buildTree/retrieveNodes against a known doc).

### C. atom-framework `PageIndexService.php:42` (OCR `:5006` `/ocr/extract`)
Not Ollama; gateway OCR route unverified.
- **Fix (after route confirmed):** `AiGatewayClient::fromSettings()->ocr(...)` (or keyed POST to `/ai/v1/ocr/extract`).
- **BLOCKED** on gateway OCR-route confirmation. If absent → file a gateway "add OCR route" request; do NOT keep the direct `:5006` call as a workaround long-term, but it can remain until the gateway route exists (operator-internal, lower exposure than a remote app endpoint).
- Effort: small once unblocked.

### D. ahgDiscoveryPlugin `extension.json:37` (`:11434`)
**Vestigial** — no PHP consumer found (Discovery uses Qdrant; query-expansion display is client-side). 
- **Fix:** update the config default string to `https://ai.theahg.co.za/ai/v1/ollama` for consistency, or remove the key if confirmed unused. If a live Ollama consumer is found during impl → route via `AiGatewayClient::generate()/chat()`.
- Effort: tiny.

## Rollout / testing / safety
- Order: (0) confirm gateway translate+OCR routes → (A) translate → (D) discovery config → (B) page-index (most logic) → (C) OCR (after route).
- Each caller keeps reading its endpoint from settings (operator override preserved); default → gateway. Backward-compatible: if `fromSettings()` yields no key, callers should fail closed with a clear log (not silently hit a node).
- Tests: translate round-trip via gateway; `OllamaPageIndexClient::buildTree()` on a sample doc (response-shape regression); OCR extract on a sample image (post-route); confirm Discovery still works untouched.
- No locked plugins involved (ahgAIPlugin, atom-framework, ahgDiscoveryPlugin). tier-2 `localhost:11434` voice/semantic/condition defaults remain a SEPARATE pass (some locked ahgThemeB5).

## Recommendation
Implement order A → D → B, treating **C (OCR)** as blocked pending gateway-route confirmation, and add the `translate()`/`ocr()` helpers to AiGatewayClient first so all four share one keyed door.
