# WDB Production Upgrade — 2026-06-08

**Instance:** WDB client production AtoM (`41.162.30.249`, `/usr/share/nginx/atom`, DB `atom`, Library+Museum sectors)

## Versions
- atom-ahg-plugins **v3.41.1 → v3.59.3**
- atom-framework **v2.11.2 → v2.12.3** (composer pulled web-auth/webauthn-lib 5.3.5 + deps)

## Scope (Johan-confirmed)
Full catch-up · no AI chatbot · skip framework base-AtoM patches · 7 new plugins stay disabled · preserve WDB look-and-feel + logo exactly.

## What was done
1. **Backup** — DB dump (20M gz), plugin+framework tarballs, 3 theme mods copied+md5'd, git anchors. At `~johan/wdb-backups/2026-06-08-0204/`.
2. **chown** — ~754 (plugins) + ~3747 (framework) root-owned files reset to `www-data:www-data` (leftover from past root-run tasks; were blocking `git pull` with "unable to unlink … Permission denied").
3. **Plugins** — `git reset --hard origin/main` to v3.59.3 (partial-pull recovery), theme mods reapplied via `git apply --3way theme-mods.patch`. Demo-creds removal preserved; logo.png md5 unchanged.
4. **Framework** — reset to v2.12.3 + `composer install --no-dev` (webauthn-lib present).
5. **Migrations** (`mysql --force`, enabled plugins only): webauthn table, audit-chain cols + state, all 12 library_* tables + serials/ILL/Z39.50/SUSHI/RDA/MARC, research portal, metadata extraction, IIIF saved-view + object-security-classification. One harmless skip: `migration_full_library.sql` L656 `ahg_dropdown_column_map` INSERT (ahgCustomFieldsPlugin disabled on WDB).
6. **Cache + restart** — `rm -rf cache/qubit/{prod,cli}` + `symfony cc` + `systemctl restart php8.3-fpm`. Audit chain sealed forward from id=9531.

## Verification
Homepage / login / IO browse / actor browse / actor view / physicalobject/add / `/index.php/library` / `display/browse?type=library` all **HTTP 200**. Library/Museum browse: 614 results. Zero new `ahg_error_log` rows post-restart. Audit chain verifies intact.

## Deliberate follow-ups (NOT done)
- Full ES reindex (`search:populate`) skipped — additive schema only; run off-peak if search anomalies appear.
- 7 new plugins (Scan/Ocfl/C2pa/Observability/ResourceSync/RecordsManage/AiCompliance) on disk, disabled.
- Framework base-AtoM patches (physicalobject CSV holdings report, ahgLdapUser) not applied.

## Post-upgrade regression found + fixed: IIIF OpenSeadragon viewer
**Symptom:** record image viewer (default OSD) shows "Failed to initialize image viewer" on WDB library item `…/library/w_library/`. Mirador works.
**Root cause (fleet-wide, NOT WDB-specific):** `ahgIiifPlugin/web/js/iiif-viewer-manager.js` (v3.59.3) calls `this.addShareButton()` (line 353, inside the OSD init try-block) and `this.applyContentState()` (line 357) — **neither method is defined anywhere in the plugin** (the IIIF Content-State/Share feature #70 was wired into the JS but never implemented). `this.addShareButton is not a function` throws → caught → showError "Failed to initialize image viewer". PSIS (also v3.59.3) has the identical broken call → its OSD viewer was broken too.
**Fix:** guard both calls with `typeof this.X === 'function'`. Applied to the PSIS repo copy AND WDB's live file (JS static asset — no restart; browser hard-refresh needed). Verified `node --check` OK, served asset contains the guard.
**Follow-up:** release the repo fix (patch) so PSIS + ANC + future deploys get it; implement the actual Share button later if desired. WDB's live file now differs from git until the release lands (content will match → clean future pull).

## Second regression found + fixed: RouteLoader any() routes reject POST (framework v2.12.3)
**Symptom:** saving an edit to a **library item** → "page can't be found" (404). Archive + museum edits fine.
**Root cause:** framework `RouteLoader::register()` (changed in v2.11.23, the #129 API verb-dispatch fix) wrapped **every** route — including `any()` routes — in `\sfRequestRoute`. `sfRequestRoute` defaults `sf_method` to **GET/HEAD only** when none is set (`sfRequestRoute.class.php:32`), so every RouteLoader `any()` route silently began rejecting POST. Library registers its routes **exclusively via RouteLoader** (no `config/routing.yml`), so its edit-**save POST** 404'd. Museum uses `config/routing.yml` and base AtoM uses static routes → unaffected. Confirmed by GET `/library/:slug/edit`=403 (matches, auth-gated) vs POST=404 (no match). Fleet-wide (PSIS + ANC too); introduced by the framework bump.
**Fix:** `atom-framework/src/Routing/RouteLoader.php::register()` — choose route class by method: `$routeClass = !empty($route['methods']) ? \sfRequestRoute::class : \sfRoute::class;` for both the main and `_ts` variant. any() → plain `sfRoute` (all verbs); get()/post() keep `sfRequestRoute` (preserves #129). Applied to WDB live (cache-clear + php-fpm restart) — POST `/library/:slug/edit` now 403 (matches), `POST /opac` 200, 0 new errors. PSIS repo fixed; needs `./bin/release` (atom-framework). WDB live file diffs from git until release+deploy.

## Reusable gotchas → see memory `wdb_upgrade_2026_06_08`
chown both repos before pull · stash fails on WDB (use patch) · reset --hard recovery · mysql --force enabled-only.
