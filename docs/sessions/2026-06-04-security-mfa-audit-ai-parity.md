# Security (MFA + audit chaining) and AI parity — closing #126, #133, #121

**Date:** 2026-06-04
**Released:** **NOT yet released.** All changes are live on PSIS (applied via DB migrations + cache‑clear/restart) but **not committed to git**. `./bin/release` commands handed to the maintainer.
**Plugins:** ahgSecurityClearancePlugin, ahgAuditTrailPlugin, ahgAIPlugin (+ atom‑framework composer; docs in atom‑extensions‑catalog).
**Issues:** closed **#126** (rights‑privacy‑compliance, rescoped to MFA + audit chaining), **#133** (WebAuthn twin), **#121** (ai parity). **#121 catalogue chatbot** built.
**Scope:** Closed the three open PSIS‑parity epics with WebAuthn passkey MFA, per‑role MFA enforcement, tamper‑evident audit‑trail hash chaining (two logs), and a RAG collection chatbot — all e2e/live‑verified.

## Trigger
"do all three, then build webauthn mfa" → then sequentially: per‑role MFA (#738), crypto audit chaining, ahg_audit_log chaining (seal‑forward), AI parity (#121), a nav link, and AI documentation.

## What shipped

### WebAuthn / FIDO2 passkey MFA (#126 / #721, twin #133) — ahgSecurityClearancePlugin
- `web-auth/webauthn-lib 5.3.5` (atom‑framework vendor) + `ahg_webauthn_credential` table + `WebAuthnService` (full 5.x rewrite — the heratio port targeted 4.x).
- Enrolment/management UI `/security/2fa/webauthn` + `web/js/webauthn.js`; passkey is an alternative second factor that satisfies the existing `security_2fa_session` gate via `SecurityClearanceService::create2FASession`.
- **e2e proven** via Playwright CDP virtual authenticator (`tests/webauthn-mfa.spec.ts`): register → assert → cleanup, green.

### Per‑role MFA enforcement (#738) — ahgSecurityClearancePlugin
- Session‑wide gate via a `controller.change_action` dispatcher listener (the intended `SecurityClearanceFilter` was dead code, never in any `filters.yml`). Admin policy at `/security/2fa/policy` (`mfa_per_role_enabled` + `mfa_required_roles` in `ahg_settings`). Fail‑open; securityClearance module exempt (escape hatch). `tests/mfa-per-role.spec.ts` green.

### Tamper‑evident audit chaining (#126) — two logs
- **security_access_log** (ahgSecurityClearancePlugin): SHA‑256 chaining in `logAccess` (`appendHashChainedAccessLog`), `verifyAuditChain`, CLI `security:audit-verify`, admin `/securityAudit/integrity`. Also fixed a silent bug — `logAccess` wrote `compartment_id`/`session_id` columns that didn't exist — and dropped FK `fk_sal_object` (CASCADE) so object deletion can't erase audit history.
- **ahg_audit_log** (ahgAuditTrailPlugin) — **seal‑forward**: new `ChainedAuditWriter` (append/verifyChain/seal), all 5 write paths routed through it (repo, request filter, AuditHelper, listener, AhgAuditService), single‑row `ahg_audit_chain_state` lock, fail‑open. Migration sealed from id=460778; 453K historical rows left unchained and skipped by the verifier. CLI `audit:chain [--seal]`, admin `/admin/audit/integrity`. **Live‑verified** ("chain intact", content edit + row deletion both caught). `tests/audit-chain-integrity.spec.ts`.

### Collection chatbot (#121) — ahgAIPlugin
- RAG Q&A over the catalogue: `CollectionChatbotService` retrieves via MySQL FULLTEXT over **published** descriptions, generates via the existing `\LlmService` provider. `/ai/assistant` page + `/ai/assistant/ask` JSON, `web/js/collection-chatbot.js`, cited source links, fail‑open to a record list. **Live‑verified** (`mode:"ai"`, 6 cited sources). `tests/collection-chatbot.spec.ts`.
- **Nav link** "Collection assistant" under Manage — idempotent `ai:install-menu` task (nested‑set safe + integrity check), also folded into `ai:install`.
- Cross‑verify confirmed the other #121 "gaps" genuinely exist already (ahgAiCompliancePlugin EU‑AI‑Act/receipts, ahgAIPlugin DONUT) — unlike #126's bad rescoping.

### Documentation (atom‑extensions‑catalog)
- `docs/technical/AI_ARCHITECTURE.md` (+ `.docx`) — technical/install reference.
- `docs/Collection_Assistant_User_Guide.md` (+ `.docx`) — end‑user guide.

## Key gotchas (captured in agent memory)
- webauthn‑lib 5.x removed `PublicKeyCredentialLoader`; getters → public properties; validators take a `CeremonyStepManager` + host.
- Binary `WHERE` over a utf8mb4 connection never matches a `VARBINARY` — use `UNHEX(?)`.
- Two `SecurityClearanceService` classes (global plugin vs framework `AtomExtensions\`); only the global one has the 2FA‑session methods the filter reads.
- `RouteLoader->any()` matches GET but **not** POST — form saves need a dedicated `->post()` route + clean URL (no `/index.php`, no trailing slash → 301 drops the body).
- ahgAuditTrailPlugin autoloader didn't map `AtoM\Framework\Plugins\AuditTrail\` — fail‑open masked it as unchained rows.
- The theme `_mainMenu.php` renders only a **fixed** top‑level menu set but renders their children generically — nav additions must attach under add/manage/import/admin.

## Verification
All PHP `php -l` clean; five Playwright specs green (`webauthn-mfa`, `mfa-per-role`, `audit-integrity`, `audit-chain-integrity`, `collection-chatbot`); both audit chains live‑verified with direct tamper tests; chatbot returned a real grounded answer; nav link renders and resolves. Site health unchanged throughout.

## Not done / follow‑ups
- **Release** — nothing committed; per‑repo `./bin/release` pending (ahgAuditTrailPlugin + ahgAIPlugin are "stable" plugins, unlocked by the explicit build requests).
- Other instances need the migrations (`add_webauthn_credential_table.sql`, `add_audit_chain_columns.sql`, `add_audit_chain.sql`), `composer install`, and `ai:install-menu` on deploy.
- Optional chatbot upgrades: vector/Qdrant retrieval, WhatsApp channel.
- #126 remaining medium item beyond MFA, if any, and #121 vector retrieval — tracked on the issues.
