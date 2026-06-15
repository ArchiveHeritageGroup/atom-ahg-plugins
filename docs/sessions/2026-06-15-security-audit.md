# Security audit — atom-ahg-plugins + atom-framework — 2026-06-15

Four parallel audits (CSP, authZ/ACL, injection/XSS/file, secrets/SSRF/crypto). Scope = our code only (NOT base AtoM apps/lib/plugins/vendor). Findings below marked **[VERIFIED]** (I read the code), **[FIXED]**, or **[AGENT]** (reported, not yet hand-verified). Agents over-report — treat [AGENT] as a lead until verified.

## FIXED this session (verified real, in ahgResearchPlugin — RARI-critical module)
1. **[FIXED] Privilege escalation — `executeViewResearcher`** (`modules/research/actions/actions.class.php`): was `isAuthenticated()` only, then approved/suspended any researcher by id on POST → any logged-in user (incl. a self-registered researcher) could approve/suspend accounts. Now admin-gated (mirrors the correct twins at lines 1236/1256). **Especially important for RARI** (public researcher self-registration).
2. **[FIXED] Missing admin gate — `targetJournalDeleteAction`**: deleted shared target-journal directory entries with `isAuthenticated()` only (table has no per-user column = shared/curated). Now admin-gated.

## FIXED — tranche 2 (path-traversal trio, verify-first)
3. **[FIXED] Arbitrary directory read (non-admin) — `ahgIngestPlugin` `executeUpload` `directory_path`**: only `requireAuth()` + `requireSessionOwner` (and any logged-in user can create their OWN session) → any authenticated user could point `directory_path` at /etc, /root, etc. and have the ingest service process it. The genuine HIGH of the trio. Fixed: the server-directory branch is now **admin-only** (file upload still works for all; arbitrary server-path ingest is admin-gated). Preserves the documented "ingest from server directory" feature for staff.
4. **[FIXED] Arbitrary file read — `ahgRicExplorerPlugin` `executeAjaxSyncProgress` `log_file`**: unconstrained `file_get_contents($param)`. Admin-only (whole `ricDashboard` module is admin-gated in boot()), so lower severity, but hardened: `log_file` must `realpath()` into `<root>/cache/` and match `ric_sync_*.log`.
- **[NOT FIXED — by design + locked] `ahgPreservationPlugin` `executeApiVerifyBackup` `path`**: backup-existence/size oracle — but it calls `checkAdminAccess()` (**admin-only**) so it's a low-risk admin capability (admins legitimately verify backups at custom paths), AND ahgPreservationPlugin is a stable/DO-NOT-MODIFY plugin. Left as-is; would need explicit unlock + a base-dir-with-override design to harden without breaking custom backup locations.

## FIXED — tranche 4/5 (hardcoded key + weak token RNG)
5. **[FIXED] Hardcoded fallback API key `ahg_ai_demo_internal_2026`** — agent found 2 sites; grep found **6** (verify-first): ahgSettingsPlugin `aiServicesAction.class.php:35` (settings seed → ''), `aiServicesSuccess.php:804` + `aiServices.blade.php:817` (JS fallback → ''), ahgTranslationPlugin `AhgTranslationService.php:27` (→ '' fail-closed), ahgAIPlugin `actions.class.php:1556,1655` (config default → ''). All now empty → fail closed; PSIS already has its real gateway key in ahg_ai_settings so no behavior change. (NOTE separately: the same defaults hardcode a DIRECT node endpoint `192.168.0.112:5004` — violates the ai.theahg.co.za gateway rule; flagged, not changed here.)
6. **[FIXED] Weak CSRF token** ahgReportsPlugin `reportUserAction.class.php:49` — `md5(session_id().microtime().mt_rand())` → `bin2hex(random_bytes(16))`.
7. **[FIXED] Weak API-key RNG** ahgUserManagePlugin `UserCrudService.php:346` — `openssl_random_pseudo_bytes(8)` → `random_bytes(16)` (existing stored keys unaffected; new keys 32-hex CSPRNG).
8. **[FIXED — locked plugins, Johan-authorized 2026-06-15]** ahgSecurityClearancePlugin `SecurityClearanceService.php:1280` watermark/clearance code → `strtoupper(substr(bin2hex(random_bytes(8)),0,12))` (same 12-char format); ahgCorePlugin `user/editAction.class.php:369` OAI API key → `bin2hex(random_bytes(16))`. All token/key RNG now CSPRNG; the only remaining `openssl_random_pseudo_bytes` is the LlmService AES-CBC **IV** (part of the separate LlmService→EncryptionService crypto item, not token gen).
- Lower-value identifier RNG (ahgCart order ids, ahgAPI ref numbers — enumeration risk only) left for an opportunistic pass.

## FIXED — tranche 3 (unserialize hardening, atom-framework repo)
9. **[FIXED] `unserialize()` `allowed_classes => false`** — grep found **6** sites (agent listed 4). Hardened the 4 data-only/array sites: `ExtensionManager.php:886`, `Console/Commands/Tools/PluginsCommand.php:55`, `Routing/AhgMetadataRoute.class.php:104` (all plugin-name lists = strings), and `Compatibility/QubitModelTrait.php:447` (DB property values — already `is_array()`-guarded, the most attacker-relevant source). Functionally verified: enabled-plugins setting still deserializes (38-element string array).
   - **[NOT changed — by design]** `Services/CacheService.php:35` (generic `mixed` file cache via QubitCache wrapper — legitimately caches objects; cache dir is server-side, not attacker-writable) and `Compatibility/Action/sfParameterHolder.php:134` (generic param holder may carry objects; session-side). Hardening these would break object storage for marginal gain given low attacker-control.

## CRITICAL / HIGH — verified or high-confidence, NOT yet fixed
- **[AGENT] Path traversal** (file read/oracle from request param):
  - `ahgRicExplorerPlugin/modules/ricDashboard/actions/actions.class.php:324` — `file_get_contents($request->getParameter('log_file'))` → arbitrary file read. HIGH. (verify gating; fix = realpath() + whitelist dir.)
  - `ahgIngestPlugin/.../actions.class.php:187-209` — `directory_path` request param used as ingest source dir. HIGH (likely admin-gated → lower; confirm).
  - `ahgPreservationPlugin/.../actions.class.php:497` → `verifyBackup($path...)` — file existence/size oracle. HIGH.
- **[AGENT] Weak password hashing** — `ahgUserManagePlugin/lib/Services/UserCrudService.php:162-166,225,408` + `ahgUserRegistrationPlugin/lib/Services/RegistrationService.php:48` — `password_hash(sha1(md5(rand()+email).password))`. SENSITIVE: changing the scheme can lock users out → needs a careful migration (hash plaintext directly going forward; verify-on-login upgrade). Do NOT blind-edit.
- **[AGENT] Hardcoded fallback API key** `ahg_ai_demo_internal_2026` — `ahgSettingsPlugin/.../aiServicesAction.class.php:35`, `ahgTranslationPlugin/lib/Service/AhgTranslationService.php:27`. It's a fallback default (low real risk) but should be removed (read from settings, fail closed).

## MEDIUM
- **[AGENT] Weak CSRF token** `md5(session_id().microtime().mt_rand())` — `ahgReportsPlugin/.../reportUserAction.class.php:49`. Use `bin2hex(random_bytes(16))` (other endpoints already do).
- **[AGENT] Weak randomness for tokens/ids** — `uniqid()`/`mt_rand()`/`openssl_random_pseudo_bytes()` in ahgSecurityClearancePlugin (clearance codes), ahgCartPlugin (order ids), ahgAPIPlugin (refs), ahgUserManage/ahgCore (API keys). Use `random_bytes()`.
- **[AGENT] LlmService AES-256-CBC without auth tag** — `ahgAIPlugin/lib/Services/LlmService.php:334`. No HMAC; key stored in DB. Migrate to the framework's `EncryptionService` (XChaCha20-Poly1305, AEAD).
- **[AGENT] md5() for dedup/cache keys** (ErrorNotificationService, CCO vocab cache) — collision risk; prefer sha256.
- **[AGENT] File-extension allowlist** missing on DonorAgreement upload (`editAction.class.php:309`); zip extraction without size cap (`ahgIiifPlugin media actions:925`).

## CSP (the headline)
- **Mode:** currently **Report-Only** (`config/app.yml:68`) — violations are logged, not enforced. So missing-nonce items are not breaking *yet*.
- **`style-src 'unsafe-inline'`** in `config/app.yml:76` — defeats style CSP. Removing it would break **2,645 inline `style=` attributes + 33 inline `<style>`** → a refactor project, not a quick fix. (script-src correctly has NO unsafe-inline; no unsafe-eval anywhere.)
- **Inline `<script>` WITHOUT nonce: 37** (of 581 = ~94% compliant). Many (~14) are in `ahgThemeB5Plugin/modules/heritage.bak/` — a **backup/dead dir** (delete it). Genuine live ones: ahgGalleryPlugin (6), ahgLibraryPlugin (3), ahg3DModelPlugin (2/3), ahgStorageManagePlugin strongroom, ahgVendorPlugin, ahgLandingPagePlugin, a few action/service-generated `window.print()` blocks.
- **Inline `<style>` WITHOUT nonce: 33** — mostly PDF/print HTML generated in Services (Spectrum, Favorites, ReportBuilder, Research export) — these are print documents, lower risk; nonce them if CSP style enforcement is turned on.
- **CDNs** all whitelisted (jsdelivr/cdnjs/d3js/unpkg/fonts). GTM/googleapis via wildcard.

## POSITIVES (confirmed good — keep)
- **SSRF**: `ahgFederationPlugin/lib/HarvestClient.php` — exemplary (blocks 169.254.169.254/metadata, NO_PRIV_RANGE, DNS-rebind pin, no redirects, size cap). `AiGatewayClient` SSRF-guarded.
- **Signing**: C2pa / Inference / Audit seal use **Ed25519 (sodium) + random_bytes** — correct.
- **Framework `EncryptionService`**: XChaCha20-Poly1305 / AES-GCM AEAD, HKDF — state of the art (use it everywhere).
- No `eval`/`system` injection surface; no committed private keys; redirects use internal route arrays (open-redirect low).

## Recommended remediation order
1. **[DONE]** ahgResearchPlugin authZ criticals.
2. Path-traversal trio (ricExplorer/ingest/preservation) — add realpath()+whitelist. Confirm admin-gating.
3. `unserialize` allowed_classes in framework — per-call, after verifying each site's data.
4. Remove hardcoded fallback API key.
5. Token/CSRF randomness → random_bytes (mechanical, low risk).
6. Password-hashing migration (careful, verify-on-login upgrade) — SEPARATE planned change.
7. LlmService → framework EncryptionService.
8. CSP: delete heritage.bak; nonce the ~12 live inline scripts; treat style-src unsafe-inline removal as its own refactor.
