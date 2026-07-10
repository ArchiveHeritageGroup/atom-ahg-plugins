# Non-admin user self-access (profile view + password change) + login hash-scheme fix

**Date:** 2026-07-06
**Reported on:** archaeology (Wits) — "Sorry, you do not have permission" when a non-admin (Thembiwe, editor) viewed her own profile / changed her password. Same code on PSIS.
**Releases:** `atom-ahg-plugins` **v3.79.68** + `atom-framework` **v2.13.16**. Live on PSIS + archaeology.

## Two root causes

### 1. authZ regression — `ahgUserManagePlugin` (v3.79.68)
The plugin registers `/user/:slug` → `userManage/view` (route name `user_view_override`), shadowing base AtoM's `user/index`. `userManage/view` (and `edit`) were hard-gated `isAdministrator()` → any non-admin got forwarded to `admin/secure` (HTTP 403) on their **own** profile. Base AtoM's `user/index` allowed self-view.

Password change was bundled inside the admin-only `edit`, and the user-menu "Change password" link (`ahgThemeB5Plugin _userMenu`) points to `/user/passwordEdit` **with no slug** → the base `passwordEdit` action built `new QubitUser()` (id null) → non-admin self-check `null != user_id` → secure page.

**Fixes (ahgUserManagePlugin):**
- `executeView`: load record first, then allow admin **or** the account owner (`isSelf`).
- New `executePassword` action + route `/user/:slug/password`, self+admin gated, current-password confirmation for non-admins, password-only `UserCrudService::update` (verified non-destructive — groups/profile untouched).
- Routed the slug-less `/user/passwordEdit` menu link to `userManage/password`; the action defaults to the current user when no slug. Removed the base slug-less passthrough.
- `viewSuccess` action bar made role-aware (admin actions admin-only; "Change password" shown to owner/admin).

### 2. login can't verify plugin/CLI-set passwords — base `QubitUser::checkCredentials` (framework v2.13.16)
The 2026-06-15 password migration moved writes to **Argon2id-over-plaintext** (empty salt) and built a **scheme-aware** `PasswordService::verify` (salt = discriminator: empty→new, non-empty→legacy `sha1(salt.pw)`). But base AtoM's web-login `checkCredentials` was never wired to it — it did a **legacy-only** `password_verify(sha1(salt.pw), hash)`. So any password set via `UserCrudService::update`, `tools:reset-password`, or `tools:add-superuser` (all use `PasswordService::hash` → empty salt) produced a hash the login **could not verify** → silent login failure → anonymous → 403 on every secure page.

**Fix:** `checkCredentials` now calls `\AtomFramework\Core\Security\PasswordService::verify(...)` (with a legacy inline fallback if the class is missing). Stored durably at `atom-framework/patches/lib/model/QubitUser.php`; applied live to both instances' `lib/model/QubitUser.php` (base AtoM isn't a git repo). **Zero regression** — existing non-empty-salt users hit the identical legacy branch.

## Decisive debugging step
Reading the live PHP session file (`/var/lib/php/sessions/sess_<id>`) showed credentials were correct (`['authenticated','editor']`, `user_id` matched) and `currentInternalUri = userManage/view … → admin/secure` — proving the request reached an admin-only action, not a credential problem. That redirected the whole investigation to the route override.

## Verification (archaeology, throwaway editor account, created + deleted)
- Editor self-view → **200** (was 403); other users' profiles → 403; admin-only user list → 403.
- `/user/:slug/password` AND slug-less `/user/passwordEdit` → 200 for owner; wrong current-password rejected; save → 302; **new password logs in**.
- Login scheme-aware: new-scheme (empty salt) login now 302 (was failing); legacy login still 302 (no regression); wrong password still rejected.
- PSIS health after base-auth change: home 302, login form renders, no new `ahg_error_log` entries.

## Notes / follow-ups
- `tools:add-superuser` is broken on archaeology (strict-mode `actor.source_culture` has no default on INSERT) — separate minor bug.
- Other AtoM instances sharing this base (`atom`/ANC if Symfony) would need the same `checkCredentials` patch applied if they exhibit the login issue.
