# AI assistant extras — chatbot persistence + translation memory — 2026-06-15

**Source:** DB-audit archive plan build-order #4. **Plugin:** ahgAIPlugin (feature request unlocks the chatbot/translation home). **Status:** built + CLI-verified live, tables created. Unreleased.

## Verified gaps (verify-first)
PSIS has `ahg_translation_log` (write log) and the live #121 `CollectionChatbotService` (FULLTEXT/semantic RAG) — but **neither `ahg_ai_chatbot_message` (conversation persistence) nor `ahg_translation_memory` (TM reuse)**. Both proven non-stubs on Heratio (12 / 8 rows). Ported. (`ahg_ner_custom_entity` / `ahg_ai_prompt_template` are 0-row stubs → skipped.)

## Delivered
- `database/migrate_ai_assistant_extras.sql` — `ahg_ai_chatbot_message` (session_id, role **VARCHAR not ENUM** per rule #5, content, sources JSON, grounding_score, model, tokens_in/out) + `ahg_translation_memory` (sha256 source hash, source/target lang, texts, provenance, confidence, hit_count, last_used_at; unique (hash,target_lang)). **Created on PSIS.** No `atom_plugin` insert (ahgAIPlugin already enabled).
- `CollectionChatbotService::newSessionId/persistTurn/history` — best-effort conversation persistence (missing table never breaks chat).
- Wired `aiActions::executeAssistantAsk` (`/ai/assistant/ask`): threads a `session_id` across turns (accepts client's or mints one), persists the user turn + assistant turn (sources/model/tokens), returns `session_id` in the JSON.
- NEW `TranslationMemoryService` (lookup/store/stats) — lookup bumps hit_count+last_used_at; store upserts on (hash,target_lang) with a **provenance rank guard** (reviewed>human>machine) so a human edit is never clobbered by a later machine pass.
- Wired `TranslateCommand::translateObject` (`ai:translate`): TM lookup before Argos (skip the Python call on a hit), store on a miss. Existing i18n write + `ahg_translation_log` unchanged.

## Verified
- All `php -l` clean.
- CLI (live DB): chatbot — newSessionId + 2 persisted turns read back via history() with model/sources; TM — miss→store→hit, and a human entry survived a later machine store (rank guard), stats {entries,hits,by_provenance} correct, hit_count incremented. **Both tables cleaned to 0 rows after the test.**
- HTTP `/ai/assistant/ask` (anon) → 200 login page (AtoM auth gate redirects before the action), no 500 → route resolves + action compiles. Persistence is authenticated-only (CLI-verified).

## Activation (hand to Johan)
Tables already created. Just activate the code + release:
```bash
cd /usr/share/nginx/archive && sudo rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm   # (already done this session)
cd /usr/share/nginx/archive/atom-ahg-plugins
./bin/release patch "ahgAIPlugin: AI assistant extras — chatbot conversation persistence + translation memory reuse"
```
