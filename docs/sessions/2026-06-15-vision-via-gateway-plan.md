# Vision via the AI gateway — PLAN — 2026-06-15

## ✅ Step 1 + ConditionAIService IMPLEMENTED 2026-06-15 (unreleased)
- **Step 1:** `AiGatewayClient::visionGenerate($prompt, $base64Images, $model=null, $options=[])` — keyed POST `/ollama/api/generate` with `{model, prompt, images, stream:false, options(temperature/seed/num_predict)}`; returns `{success, text(=`$data['response']`), model, error, raw}`; fails closed without a key. Lint + Reflection verified.
- **ConditionAIService::analyzePhoto** → `AiGatewayClient::fromSettings()->visionGenerate($prompt, [$base64], $this->model, ['temperature'=>0,'seed'=>42,'timeout'=>…])`; dropped the localhost curl; image-prep/buildPrompt/parseResponse unchanged. (`$this->ollamaUrl` property now unused — harmless.) Lint clean; no `/api/generate`/`curl_init` left.
- **Remaining:** ahgThemeB5 voice `describeImageAction::callLocal` + `describeObjectAction::callLocal` + `voiceConfig.php` — same swap, **pending Johan's ahgThemeB5 unlock**.
- ⚠️ Still needs the operator prereq below (gateway must serve `llava:7b`) before a live vision smoke test passes.

---


Final gateway-routing piece: the image (vision) AI callers still hit `localhost:11434/api/generate` with no key. Route them through the gateway's Ollama passthrough.

## Good news — all three callers are identical
Every vision caller uses the **same Ollama-native shape**: POST `/api/generate` with `{model, prompt, images:[<base64>], stream:false, options}`, model `llava:7b`, response `{response: "..."}`:
- `ahgConditionPlugin/lib/Service/ConditionAIService.php::analyzePhoto` (NOT locked) — `temperature:0, seed:42`.
- `ahgThemeB5Plugin/modules/ahgVoice/actions/describeImageAction.class.php::callLocal` (LOCKED — Johan unlocking).
- `ahgThemeB5Plugin/modules/ahgVoice/actions/describeObjectAction.class.php::callLocal` (LOCKED — collage image).
Config: `voiceConfig.php` (`local_llm_url`, `local_llm_model=llava:7b`, `llm_provider: local|cloud`). The voice actions have a separate `callCloud()` (Anthropic) path — leave it untouched.

So one helper + 3 near-drop-in call swaps.

## The gateway path (confirmed)
The gateway fronts Ollama at `/ai/v1/ollama/*` (transparent passthrough, keyed, `gateway` scope). ai-demo's reference uses `…/ai/v1/ollama/v1/chat/completions` (OpenAI-compat, `image_url`); our callers use **Ollama-native `/api/generate` + `images[]`**, which the same passthrough serves at **`…/ai/v1/ollama/api/generate`**. Native is the lower-friction target (caller bodies/response stay the same).

⚠️ **Operator prereq:** the gateway dispatches by model name to a GPU node that **must have the vision model (`llava:7b`) loaded/routable**. If not, expect a 500 "model failed to load" (same class as the earlier qwen3:14b issue). Confirm `llava:7b` is gateway-available, or set the callers to a vision model the gateway serves.

## Step 1 — add the helper (AiGatewayClient)
`visionGenerate(string $prompt, array $base64Images, ?string $model = null, array $options = []): array`
- POST `/ollama/api/generate` with `{model, prompt, images:$base64Images, stream:false, options}` via the existing keyed `postJson()` (SSRF-guarded).
- Return `{success, text, model, error, raw}` where `text = $data['response']` (Ollama generate shape) — mirrors `chat()`'s return contract.
- Fail closed when `!isConfigured()` (no key).

## Step 2 — callers (each: build base64 → visionGenerate → map text)
- **ConditionAIService** (do first, unlocked): `analyzePhoto` → `AiGatewayClient::fromSettings()->visionGenerate($prompt, [$base64], $this->model, ['temperature'=>0,'seed'=>42])`; feed `result['text']` into the existing `parseResponse()`. Drop the localhost curl. Keep image-read + buildPrompt + parseResponse.
- **describeImageAction::callLocal** + **describeObjectAction::callLocal** (after unlock): same swap (`[$base64]` / `[$base64Collage]`, model from config). `callCloud()` untouched. `voiceConfig.local_llm_url` becomes an unused override (the 'local' provider now means "local models *via the gateway*", per the host rule).

## Cross-cutting
- Key: `AiGatewayClient::fromSettings()` (ahg_ai_settings) — no more keyless calls.
- Model: pass the caller's configured model (`llava:7b`); ensure gateway-routable (prereq above).
- Backward-compat: gateway-first; **recommend failing closed** with a clear error if no key (don't silently hit a node). Optional: retain a localhost fallback (like EmbeddingService) for dev resilience — decide per environment.
- Response shapes preserved → `parseResponse()`/voice consumers unchanged.

## Testing
- ConditionAIService: analyze a condition photo → damage/severity parsed.
- Voice: `/ahgVoice` describe-image + describe-object (single + collage) → description returned.
- All three must show the call hitting the gateway (keyed) and the vision model responding. ⚠️ needs the model loaded — do a `/ai/v1/health` + a one-shot `visionGenerate` smoke first.

## Scope / locks
- ConditionAIService: in-scope now (not locked).
- ahgThemeB5 voice (describeImage/Object + voiceConfig): **Johan unlocking** → in-scope.
- AiGatewayClient (framework): not locked.

## Recommendation
Add `visionGenerate()` → do ConditionAIService → (after unlock) the two voice actions. Treat **gateway model-availability for `llava:7b`** as the one operator prereq to confirm before the smoke test.
