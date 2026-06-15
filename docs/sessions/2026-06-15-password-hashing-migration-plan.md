# Password-hashing migration — PLAN — 2026-06-15

## ✅ P0 + P1 IMPLEMENTED (2026-06-15, unreleased)
- **P0:** new `atom-framework/src/Core/Security/PasswordService.php` — `algo()` (Argon2id>Argon2i>default), `hash()` (→ password_hash + salt=''), `verify($pw,$hash,$salt)` (salt empty=new / non-empty=legacy), `needsUpgrade()`. Functionally unit-tested in isolation: legacy verify ✓, new verify ✓, upgrade ✓, wrong-pw rejected ✓, cross-scheme no-false-positive ✓, empty-hash ✓ (9/9).
- **P1 (all login verify paths made scheme-aware — more than the planned 2):** `AuthService.php:68` + `UserService.php:196` now `PasswordService::verify(...)` **and rehash-on-login** when `needsUpgrade` (transparent upgrade, best-effort). Also fixed two more login-verify paths that had a `!salt` guard which would have rejected upgraded (salt='') users: `ahgUserManagePlugin/UserCrudService.php:404` (verifyPassword) and `ahgRegistryPlugin actions.class.php:5673` (registry login) — both now `PasswordService::verify`, keeping the raw-sha1 import fallback (now `hash_equals`). All lint clean.
- **Deploy P1 ALONE is safe + starts migrating users:** verify handles both schemes; legacy users keep logging in and get upgraded to Argon2id+salt='' on next login. Write sites still create legacy hashes (P2) — fine, they verify via the legacy branch. **No lockout risk.**
- **`PasswordPolicyService.php:131`** (password-REUSE history check, not a login gate) deferred to P2 (it's part of the change-password flow).
- **Files:** atom-framework {PasswordService.php(new), AuthService.php, UserService.php}; atom-ahg-plugins {ahgUserManagePlugin/UserCrudService.php, ahgRegistryPlugin/.../actions.class.php}.
## ✅ P2 IMPLEMENTED (2026-06-15, unreleased) — write sites → PasswordService::hash
All non-locked create/change/reset/CLI write sites now mint Argon2id-over-plaintext + `salt=''`:
- atom-framework: `UserService::updatePassword`, `Console/.../AddSuperuserCommand.php:64`, `Console/.../ResetPasswordCommand.php:49`.
- atom-ahg-plugins: `ahgUserManagePlugin/UserCrudService.php` (create + update), `ahgUserRegistrationPlugin/RegistrationService.php` (register; flows through request→approval which copies salt=''), `ahgRegistryPlugin actions.class.php` (register insert + 2 reset paths).
- **Bonus bug fix:** AddSuperuserCommand + ResetPasswordCommand previously stored a **raw `sha1()`** as password_hash (unverifiable by AuthService — same class as the ArchiveImporter bug); now correct.
- **Deferred (locked caller):** `PasswordPolicyService::isPasswordReused` + its ONLY caller `ahgCorePlugin/.../passwordEditAction.class.php` are both in locked ahgCorePlugin; the change-password write + reuse-history stay old-scheme there (verify+upgrade still handle those users). The reuse-check was already salt-imperfect and is not a login gate → moved to the locked-plugin pass (with P4).
- All lint clean. New users are now Argon2id-from-birth; existing users upgrade on login (P1).
- **P4 DONE — see below.**

## P4 IMPLEMENTED (2026-06-15, unreleased) — locked plugins (Johan-authorized unlock)
- **ahgResearchPlugin** 4 direct-DB write sites → `PasswordService::hash` (Argon2id + salt=''): createAtomUser (1069), reactivate-rejected (1006), researcher reset (1166 — was argon(plaintext) with a RANDOM salt → scheme-aware verify would have mis-treated as legacy and rejected: BUG fixed), admin reset (1344 — was raw sha1(): BUG fixed). Lint clean.
- **ahgCorePlugin `passwordEditAction` + `PasswordPolicyService` — correctly LEFT AS-IS:** the actual password write delegates to base-AtoM `QubitUser::setPassword` (lib/model/ — HARD-LOCKED), which writes the legacy dual-layer (`salt=md5(rand.email)`, `argon(sha1(salt.pw))`). That is verifiable by PasswordService's legacy branch and upgrades on next login. The reuse-history uses *unsalted* sha1 consistently for both check + record → already scheme-independent. Nothing to change.
- **Single unavoidable legacy writer remaining = base-AtoM `QubitUser::setPassword`** (locked; used by the Symfony user-edit path). Cannot change without modifying base AtoM. Impact is nil: those users are transparently migrated to Argon2id on their next login by P1's verify+upgrade.

## ✅ MIGRATION COMPLETE (P0–P4, unreleased)
Every app-owned write site mints Argon2id-over-plaintext + salt=''. All login-verify paths support both schemes and upgrade legacy users on login. Raw-sha1 storage bugs (CLI add-superuser/reset-password, ArchiveImporter, research admin-reset) all fixed; research reset random-salt bug fixed. Only base-AtoM QubitUser still writes legacy (locked, auto-upgraded on login).

## P3 IMPLEMENTED (2026-06-15, unreleased)
`ahgPortableExportPlugin/ArchiveImporter.php:666` — imported users were stored with a **raw sha1($salt.$tempPassword)** as password_hash (AuthService can't verify it). Now `PasswordService::hash($tempPassword)` (Argon2id + salt=''). Temp password is still random + must-be-reset; this just makes the stored hash valid. Lint clean. **No raw-sha1-as-password_hash writes remain in code** — only locked ahgResearch/ahgCore still use the old verifiable dual-layer argon(sha1) scheme (P4).

---


## Honest current-state assessment (refines the audit's "CRITICAL")
The login scheme is a **dual layer**: store `password_hash = password_hash(sha1(salt . plaintext), ARGON2I)`, `salt = md5(rand(100000,999999) . email)`; verify `password_verify(sha1(salt . input), password_hash)`. Documented in `atom-framework/src/Services/AuthService.php`.

So the stored hash is **Argon2i — NOT trivially crackable**. The real defects are: (a) **weak inner-salt RNG** (`md5(rand())`, ~900k space) — but Argon2i adds its own 16-byte random salt internally, so impact is largely neutralised; (b) a **redundant sha1 pre-layer**; (c) **inconsistency** across sites; (d) one **real latent bug**. Net severity: **MEDIUM-HIGH hardening + consistency fix**, not an emergency. The `user` table has only `password_hash` + `salt` (no base-AtoM `sha1_password`), and base AtoM Symfony login is not wired (factories user.class absent) → the framework `AuthService` is canonical, so this migration is self-contained in OUR code (no base-AtoM change needed).

## All affected sites (the migration must cover all, or it locks users out)
**Verify (login):** `atom-framework/AuthService.php:68`, `atom-framework/UserService.php:196`, `ahgRegistryPlugin actions.class.php:5800,6004`.
**Write (create/change/reset):** `ahgUserManagePlugin/UserCrudService.php:163/166,226,408`; `ahgUserRegistrationPlugin/RegistrationService.php:49,51`; `atom-framework/UserService.php:210`; `atom-framework/Console/.../AddSuperuserCommand.php:64`; `atom-framework/Console/.../ResetPasswordCommand.php:49`; `ahgRegistryPlugin actions.class.php:5120`; **`ahgResearchPlugin actions.class.php:1007,1070,1344` (⚠️ LOCKED plugin — needs explicit unlock)**.
**Already-modern (reference / no change):** `atom-framework/Heritage/Contributions/ContributorService.php` — `heritage_contributor` store already uses `password_hash($pw)` + `password_verify($pw,$hash)` correctly. Use as the template.
**Inconsistencies / bugs found:**
- `ahgResearchPlugin actions.class.php:1167` already uses `password_hash($password)` directly (new style) while 1008/1071 use the old style → researcher passwords are already mixed.
- 🐞 `ahgPortableExportPlugin/ArchiveImporter.php:666` stores `'password_hash' => sha1($salt.$tempPassword)` — a **raw sha1, NOT wrapped in password_hash()** → `password_verify()` will always fail → **imported users cannot log in today**. Fix as part of this work.
- `ahgResearchPlugin/backups/20260109_193045/` — dead backup dir, ignore.

## Target scheme
`password_hash($plaintext, PASSWORD_ARGON2ID)` (fallback ARGON2I → DEFAULT); verify `password_verify($plaintext, $hash)`. Drop the sha1 + weak-salt inner layer.

## Migration mechanism — transparent verify-on-login upgrade, NO schema change
The `user` table is a core table (cannot add a flag column). **Reuse the existing `salt` column as the scheme discriminator:**
- **`salt` non-empty → legacy** hash. **`salt` empty/NULL → new** hash.
- New users & rehashes: write Argon2id over plaintext and set `salt = ''`.

This is deterministic (one verify per login, no double-hashing) and self-migrating.

### Phase 0 — one shared helper (kills the inconsistency)
New `atom-framework/src/Core/Security/PasswordService.php`:
- `hash(string $pw): array` → `['password_hash' => password_hash($pw, ARGON2ID), 'salt' => '']`.
- `verify(string $pw, string $hash, ?string $salt): bool` → if `salt` empty: `password_verify($pw, $hash)`; else legacy `password_verify(sha1($salt.$pw), $hash)`.
- `needsUpgrade(?string $salt): bool` → `salt` is non-empty (legacy) OR `password_needs_rehash($hash, ARGON2ID)`.
Every site below calls this — single source of truth.

### Phase 1 — verify sites (backward-compatible; existing users keep logging in)
At each verify site: `if (PasswordService::verify($pw, $u->password_hash, $u->salt)) { if (PasswordService::needsUpgrade($u->salt)) { $n = PasswordService::hash($pw); DB::table('user')->where('id',$u->id)->update($n); } /* logged in */ }`. On a legacy user's first correct login they're rehashed to Argon2id + `salt=''`. Rehash failure is non-fatal (they logged in; retried next time).

### Phase 2 — write sites
Replace every create/change/reset with `PasswordService::hash($pw)` and store `password_hash` + `salt=''`. Includes CLI `AddSuperuser`/`ResetPassword` (these are the backstop — they always mint a new-scheme hash, so no one can ever be permanently locked out).

### Phase 3 — fix the ArchiveImporter raw-sha1 bug
`ArchiveImporter.php:666` → `PasswordService::hash($tempPassword)`.

### Phase 4 — ahgResearchPlugin (deferred / needs unlock)
Its 3 write sites stay legacy until the plugin is unlocked — but that is SAFE: AuthService's legacy branch still verifies those accounts, and they upgrade on next login. So research can be done last, separately.

## Rollout, testing, rollback
- **Reversible:** Phase 1 is additive (legacy branch retained); rollback = revert code. Rehash overwrites the old hash only on a *successful correct-password* login, so no destructive change to credentials.
- **Test (scratch user, never blind against real accounts):** create legacy user (old scheme) → login works → row now has `salt=''` + Argon2id → login again (new path) works → wrong password fails → `tools:reset-password` works → new register → admin "force reset". Verify a `heritage_contributor` login still works (untouched).
- **Telemetry:** count `user WHERE salt <> ''` over time → migration progress. Legacy branch can be removed once ~0 (optional; harmless to keep).
- **Order of deploy:** Phase 0+1 first (verify stays compatible) → then Phase 2/3 → research last. Never deploy a write-side change before the verify side understands both schemes.

## NOT doing
- No change to base AtoM. No `user` schema change. No forced mass password reset (transparent upgrade instead). ahgResearchPlugin only on explicit unlock.

## Recommendation
Approve Phase 0–3 (framework + ahgUserManage/ahgUserRegistration/ahgRegistry/ahgPortableExport — all non-locked). Do ahgResearchPlugin (Phase 4) when you unlock it. Also fold in the ArchiveImporter bug (it's currently breaking imported-user logins).
