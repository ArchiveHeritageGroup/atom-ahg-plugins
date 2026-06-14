# AI-wave: gateway-compliant AI client + semantic catalogue search (2026-06-14)

**Repos:** atom-framework + atom-ahg-plugins · **Status:** built + lint-clean, NOT released, NOT yet activated (needs gateway key).

## Why
The PSIS AI stack bypassed the AHG AI gateway: every call went direct to node ports (`:11434` Ollama, `:5004` workers) or external provider APIs — violating the host-wide rule that all app AI calls route through `https://ai.theahg.co.za/ai/v1/...`. There was also no semantic search of the catalogue (chatbot #121 was FULLTEXT-only).

## Key finding (gateway routes)
No gateway routes were missing. The `/ai/v1/ollama/{full_path}` **passthrough** already exposes embeddings/chat/generate (`api/embeddings`, `api/chat`, `api/generate`) — all return 401 (exist, need key), not 404. KM's `nomic-embed-text` retrieval already rides it, and the passthrough deliberately skips embeddings paths from its model-fallback rewrite. So the AI-wave routes through the passthrough; the only prerequisite is a `gateway`-scoped key for PSIS.

## Built — increment 1 (plumbing)
- **`atom-framework/src/Services/AI/AiGatewayClient.php`** — canonical client. `embed()`/`embedBatch()` → `/ollama/api/embeddings`; `chat()`/`generate()` → `/ollama/api/chat`; `isAvailable()` → `/health`. Reads `ahg_ai_settings` feature='gateway' (api_key falls back to feature='general'). Built on `HttpClientService` whose SSRF guard **blocks private IPs** → structurally can't be pointed back at a node. Fails closed without a key.
- **`ahgSemanticSearchPlugin/EmbeddingService::getEmbedding()`** — gateway-first, falls back to legacy Ollama-direct when no key.
- **`ahgAIPlugin` `GatewayProvider`** (new) registered as `provider='gateway'` in `LlmService` — chatbot/cataloguer/copilot move to the gateway via a one-row `ahg_llm_config` flip; existing Ollama/OpenAI/Anthropic providers + #141 guardrails untouched.

## Built — increment 2 (semantic catalogue search)
- **`ahgAIPlugin/lib/Services/CatalogueVectorService.php`** — embeds published IOs via the gateway (nomic-embed-text, 768-dim) into a **separate** Qdrant collection `{db}_io_nomic` (= `archive_io_nomic`). Kept apart from Discovery's MiniLM `archive_records` (384-dim, 693 pts — left untouched; incompatible vector space). `indexBatch()`, `publishedCount()`, `search()`. Qdrant accessed direct on localhost:6333 (vector store, not a GPU node → not a gateway-rule bypass).
- **`ai:index-catalogue` CLI task** (`aiIndexCatalogueTask`) — `--dry-run`, `--limit`, `--batch`, `--culture`. Builds/refreshes the collection.
- **`CollectionChatbotService::retrieve()`** — now **hybrid**: semantic (gateway) + FULLTEXT, deduped by id, semantic-first. Falls back to pure FULLTEXT whenever the index is unavailable → zero behaviour change before the key/index exist.

## Verification
- All files `php -l` clean.
- Qdrant REST shapes (create `PUT /collections/{n}` `{vectors:{size,distance}}`; upsert `PUT /points?wait=true`; query `POST /points/query` `{query,limit,with_payload,score_threshold}` → `result.points`) validated live against PSIS Qdrant with a throwaway 3-dim collection (create→upsert→query hit→delete, all `ok`).
- Not runtime-tested end-to-end: blocked on the gateway key.

## Activation (Johan)
1. Mint a `gateway`-scoped API key for PSIS in the gateway admin console.
2. `INSERT INTO ahg_ai_settings (feature,setting_key,setting_value) VALUES ('gateway','api_key','<key>'),('gateway','base_url','https://ai.theahg.co.za/ai/v1');`
3. `php symfony ai:index-catalogue --dry-run` then `php symfony ai:index-catalogue` (run as www-data; after release + `cc`).
4. *(optional, generation→gateway)* `UPDATE ahg_llm_config SET provider='gateway', model='qwen3:14b' WHERE is_default=1;`

## Notes / next
- nomic vs MiniLM dimension split is intentional — never point this at `archive_records`.
- Future: re-point Discovery's VectorSearchStrategy at the gateway too (would require re-indexing `archive_records` to 768-dim) — deferred; it works locally today.
