# Consolidated deploy order + open operator items — 2026-06-15

All code committed/released: **atom-framework v2.13.10**, **atom-ahg-plugins v3.71.27** (both git-clean, pushed to origin). PSIS holds the code on disk (it's the dev box). This is the activation + operator checklist.

## 1. PSIS activation (do once, after the last release)
opcache.validate_timestamps=0 → code edits aren't live until:
```
cd /usr/share/nginx/archive && sudo rm -rf cache/qubit/prod/* && sudo systemctl restart php8.3-fpm
```

## 2. DB / operator actions — STATUS (all DONE on PSIS)
- ✅ `research_researcher.experience_level` ALTER (research mode)
- ✅ `ahg_audit_log` seal cols + `ahg_audit_chain_state.last_seq` ALTER; ✅ `audit:chain --keygen` (key `ed25519:e0c9…`, minted as www-data)
- ✅ `image_alt_text` table + `ahgAccessibilityPlugin` enabled (atom_plugin) + symlink
- ✅ `ahg_ai_chatbot_message` + `ahg_translation_memory` tables
- ✅ gateway key + api_url already seeded in `ahg_ai_settings` (key `ahg_live_…`)
- (password migration / LlmService AEAD / gateway routing: no DB change)

## 3. OPEN operator items (action needed)
1. **Gateway vision model** — the gateway GPU node currently serves only `nomic-embed-text` + `qwen3:8b` (verified `/ai/v1/ollama/api/tags`). **No vision model loaded** → condition-photo analysis + voice describe-image/object fail-closed (graceful) until **`llava:7b`** (or another vision model) is loaded/routable on the gateway. Then they work unchanged. (Or tell Claude which vision model the gateway will serve to repoint the config.)
2. **Propagate to other instances** — ANC (`/usr/share/nginx/atom`) + WDB pull from origin to get framework v2.13.10 + plugins v3.71.27 (separate deploys; chown→stash→pull→migrate→reindex per the WDB runbook). Deferred to Johan.

## 4. Smoke tests (post-activation)
- Research mode: `/research/projects` shows the mode guide + sidebar selector; switching mode changes sidebar items (test as a user WITH a researcher profile).
- Audit seal: `php symfony audit:chain` → "chain intact … Seal: N signed, N verified, 0 failed".
- Accessibility: `/accessibility/alt-text` (302 login), author alt on an image.
- AI assistant: translate a field + fetch languages (via gateway); chatbot turn persists.
- OCR via gateway: index a PDF (page-index) → OCR text returns (`/ai/v1/htr/legacy/ocr/extract`).
- Vision (after model loaded): condition-photo analysis + voice describe-image return descriptions.
- Password: an existing user logs in → row flips to `salt=''` + Argon2id; `tools:add-superuser` account can log in.

## 5. Accepted / deferred (no action)
- `style-src 'unsafe-inline'` kept by decision (Option A) — compensated by `script-src` no-unsafe-inline.
- Vision callers fail-closed until #3.1 (no crash).
- Long-horizon: inline-style-attribute refactor (only if compliance mandates), tier-2 locked-plugin `<style>`/voice already covered, flagged ahgCore print-label `<style>` (locked, harmless).

## 6. Separate engagements (not part of this deploy)
- **Wits RARI upgrade + new VM** — BLOCKED on Wits SSH access + a prod RARI DB dump (`/usr/share/nginx/rari29` has code, no DB). Plan: docs/sessions/2026-06-15-wits-rari-upgrade-and-new-install-plan.md.

## Releases shipped this session (reference)
Research-mode levels, #150/#151/#152 twins, 3D parity, accessibility plugin, AI assistant extras, audit Ed25519 seal, full security remediation (authZ, path-traversal, secrets/RNG, unserialize, password Argon2id P0–P4, LlmService AEAD), full gateway-routing (translate/page-index/OCR/discovery/worker/vision), CSP nonces + style decision.
